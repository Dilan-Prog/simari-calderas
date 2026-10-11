<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\ShipmentStatusLog;
use App\Models\User;
use App\Services\Erp\DocumentFlow;
use App\Support\ShipmentStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Envíos B2B: un Shipment ligado a un pedido B2B (SalesOrder) o a una
 * cotización (Quote) en lugar de un StoreOrder. Reutiliza ShipmentStatus,
 * ShipmentStatusLog y la ruta de cambio de estatus del módulo de Envíos.
 */
class ShipmentB2bController extends Controller
{
    public function create(Request $request): View
    {
        $order = $request->filled('sales_order') ? SalesOrder::with(['customer.defaultAddress', 'quote'])->find($request->sales_order) : null;
        $quote = $request->filled('quote') ? Quote::with('customer.defaultAddress')->find($request->quote) : ($order?->quote);

        $shipment = new Shipment($this->prefill($order, $quote));
        $shipment->fecha_envio = now();

        return view('admin.shipments.b2b-form', [
            'shipment' => $shipment,
            'mode' => 'create',
            'order' => $order,
            'quote' => $quote,
            'carriers' => Delivery::where('is_active', true)->orderBy('name')->get(),
            'orders' => SalesOrder::where('status', '!=', 'cancelado')->latest('id')->limit(100)->get(['id', 'order_number']),
            'quotes' => Quote::latest('id')->limit(100)->get(['id', 'quote_number']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $order = $data['shippable_type'] === 'sales_order' ? SalesOrder::findOrFail($data['shippable_id']) : null;
        $quote = $data['shippable_type'] === 'quote'
            ? Quote::findOrFail($data['shippable_id'])
            : (! empty($data['quote_id']) ? Quote::find($data['quote_id']) : $order?->quote);

        $shipment = DB::transaction(function () use ($data, $order, $quote) {
            $shipment = Shipment::create(array_merge($data, [
                'quote_id' => $data['quote_id'] ?? $quote?->id,
                'customer_id' => $data['customer_id'] ?? $order?->customer_id ?? $quote?->customer_id,
                'status' => 'preparando',
                'shipped_at' => $data['fecha_envio'] ?? null,
                'created_by_user_id' => auth()->id(),
            ]));

            ShipmentStatusLog::create([
                'shipment_id' => $shipment->id,
                'from_status' => null,
                'to_status' => 'preparando',
                'note' => 'Envío registrado',
                'changed_by_user_id' => auth()->id(),
            ]);

            return $shipment;
        });

        // Document Flow: pedido → envío (o cotización → envío si no hay pedido).
        DocumentFlow::link($order ?? $quote, $shipment);
        if ($order && $quote) {
            DocumentFlow::link($quote, $order);
        }

        return redirect()->route('admin.shipments.show', $shipment)->with('success', 'Envío registrado correctamente.');
    }

    public function show(Shipment $shipment): View
    {
        abort_if(! $shipment->isB2b(), 404);
        $shipment->load(['carrier', 'customer', 'quote', 'statusLogs.changedBy', 'createdBy']);

        return view('admin.shipments.show', [
            'shipment' => $shipment,
            'statusMeta' => ShipmentStatus::META,
            'motivos' => ShipmentStatus::MOTIVOS,
            'allowed' => ShipmentStatus::allowedTransitions($shipment->status),
            'source' => $shipment->shippable_type === 'sales_order' ? SalesOrder::find($shipment->shippable_id) : null,
        ]);
    }

    public function edit(Shipment $shipment): View
    {
        abort_if(! $shipment->isB2b(), 404);

        return view('admin.shipments.b2b-form', [
            'shipment' => $shipment,
            'mode' => 'edit',
            'order' => null,
            'quote' => $shipment->quote,
            'carriers' => Delivery::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Shipment $shipment): RedirectResponse
    {
        abort_if(! $shipment->isB2b(), 404);
        $data = $this->validated($request, false);
        unset($data['shippable_type'], $data['shippable_id']);
        $data['shipped_at'] = $data['fecha_envio'] ?? $shipment->shipped_at;
        $shipment->update($data);

        return redirect()->route('admin.shipments.show', $shipment)->with('success', 'Envío actualizado.');
    }

    private function validated(Request $request, bool $creating = true): array
    {
        $rules = [
            'carrier_id' => ['nullable', 'exists:carriers,id'],
            'guia' => ['required', 'string', 'max:80'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'order_number_ref' => ['nullable', 'string', 'max:60'],
            'recipient_name' => ['required', 'string', 'max:160'],
            'recipient_company' => ['nullable', 'string', 'max:160'],
            'origin_address' => ['nullable', 'string', 'max:1000'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'recipient_phone' => ['nullable', 'string', 'max:40'],
            'recipient_email' => ['nullable', 'email', 'max:160'],
            'quote_id' => ['nullable', 'exists:quotes,id'],
            'fecha_envio' => ['required', 'date'],
            'eta' => ['nullable', 'date', 'after_or_equal:fecha_envio'],
            'bultos' => ['required', 'integer', 'min:1'],
            'peso' => ['nullable', 'numeric', 'min:0'],
            'costo' => ['nullable', 'numeric', 'min:0'],
        ];
        if ($creating) {
            if ($request->filled('source') && str_contains($request->source, ':')) {
                [$t, $i] = explode(':', $request->source, 2);
                $request->merge(['shippable_type' => $t, 'shippable_id' => $i]);
            }
            $rules['shippable_type'] = ['required', 'in:sales_order,quote'];
            $rules['shippable_id'] = ['required', 'integer'];
        }

        return $request->validate($rules);
    }

    private function prefill(?SalesOrder $order, ?Quote $quote): array
    {
        $customer = $order?->customer ?? $quote?->customer;
        $addr = $customer?->defaultAddress;
        $fullName = $customer ? trim($customer->first_name . ' ' . $customer->last_name) : ($quote?->guest_name);

        return [
            'shippable_type' => $order ? 'sales_order' : ($quote ? 'quote' : null),
            'shippable_id' => $order?->id ?? $quote?->id,
            'customer_id' => $customer?->id,
            'order_number_ref' => $order?->order_number ?? $quote?->quote_number,
            'quote_id' => $quote?->id,
            'recipient_name' => $fullName,
            'recipient_company' => $customer?->company ?? $quote?->guest_company,
            'recipient_phone' => $addr?->phone ?? $customer?->phone ?? $quote?->guest_phone,
            'recipient_email' => $customer?->email ?? $quote?->guest_email,
            'delivery_address' => $addr ? trim(implode(', ', array_filter([$addr->address_line1, $addr->address_line2, $addr->city, $addr->state, $addr->postal_code]))) : null,
        ];
    }
}
