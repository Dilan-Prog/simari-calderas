<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\MonitoringEntry;
use App\Models\MonitoringEquipment;
use App\Models\MonitoringProgram;
use App\Services\MonitoringService;
use App\Services\WaterAnalysisInsights;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Portal de servicios > Monitoreo. El cliente solo ve programas, equipos y
 * registros que le pertenecen (customer_id = cliente autenticado); cualquier
 * otro id responde 404.
 */
class MonitoringController extends Controller
{
    public const TABS = ['registro', 'semanas', 'gantt', 'graficas'];

    public function __construct(private MonitoringService $service)
    {
    }

    private function customerId(): int
    {
        return (int) auth('customer')->id();
    }

    private function ownedProgram(int|string $id): MonitoringProgram
    {
        return MonitoringProgram::where('customer_id', $this->customerId())->findOrFail($id);
    }

    public function index(Request $request, WaterAnalysisInsights $insights): View
    {
        $programs = MonitoringProgram::where('customer_id', $this->customerId())
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'paused' THEN 1 ELSE 2 END")
            ->orderByDesc('start_date')
            ->get();

        $daysLogged = $programs->isEmpty() ? collect() : MonitoringEntry::whereIn('program_id', $programs->pluck('id'))
            ->select('program_id', DB::raw('COUNT(DISTINCT entry_date) as days'))
            ->groupBy('program_id')
            ->pluck('days', 'program_id');

        $today = now()->startOfDay();

        $cards = $programs->map(function (MonitoringProgram $p) use ($daysLogged, $today) {
            $total = $p->weeks * 7;
            $elapsed = $today->lessThan($p->start_date) ? 0 : min($total, (int) $p->start_date->diffInDays($today) + 1);

            return [
                'program'      => $p,
                'stats'        => $this->service->programStats($p),
                'current_week' => $p->currentWeek(),
                'end'          => $p->endDate(),
                'not_started'  => $today->lessThan($p->start_date),
                'finished'     => $today->greaterThan($p->endDate()),
                'days_logged'  => (int) ($daysLogged[$p->id] ?? 0),
                'days_elapsed' => $elapsed,
                'days_total'   => $total,
            ];
        });

        $alerts = $this->service->alerts($this->customerId(), 30)->take(15);

        // Gráficas del Reporte de Análisis Químico (solo si el usuario puede ver ese módulo).
        $analysis = \App\Support\PortalContext::can('water-analysis', 'view')
            ? $insights->forCustomer($this->customerId(), (int) $request->query('dias', 30))
            : null;

        return view('customers.monitoring.index', [
            'cards'    => $cards,
            'alerts'   => $alerts,
            'analysis' => $analysis,
        ]);
    }

    public function show(Request $request, string $program): View
    {
        $prog = $this->ownedProgram($program);
        $customerId = $this->customerId();

        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'registro';

        $items = $prog->items()->withCount('entries')->get();

        // Todos los equipos propios (para filtrar); los activos alimentan los formularios.
        $allEquipment = MonitoringEquipment::where('customer_id', $customerId)->orderBy('name')->get();
        $activeEquipment = $allEquipment->where('is_active', true)->values();

        $equipmentId = null;
        if ($request->filled('equipment')) {
            $match = $allEquipment->firstWhere('id', (int) $request->query('equipment'));
            $equipmentId = $match?->id; // un id ajeno se ignora, no filtra datos de otros
        }

        $today = now()->startOfDay();
        $currentWeek = $prog->currentWeek();
        $status = $today->lessThan($prog->start_date) ? 'not_started' : ($today->greaterThan($prog->endDate()) ? 'finished' : 'running');
        $defaultWeek = $currentWeek ?? ($status === 'finished' ? $prog->weeks : 1);

        $data = [
            'program'         => $prog,
            'items'           => $items,
            'tab'             => $tab,
            'equipmentId'     => $equipmentId,
            'allEquipment'    => $allEquipment,
            'activeEquipment' => $activeEquipment,
            'currentWeek'     => $currentWeek,
            'runState'        => $status,
            'stats'           => $this->service->programStats($prog),
            'endDate'         => $prog->endDate(),
            'maxEntryDate'    => $prog->endDate()->lessThan($today) ? $prog->endDate() : $today,
        ];

        if ($tab === 'registro') {
            $weekParam = $request->query('week');
            $week = $weekParam === 'all' ? null : (is_numeric($weekParam) ? max(1, min($prog->weeks, (int) $weekParam)) : $defaultWeek);

            $entries = MonitoringEntry::with(['item', 'equipment'])
                ->where('program_id', $prog->id)
                ->when($equipmentId, fn ($q) => $q->where('equipment_id', $equipmentId))
                ->when($week !== null, fn ($q) => $q->whereBetween('entry_date', [
                    $prog->weekStart($week)->toDateString(),
                    $prog->weekEnd($week)->toDateString(),
                ]))
                ->orderByDesc('entry_date')
                ->orderByDesc('id')
                ->paginate(40)
                ->withQueryString();

            $data += ['entries' => $entries, 'week' => $week];
        }

        if ($tab === 'semanas') {
            $data['weekSummaries'] = $this->service->weekSummaries($prog, $equipmentId);
        }

        if ($tab === 'gantt') {
            $data['gantt'] = $this->service->gantt($prog, true, $equipmentId);
        }

        if ($tab === 'graficas') {
            $itemId = (int) $request->query('item');
            $item = $items->firstWhere('id', $itemId)
                ?? $items->first(fn ($i) => $i->entries_count > 0)
                ?? $items->first();

            $data['chartItem'] = $item;
            $data['series'] = $item ? $this->service->chartSeries($prog, $item->id, $equipmentId) : null;
            $data['itemStats'] = $item ? MonitoringEntry::where('program_id', $prog->id)
                ->where('item_id', $item->id)
                ->when($equipmentId, fn ($q) => $q->where('equipment_id', $equipmentId))
                ->selectRaw('COUNT(*) AS n, COUNT(DISTINCT entry_date) AS days, AVG(value) AS avg_v, MIN(value) AS min_v, MAX(value) AS max_v, SUM(value) AS sum_v, SUM(CASE WHEN in_range = 0 THEN 1 ELSE 0 END) AS oor')
                ->first() : null;
        }

        return view('customers.monitoring.show', $data);
    }

