<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Reservas de material por proyecto (Almacén > ALM3). Lista las reservas activas
 * agrupadas por proyecto y permite liberarlas. Permiso: `inventory` (módulo Inventario).
 */
class StockReservationController extends Controller
{
    public function index(Request $request)
    {
        $reservations = collect();
        $projects = collect();

        if (Schema::hasTable('stock_reservations')) {
            $query = StockReservation::active()
                ->with(['product:id,name,sku', 'warehouse:id,name', 'project:id,project_number,name', 'requirement:id,number,needed_by_date'])
                ->when($request->filled('project_id'), function ($q) use ($request) {
                    $request->input('project_id') === 'none'
                        ? $q->whereNull('project_id')
                        : $q->where('project_id', (int) $request->input('project_id'));
                })
                ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', (int) $request->input('warehouse_id')))
                ->when(trim((string) $request->input('search')) !== '', function ($q) use ($request) {
                    $t = trim((string) $request->input('search'));
                    $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$t}%")->orWhere('sku', 'like', "%{$t}%"));
                })
                ->orderBy('project_id')->orderBy('product_id')->orderBy('id');

            $reservations = $query->limit(1000)->get();

            // Proyectos con reservas activas (para el filtro), independientemente del filtro actual.
            $projectIds = StockReservation::active()->whereNotNull('project_id')->distinct()->pluck('project_id');
            $projects = Project::whereIn('id', $projectIds)->orderBy('project_number')->get(['id', 'project_number', 'name']);
        }

        // Agrupa por proyecto y, dentro, por producto.
        $groups = $reservations->groupBy(fn ($r) => $r->project_id ?? 0)->map(function ($rows, $pid) {
            $first = $rows->first();

            return [
                'project' => $first->project,
                'label' => $first->project ? $first->project->project_number . ' · ' . $first->project->name : 'Sin proyecto',
                'total' => (float) $rows->sum('quantity'),
                'products' => $rows->groupBy('product_id'),
            ];
        })->values();

        return view('admin.stock-reservations.index', [
            'groups' => $groups,
            'total' => $reservations->count(),
            'projects' => $projects,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'filters' => $request->only(['project_id', 'warehouse_id', 'search']),
        ]);
    }

    /** Libera una o varias reservas activas (ids[]) — el material vuelve a estar disponible. */
    public function release(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ], ['ids.required' => 'Selecciona al menos una reserva.']);

        // Uno a uno para que quede la bitácora de actividad de cada reserva.
        $rows = StockReservation::active()->whereIn('id', $data['ids'])->get();
        $rows->each->update(['status' => 'liberada']);
        $n = $rows->count();

        return back()->with('success', $n === 1 ? 'Reserva liberada.' : "{$n} reservas liberadas.");
    }
}
