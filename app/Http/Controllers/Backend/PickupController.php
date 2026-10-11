<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Pickup;
use App\Models\PickupEvidence;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Erp\DocumentFlow;
use App\Services\PickupService;
use App\Support\PickupStatus;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class PickupController extends Controller
{
    public function __construct(private PickupService $service)
    {
    }

    public function index(Request $request): View
    {
        $counts = Pickup::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $query = Pickup::with(['customer', 'supplier', 'driver', 'carrier'])->withCount('evidences')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('driver_user_id'), fn ($q) => $q->where('driver_user_id', $request->driver_user_id))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('scheduled_date', $request->date))
            ->when($request->filled('q'), function ($q) use ($request) {
                $t = $request->q;
                $q->where(fn ($s) => $s->where('number', 'like', "%{$t}%")
                    ->orWhere('contact_name', 'like', "%{$t}%")
                    ->orWhere('address', 'like', "%{$t}%"));
            })
            ->orderByRaw('scheduled_date is null')
            ->orderBy('scheduled_date')
            ->orderByDesc('id');

        return view('admin.pickups.index', [
            'pickups' => $query->paginate(30)->withQueryString(),
            'counts' => $counts,
            'filters' => $request->only(['q', 'status', 'type', 'driver_user_id', 'date']),
            'drivers' => User::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function create(Request $request): View
    {
        $pickup = new Pickup(['type' => 'pedidos', 'scheduled_date' => now()->addDay()]);
        $items = [];

        if ($request->filled('sales_order') && ($order = SalesOrder::with('customer.defaultAddress', 'items')->find($request->sales_order))) {
            $pickup->customer_id = $order->customer_id;
            $pickup->contact_name = $order->customer ? trim($order->customer->first_name . ' ' . $order->customer->last_name) : null;
            $pickup->contact_phone = $order->customer?->phone;
            $a = $order->customer?->defaultAddress;
            $pickup->address = $a ? implode(', ', array_filter([$a->address_line1, $a->address_line2, $a->city, $a->state, $a->postal_code])) : null;
            $items[] = ['description' => 'Pedido ' . $order->order_number, 'quantity' => 1, 'unit' => 'pedido', 'sales_order_id' => $order->id];
        }

        return $this->form($pickup, $items, 'create');
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $items] = $this->validated($request);
        $pickup = $this->service->create($data, $items, auth()->id());

        return redirect()->route('admin.pickups.show', $pickup)->with('success', "Recolección {$pickup->number} creada.");
    }

    public function show(Pickup $pickup): View
    {
        $pickup->load(['customer', 'supplier', 'driver', 'carrier', 'items.salesOrder', 'evidences', 'statusLogs.changedBy', 'createdBy']);

        return view('admin.pickups.show', [
            'pickup' => $pickup,
            'allowed' => PickupStatus::allowedTransitions($pickup->status),
        ]);
    }

    public function edit(Pickup $pickup): View|RedirectResponse
    {
        if (! PickupStatus::isOpen($pickup->status)) {
            return redirect()->route('admin.pickups.show', $pickup)->with('error', 'Esta recolección ya está cerrada.');
        }

        $items = $pickup->items->map(fn ($i) => $i->only(['description', 'quantity', 'unit', 'sales_order_id', 'product_id']))->all();

        return $this->form($pickup, $items, 'edit');
    }

    public function update(Request $request, Pickup $pickup): RedirectResponse
    {
        if (! PickupStatus::isOpen($pickup->status)) {
            return back()->with('error', 'Esta recolección ya está cerrada.');
        }
        [$data, $items] = $this->validated($request);
        $this->service->update($pickup, $data, $items);

        return redirect()->route('admin.pickups.show', $pickup)->with('success', 'Recolección actualizada.');
    }

    public function destroy(Pickup $pickup): RedirectResponse
    {
        if (! in_array($pickup->status, ['programada', 'cancelada'], true)) {
            return back()->with('error', 'Solo se pueden eliminar recolecciones programadas o canceladas.');
        }
        DocumentFlow::unlinkAll($pickup);
        $pickup->delete();

        return redirect()->route('admin.pickups.index')->with('success', 'Recolección eliminada.');
    }

    public function updateStatus(Request $request, Pickup $pickup): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(PickupStatus::META))],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->service->changeStatus($pickup, $data['status'], $data['note'] ?? null, auth()->id());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Estatus actualizado a ' . PickupStatus::label($data['status']) . '.');
    }

    public function storeEvidence(Request $request, Pickup $pickup): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:10240'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $ev = $this->service->addEvidence($pickup, $request->file('photo'), $request->input('caption'), auth()->id());

        return $ev
            ? back()->with('success', 'Foto agregada.')
            : back()->with('error', 'No se pudo procesar la imagen.');
    }

    public function destroyEvidence(Pickup $pickup, PickupEvidence $evidence): RedirectResponse
    {
        abort_unless($evidence->pickup_id === $pickup->id, 404);
        $evidence->delete();

        return back()->with('success', 'Foto eliminada.');
    }

    public function sign(Request $request, Pickup $pickup): RedirectResponse
    {
        $data = $request->validate([
            'signature_data' => ['required', 'string'],
            'signed_by_name' => ['nullable', 'string', 'max:160'],
        ]);

        try {
            $this->service->saveSignature($pickup, $data['signature_data'], $data['signed_by_name'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['signature_data' => $e->getMessage()]);
        }

        return back()->with('success', 'Firma guardada.');
    }

    /** Captura móvil: foto + firma + marcar entregada. */
    public function mobile(Pickup $pickup): View
    {
        $pickup->load(['customer', 'items', 'evidences']);

        return view('admin.pickups.mobile', [
            'pickup' => $pickup,
            'allowed' => PickupStatus::allowedTransitions($pickup->status),
        ]);
    }

    /** Ruta del día: recolecciones y envíos del día agrupados por chofer / paquetería. */
    public function dayRoute(Request $request): View
    {
        $date = $request->filled('date') ? Carbon::parse($request->date)->startOfDay() : today();

        $pickups = Pickup::with(['customer', 'driver', 'carrier'])
            ->whereDate('scheduled_date', $date)
            ->whereNotIn('status', ['cancelada'])
            ->orderBy('time_window')->orderBy('id')->get();

        $shipments = Shipment::with(['carrier', 'storeOrder'])
            ->where(fn ($q) => $q->whereDate('fecha_envio', $date)->orWhereDate('eta', $date))
            ->where('status', '!=', 'entregado')
            ->orderBy('id')->get();

        $groups = [];
        foreach ($pickups as $p) {
            $key = $p->driver ? 'd' . $p->driver->id : ($p->carrier ? 'c' . $p->carrier->id : 'none');
            $label = $p->driver ? 'Chofer: ' . trim($p->driver->first_name . ' ' . $p->driver->last_name)
                : ($p->carrier ? 'Paquetería: ' . $p->carrier->name : 'Sin asignar');
            $groups[$key]['label'] = $label;
            $groups[$key]['pickups'][] = $p;
        }
        foreach ($shipments as $s) {
            $key = $s->carrier ? 'c' . $s->carrier->id : 'none';
            $groups[$key]['label'] ??= $s->carrier ? 'Paquetería: ' . $s->carrier->name : 'Sin asignar';
            $groups[$key]['shipments'][] = $s;
        }
        ksort($groups);

        return view('admin.pickups.route', [
            'date' => $date,
            'groups' => $groups,
            'totals' => ['pickups' => $pickups->count(), 'shipments' => $shipments->count()],
        ]);
    }

    private function form(Pickup $pickup, array $items, string $mode): View
    {
        return view('admin.pickups.form', [
            'pickup' => $pickup,
            'items' => $items ?: [['description' => '', 'quantity' => 1, 'unit' => '', 'sales_order_id' => null]],
            'mode' => $mode,
            'customers' => Customer::orderBy('first_name')->limit(500)->get(['id', 'first_name', 'last_name', 'company']),
            'suppliers' => Supplier::orderBy('company_name')->limit(500)->get(['id', 'company_name', 'contact_name']),
            'drivers' => User::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'carriers' => Delivery::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(PickupStatus::TYPES))],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'contact_name' => ['nullable', 'string', 'max:160'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:1000'],
            'scheduled_date' => ['required', 'date'],
            'time_window' => ['nullable', 'string', 'max:60'],
            'driver_user_id' => ['nullable', 'exists:users,id'],
            'carrier_id' => ['nullable', 'exists:carriers,id'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
            'items.*.sales_order_id' => ['nullable', 'exists:sales_orders,id'],
            'items.*.product_id' => ['nullable', 'integer'],
        ]);

        $items = $data['items'] ?? [];
        unset($data['items']);

        return [$data, $items];
    }
}
