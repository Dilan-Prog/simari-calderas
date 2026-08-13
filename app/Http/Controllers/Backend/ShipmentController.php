<?php

namespace App\Http\Controllers\Backend;

use App\Actions\AdvanceStoreOrderStatus;
use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Shipment;
use App\Models\ShipmentStatusLog;
use App\Models\StoreOrder;
use App\Models\UserColumnPreference;
use App\Support\ShipmentStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Módulo Envíos — un Shipment vive 1:1 con un StoreOrder (checkout público).
 * Los avances de estatus del envío (registro inicial y entrega) pueden
 * empujar automáticamente el estatus de la orden via AdvanceStoreOrderStatus,
 * que es un no-op silencioso si la transición ya no aplica.
 */
class ShipmentController extends Controller
{
    public function index(Request $request): View
    {
        // Una sola query agrupada para KPIs y contadores de tabs.
        $statusCounts = Shipment::selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $countFor = fn (string $status) => (int) ($statusCounts[$status] ?? 0);

        $kpis = [
            'activos'        => $countFor('preparando') + $countFor('enviado') + $countFor('en_transito'),
            'en_transito'    => $countFor('en_transito'),
            'incidencia'     => $countFor('incidencia'),
            'entregados_mes' => Shipment::where('status', 'entregado')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
        ];

        $tabs = ['todos' => (int) $statusCounts->sum()];
        foreach (array_keys(ShipmentStatus::META) as $status) {
            $tabs[$status] = $countFor($status);
        }

        $shipments = $this->filteredShipmentsQuery($request)
            ->paginate(30)
            ->withQueryString();

        $carriers = Delivery::where('is_active', true)->orderBy('name')->get();
        $statusMeta = ShipmentStatus::META;

        $visibleColumns = UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'shipments.index')
            ->value('columns');

        $filters = $request->only(['q', 'carrier_id', 'tab']);