    /** Serie de una gráfica en JSON (mismo alcance que la pestaña Gráficas). */
    public function series(Request $request, string $program): JsonResponse
    {
        $prog = $this->ownedProgram($program);

        $item = $prog->items()->findOrFail((int) $request->query('item'));

        $equipmentId = null;
        if ($request->filled('equipment')) {
            $equipmentId = MonitoringEquipment::where('customer_id', $this->customerId())
                ->where('id', (int) $request->query('equipment'))
                ->value('id');
        }

        return response()->json($this->service->chartSeries($prog, $item->id, $equipmentId));
    }

    /** Ajusta solo la fecha de inicio; las semanas no cambian y ningún registro puede quedar fuera. */
    public function updateDates(Request $request, string $program): RedirectResponse
    {
        $prog = $this->ownedProgram($program);

        $data = $request->validateWithBag('dates', [
            'start_date' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
        ], [
            'start_date.required'       => 'Indica la nueva fecha de inicio.',
            'start_date.date'           => 'La fecha de inicio no es válida.',
            'start_date.after_or_equal' => 'La fecha de inicio no es válida.',
            'start_date.before_or_equal' => 'La fecha de inicio no es válida.',
        ]);

        $newStart = Carbon::parse($data['start_date'])->startOfDay();
        $newEnd = $newStart->copy()->addDays($prog->weeks * 7 - 1);

        $bounds = $prog->entries()->selectRaw('MIN(entry_date) as first_date, MAX(entry_date) as last_date')->first();

        if ($bounds && $bounds->first_date) {
            $first = Carbon::parse($bounds->first_date)->startOfDay();
            $last = Carbon::parse($bounds->last_date)->startOfDay();

            if ($first->lessThan($newStart) || $last->greaterThan($newEnd)) {
                throw ValidationException::withMessages([
                    'start_date' => 'Con esas fechas quedarían registros fuera del programa (tienes registros del '
                        . $first->format('d/m/Y') . ' al ' . $last->format('d/m/Y')
                        . '). Elige un inicio que los incluya: entre el '
                        . $last->copy()->subDays($prog->weeks * 7 - 1)->format('d/m/Y') . ' y el ' . $first->format('d/m/Y') . '.',
                ])->errorBag('dates');
            }
        }

        $prog->update(['start_date' => $newStart->toDateString()]);

        return redirect()
            ->route('customer.monitoring.show', ['program' => $prog->id, 'tab' => in_array($request->input('tab'), self::TABS, true) ? $request->input('tab') : 'registro'])
            ->with('success', 'Fechas del programa actualizadas: del ' . $newStart->format('d/m/Y') . ' al ' . $newEnd->format('d/m/Y') . '.');
    }
}