        return view('admin.shipments.index', compact(
            'shipments',
            'kpis',
            'tabs',
            'carriers',
            'statusMeta',
            'visibleColumns',
            'filters'
        ));
    }

    public function store(Request $request, StoreOrder $order): RedirectResponse
    {
        if ($order->shipment) {
            return back()->with('error', 'Esta orden ya tiene un envío registrado.');
        }

        $validated = $request->validate([
            'carrier_id'             => ['nullable', 'exists:carriers,id'],
            'guia'                   => ['required', 'string'],
            'fecha_envio'            => ['required', 'date'],
            'eta'                    => ['nullable', 'date', 'after_or_equal:fecha_envio'],
            'bultos'                 => ['required', 'integer', 'min:1'],
            'peso'                   => ['nullable', 'numeric', 'min:0'],
            'costo'                  => ['nullable', 'numeric', 'min:0'],
            'authorized_by_user_id'  => ['nullable', 'exists:users,id'],
            'avanzar'                => ['nullable', 'boolean'],
        ]);

        // Strip-then-measure no se expresa limpio como regla de validación
        // declarativa, se hace en código.
        if (mb_strlen(str_replace(' ', '', $validated['guia'])) < 8) {
            return back()
                ->withErrors(['guia' => 'La guía debe tener al menos 8 caracteres (sin contar espacios).'])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $order) {
            $shipment = Shipment::create([
                'store_order_id'         => $order->id,
                'carrier_id'             => $validated['carrier_id'] ?? null,
                'guia'                   => $validated['guia'],
                'status'                 => 'preparando',
                'fecha_envio'            => $validated['fecha_envio'],
                'eta'                    => $validated['eta'] ?? null,
                'bultos'                 => $validated['bultos'],
                'peso'                   => $validated['peso'] ?? null,
                'costo'                  => $validated['costo'] ?? null,
                'authorized_by_user_id'  => $validated['authorized_by_user_id'] ?? null,
                'created_by_user_id'     => auth()->id(),
            ]);

            ShipmentStatusLog::create([
                'shipment_id'        => $shipment->id,
                'from_status'        => null,
                'to_status'          => 'preparando',
                'note'               => 'Envío registrado',
                'changed_by_user_id' => auth()->id(),
            ]);
        });

        if ($request->boolean('avanzar')) {
            (new AdvanceStoreOrderStatus)($order, 'enviado', 'Avance automático por registro de envío');
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Envío registrado correctamente.');
    }

    public function updateStatus(Request $request, Shipment $shipment): RedirectResponse
    {
        $allowed = ShipmentStatus::allowedTransitions($shipment->status);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in($allowed)],
            'motivo' => ['nullable', 'string', Rule::in(array_keys(ShipmentStatus::MOTIVOS))],
            'note'   => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['status'] === 'incidencia') {
            if (empty($validated['motivo'])) {
                return back()->withErrors(['motivo' => 'Selecciona un motivo de incidencia.'])->withInput();
            }
            if (empty($validated['note'])) {
                return back()->withErrors(['note' => 'Describe la incidencia en la nota interna.'])->withInput();
            }
        }

        DB::transaction(function () use ($shipment, $validated) {
            ShipmentStatusLog::create([
                'shipment_id'        => $shipment->id,
                'from_status'        => $shipment->status,
                'to_status'          => $validated['status'],
                'note'               => $validated['note'] ?? null,
                'motivo'             => $validated['motivo'] ?? null,
                'changed_by_user_id' => auth()->id(),
            ]);

            $shipment->update([
                'status'          => $validated['status'],
                'motivo'          => $validated['status'] === 'incidencia' ? $validated['motivo'] : $shipment->motivo,
                'incidencia_note' => $validated['status'] === 'incidencia' ? $validated['note'] : $shipment->incidencia_note,
            ]);
        });

        if ($validated['status'] === 'entregado') {
            (new AdvanceStoreOrderStatus)($shipment->storeOrder, 'entregado', 'Avance automático: envío entregado');
        }

        return redirect()->route('admin.orders.show', $shipment->store_order_id)
            ->with('success', 'Estatus del envío actualizado.');
    }

    public function destroy(Shipment $shipment): RedirectResponse
    {
        if (! in_array($shipment->status, ['preparando', 'enviado'], true)) {
            return back()->with('error', 'Este envío ya no se puede eliminar en su estatus actual.');
        }

        $orderId = $shipment->store_order_id;
        $shipment->delete();

        return redirect()->route('admin.orders.show', $orderId)
            ->with('success', 'Envío eliminado correctamente.');
    }

    public function export(Request $request)
    {
        $shipments = $this->filteredShipmentsQuery($request)->get();

        $rows = $shipments->map(fn (Shipment $s) => [
            'orden'          => $s->storeOrder?->order_number,
            'cliente'        => $s->storeOrder?->contact_name,
            'transportista'  => $s->carrier?->name,
            'guia'           => $s->guia,
            'estatus'        => ShipmentStatus::meta($s->status)['label'] ?? $s->status,
            'bultos'         => $s->bultos,
            'costo'          => $s->costo,
            'fecha_envio'    => $s->fecha_envio?->format('d/m/Y'),
            'eta'            => $s->eta?->format('d/m/Y'),
        ]);

        $headings = ['Orden', 'Cliente', 'Transportista', 'Guía', 'Estatus', 'Bultos', 'Costo', 'Fecha de envío', 'ETA'];

        return Excel::download(new ReportExport($rows, $headings), 'envios-' . now()->format('Y-m-d') . '.csv', ExcelFormat::CSV);
    }

    private function filteredShipmentsQuery(Request $request): Builder
    {
        $query = Shipment::query()->with(['storeOrder', 'carrier']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('guia', 'like', "%{$q}%")
                    ->orWhereHas('storeOrder', function ($sq) use ($q) {
                        $sq->where('order_number', 'like', "%{$q}%")
                            ->orWhere('contact_name', 'like', "%{$q}%")
                            ->orWhere('contact_email', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->carrier_id);
        }

        if ($request->filled('tab') && $request->tab !== 'todos') {
            $query->where('status', $request->tab);
        }

        return $query;
    }
}
