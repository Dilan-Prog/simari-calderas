<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MonitoringEntry;
use App\Models\MonitoringEquipment;
use App\Models\MonitoringProgram;
use App\Models\MonitoringProgramItem;
use App\Models\Products;
use App\Models\User;
use App\Services\MonitoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Monitoreo de agua / químicos de clientes.
 *
 * El personal define PROGRAMAS (N semanas desde una fecha) con ELEMENTOS
 * (parámetros del agua con rango min/max o químicos con dosis diaria planeada
 * y semanas inicio/fin = barras del Gantt) por EQUIPO del cliente. El cliente
 * captura lecturas/dosis desde su portal; aquí se ve todo, alertas, Gantt,
 * gráficas y exportación. Los cálculos viven en App\Services\MonitoringService.
 */
class MonitoringController extends Controller
{
    private const TABS = ['resumen', 'gantt', 'graficas', 'registros', 'alertas'];

    /** Sugerencias editables para armar un programa rápido. */
    private const PRESETS = [
        'caldera' => [
            'label' => 'Agua de caldera',
            'items' => [
                ['kind' => 'parameter', 'name' => 'pH', 'unit' => '', 'limit_min' => 10.5, 'limit_max' => 11.5],
                ['kind' => 'parameter', 'name' => 'Dureza total', 'unit' => 'ppm', 'limit_min' => 0, 'limit_max' => 2],
                ['kind' => 'parameter', 'name' => 'Sólidos disueltos (TDS)', 'unit' => 'ppm', 'limit_min' => 0, 'limit_max' => 3500],
                ['kind' => 'parameter', 'name' => 'Alcalinidad total', 'unit' => 'ppm', 'limit_min' => 0, 'limit_max' => 700],
                ['kind' => 'parameter', 'name' => 'Sulfitos residuales', 'unit' => 'ppm', 'limit_min' => 30, 'limit_max' => 60],
                ['kind' => 'parameter', 'name' => 'Sílice', 'unit' => 'ppm', 'limit_min' => 0, 'limit_max' => 150],
                ['kind' => 'chemical', 'name' => 'Secuestrante de oxígeno', 'unit' => 'L'],
                ['kind' => 'chemical', 'name' => 'Inhibidor de incrustación', 'unit' => 'L'],
                ['kind' => 'chemical', 'name' => 'Alcalinizante', 'unit' => 'L'],
            ],
        ],
        'torre' => [
            'label' => 'Torre de enfriamiento',
            'items' => [
                ['kind' => 'parameter', 'name' => 'pH', 'unit' => '', 'limit_min' => 7.5, 'limit_max' => 9],
                ['kind' => 'parameter', 'name' => 'Sólidos disueltos (TDS)', 'unit' => 'ppm', 'limit_min' => 0, 'limit_max' => 2000],
                ['kind' => 'parameter', 'name' => 'Cloruros', 'unit' => 'ppm', 'limit_min' => 0, 'limit_max' => 250],
                ['kind' => 'parameter', 'name' => 'Dureza total', 'unit' => 'ppm', 'limit_min' => 0, 'limit_max' => 500],
                ['kind' => 'parameter', 'name' => 'Cloro libre', 'unit' => 'ppm', 'limit_min' => 0.2, 'limit_max' => 1],
                ['kind' => 'chemical', 'name' => 'Inhibidor de corrosión', 'unit' => 'L'],
                ['kind' => 'chemical', 'name' => 'Biocida oxidante', 'unit' => 'kg'],
                ['kind' => 'chemical', 'name' => 'Biocida no oxidante', 'unit' => 'L'],
                ['kind' => 'chemical', 'name' => 'Dispersante', 'unit' => 'L'],
            ],
        ],
    ];

    public function __construct(private MonitoringService $service)
    {
    }

    // ─────────────────────────────────────────────────────────────
    //  Listado
    // ─────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $filters = [
            'q'      => trim((string) $request->input('q', '')),
            'status' => (string) $request->input('status', ''),
        ];

        $query = MonitoringProgram::query()->with('customer');

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function (Builder $sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhereHas('customer', function (Builder $c) use ($q) {
                        $c->where('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%")
                            ->orWhere('company', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$q}%"]);
                    });
            });
        }

        if ($filters['status'] !== '' && isset(MonitoringProgram::STATUSES[$filters['status']])) {
            $query->where('status', $filters['status']);
        }

        $programs = $query
            ->orderByRaw("status = 'active' desc")
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [];
        foreach ($programs as $program) {
            $stats[$program->id] = $this->service->programStats($program);
        }

        $equipmentCounts = MonitoringEquipment::query()
            ->whereIn('customer_id', $programs->pluck('customer_id')->unique())
            ->where('is_active', true)
            ->selectRaw('customer_id, count(*) as c')
            ->groupBy('customer_id')
            ->pluck('c', 'customer_id');

        $kpis = [
            'active_programs' => MonitoringProgram::where('status', 'active')->count(),
            'entries_week'    => MonitoringEntry::whereBetween('entry_date', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ])->count(),
            'out_of_range'    => MonitoringEntry::where('entry_type', MonitoringEntry::TYPE_READING)
                ->where('in_range', false)
                ->where('entry_date', '>=', now()->subDays(30)->toDateString())
                ->count(),
            'customers'       => MonitoringProgram::where('status', 'active')->distinct()->count('customer_id'),
        ];

        return view('admin.monitoring.index', [
            'programs'        => $programs,
            'stats'           => $stats,
            'equipmentCounts' => $equipmentCounts,
            'kpis'            => $kpis,
            'filters'         => $filters,
            'statuses'        => MonitoringProgram::STATUSES,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  Alta / edición de programa
    // ─────────────────────────────────────────────────────────────

    public function create(Request $request): View
    {
        $program = new MonitoringProgram([
            'start_date' => now()->startOfWeek(),
            'weeks'      => 4,
            'status'     => 'active',
        ]);

        $customer = null;
        if ($request->filled('customer_id')) {
            $customer = Customer::find($request->integer('customer_id'));
            $program->customer_id = $customer?->id;
        }

        return $this->formView($program, collect(), $customer);
    }

    public function edit(MonitoringProgram $program): View
    {
        $program->load('customer');
        $items = $program->items()->withCount('entries')->with('product:id,name,sku')->get();

        return $this->formView($program, $items, $program->customer);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProgram($request);

        $program = DB::transaction(function () use ($data) {
            $program = MonitoringProgram::create([
                'customer_id'        => $data['customer_id'],
                'name'               => $data['name'],
                'start_date'         => $data['start_date'],
                'weeks'              => $data['weeks'],
                'status'             => $data['status'],
                'notes'              => $data['notes'] ?? null,
                'created_by_user_id' => auth()->id(),
            ]);

            $this->syncItems($program, $data['items'] ?? []);

            return $program;
        });

        return redirect()->route('admin.monitoring.show', $program)
            ->with('success', 'Programa de monitoreo creado correctamente.');
    }

    public function update(Request $request, MonitoringProgram $program): RedirectResponse
    {
        $data = $this->validateProgram($request);

        // Elementos que desaparecen del formulario: si ya tienen registros se
        // pide confirmación explícita (borrarlos elimina también sus registros).
        $submittedIds = collect($data['items'] ?? [])->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        $withEntries = $program->items()->withCount('entries')->get()
            ->reject(fn (MonitoringProgramItem $i) => in_array($i->id, $submittedIds, true))
            ->filter(fn (MonitoringProgramItem $i) => $i->entries_count > 0);

        if ($withEntries->isNotEmpty() && ! $request->boolean('confirm_cascade')) {
            $names = $withEntries->map(fn ($i) => $i->name . ' (' . $i->entries_count . ' registros)')->implode(', ');

            return back()->withInput()->withErrors([
                'items' => 'No se pueden quitar elementos con registros sin confirmar: ' . $names . '.',
            ]);
        }

        DB::transaction(function () use ($data, $program) {
            $program->update([
                'customer_id' => $data['customer_id'],
                'name'        => $data['name'],
                'start_date'  => $data['start_date'],
                'weeks'       => $data['weeks'],
                'status'      => $data['status'],
                'notes'       => $data['notes'] ?? null,
            ]);

            $this->syncItems($program, $data['items'] ?? []);
        });

        return redirect()->route('admin.monitoring.show', $program)
            ->with('success', 'Programa de monitoreo actualizado correctamente.');
    }

    public function destroy(MonitoringProgram $program): RedirectResponse
    {
        $program->delete(); // items y registros caen en cascada (FK)

        return redirect()->route('admin.monitoring.index')
            ->with('success', 'Programa de monitoreo eliminado.');
    }

    // ─────────────────────────────────────────────────────────────
    //  Detalle con pestañas
    // ─────────────────────────────────────────────────────────────

    public function show(Request $request, MonitoringProgram $program): View
    {
        $program->load('customer');
        $tab = in_array($request->input('tab'), self::TABS, true) ? $request->input('tab') : 'resumen';
        $equipmentId = $request->integer('equipment_id') ?: null;

        $items = $program->items()->withCount('entries')->with('product:id,name,sku')->get();
        $equipment = MonitoringEquipment::where('customer_id', $program->customer_id)->orderBy('name')->get();

        $data = [
            'program'     => $program,
            'tab'         => $tab,
            'items'       => $items,
            'equipment'   => $equipment,
            'equipmentId' => $equipmentId,
            'stats'       => $this->service->programStats($program),
            'tabCounts'   => ['registros' => $program->entries()->count()],
            'recorders'   => [],
        ];

        switch ($tab) {
            case 'resumen':
                $data['weekSummaries'] = $this->service->weekSummaries($program, $equipmentId);
                break;

            case 'gantt':
                $data['gantt'] = $this->service->gantt($program, true, $equipmentId);
                break;

            case 'graficas':
                $itemId = $request->integer('item') ?: null;
                if (! $itemId || ! $items->contains('id', $itemId)) {
                    $itemId = optional($items->firstWhere('kind', MonitoringProgramItem::KIND_PARAMETER) ?? $items->first())->id;
                }
                $data['itemId'] = $itemId;
                $data['series'] = $itemId ? $this->service->chartSeries($program, $itemId, $equipmentId) : null;
                break;

            case 'registros':
                $entries = $this->entriesQuery($program, $request)
                    ->with(['item', 'equipment'])
                    ->orderByDesc('entry_date')
                    ->orderByDesc('id')
                    ->paginate(30)
                    ->withQueryString();
                $data['entries'] = $entries;
                $data['recorders'] = $this->recorderNames($entries->getCollection());
                $data['entryFilters'] = $request->only(['item_id', 'equipment_id', 'from', 'to', 'out']);
                break;

            case 'alertas':
                $days = in_array($request->integer('days'), [7, 30, 90, 365], true) ? $request->integer('days') : 30;
                $data['days'] = $days;
                $data['alerts'] = $this->service->alerts($program->customer_id, $days, $program->id);
                break;
        }

        return view('admin.monitoring.show', $data);
    }

    // ─────────────────────────────────────────────────────────────
    //  Alertas globales
    // ─────────────────────────────────────────────────────────────

    public function alerts(Request $request): View
    {
        $days = in_array($request->integer('days'), [7, 30, 90, 365], true) ? $request->integer('days') : 30;
        $customerId = $request->integer('customer_id') ?: null;

        $alerts = $this->service->alerts($customerId, $days);

        $customers = Customer::query()
            ->whereIn('id', MonitoringProgram::query()->select('customer_id'))
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'company']);

        return view('admin.monitoring.alerts', [
            'alerts'     => $alerts,
            'days'       => $days,
            'customerId' => $customerId,
            'customers'  => $customers,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  Registros hechos por el personal en nombre del cliente
    // ─────────────────────────────────────────────────────────────

    public function storeEntry(Request $request, MonitoringProgram $program): RedirectResponse
    {
        $data = $this->validateEntry($request, $program);
        $item = $program->items()->findOrFail($data['item_id']);

        MonitoringEntry::create([
            'program_id'          => $program->id,
            'item_id'             => $item->id,
            'equipment_id'        => $data['equipment_id'] ?? null,
            'entry_type'          => $item->isChemical() ? MonitoringEntry::TYPE_DOSE : MonitoringEntry::TYPE_READING,
            'entry_date'          => $data['entry_date'],
            'value'               => $data['value'],
            'note'                => $data['note'] ?? null,
            'recorded_by_user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Registro agregado.');
    }

    public function updateEntry(Request $request, MonitoringProgram $program, MonitoringEntry $entry): RedirectResponse
    {
        abort_unless($entry->program_id === $program->id, 404);

        $data = $this->validateEntry($request, $program);
        $item = $program->items()->findOrFail($data['item_id']);

        $entry->fill([
            'item_id'      => $item->id,
            'equipment_id' => $data['equipment_id'] ?? null,
            'entry_type'   => $item->isChemical() ? MonitoringEntry::TYPE_DOSE : MonitoringEntry::TYPE_READING,
            'entry_date'   => $data['entry_date'],
            'value'        => $data['value'],
            'note'         => $data['note'] ?? null,
        ]);
        $entry->setRelation('item', $item);
        $entry->save();

        return back()->with('success', 'Registro actualizado.');
    }

    public function destroyEntry(MonitoringProgram $program, MonitoringEntry $entry): RedirectResponse
    {
        abort_unless($entry->program_id === $program->id, 404);
        $entry->delete();

        return back()->with('success', 'Registro eliminado.');
    }

    // ─────────────────────────────────────────────────────────────
    //  Equipos del cliente
    // ─────────────────────────────────────────────────────────────

    public function storeEquipment(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validateEquipment($request);

        MonitoringEquipment::create($data + [
            'customer_id' => $customer->id,
            'created_via' => 'staff',
        ]);

        return back()->with('success', 'Equipo agregado.');
    }

    public function updateEquipment(Request $request, MonitoringEquipment $equipment): RedirectResponse
    {
        $equipment->update($this->validateEquipment($request));

        return back()->with('success', 'Equipo actualizado.');
    }

    public function destroyEquipment(MonitoringEquipment $equipment): RedirectResponse
    {
        // Los registros del equipo se conservan (equipment_id queda en null por la FK).
        $equipment->delete();

        return back()->with('success', 'Equipo eliminado. Sus registros se conservaron sin equipo asignado.');
    }

    // ─────────────────────────────────────────────────────────────
    //  Exportación
    // ─────────────────────────────────────────────────────────────

    public function export(Request $request, MonitoringProgram $program)
    {
        $program->load('customer');
        $equipmentId = $request->integer('equipment_id') ?: null;
        $slug = 'monitoreo-' . $program->id . '-' . now()->format('Y-m-d');

        if ($request->input('type') === 'weeks') {
            [$rows, $headings] = $this->weeksExportRows($program, $equipmentId);
            $slug .= '-semanas';
        } else {
            $entries = $this->entriesQuery($program, $request)
                ->with(['item', 'equipment'])
                ->orderBy('entry_date')->orderBy('id')
                ->get();
            $recorders = $this->recorderNames($entries);

            $rows = $entries->map(fn (MonitoringEntry $e) => [
                'fecha'      => $e->entry_date->format('d/m/Y'),
                'semana'     => $program->weekOf($e->entry_date),
                'equipo'     => $e->equipment?->name,
                'tipo'       => $e->entry_type === MonitoringEntry::TYPE_DOSE ? 'Dosificación' : 'Lectura',
                'elemento'   => $e->item?->name,
                'unidad'     => $e->item?->unit,
                'valor'      => $e->value,
                'limite_min' => $e->item?->limit_min,
                'limite_max' => $e->item?->limit_max,
                'en_rango'   => $e->in_range === null ? '' : ($e->in_range ? 'Sí' : 'No'),
                'nota'       => $e->note,
                'registro'   => $this->recorderLabel($e, $recorders),
            ]);
            $headings = ['Fecha', 'Semana', 'Equipo', 'Tipo', 'Elemento', 'Unidad', 'Valor', 'Límite mín.', 'Límite máx.', 'Dentro de rango', 'Nota', 'Registrado por'];
            $slug .= '-registros';
        }

        $export = new ReportExport($rows, $headings);

        if ($request->input('format') === 'csv') {
            return Excel::download($export, $slug . '.csv', ExcelFormat::CSV);
        }

        return Excel::download($export, $slug . '.xlsx');
    }

    // ─────────────────────────────────────────────────────────────
    //  AJAX
    // ─────────────────────────────────────────────────────────────

    public function searchCustomers(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        $customers = Customer::query()
            ->where('status', 'active')
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->where(function (Builder $sub) use ($q) {
                    $sub->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('company', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$q}%"]);
                });
            })
            ->orderBy('first_name')->orderBy('last_name')
            ->limit(15)
            ->get(['id', 'first_name', 'last_name', 'company'])
            ->map(fn (Customer $c) => [
                'id'      => $c->id,
                'name'    => self::customerName($c),
                'company' => $c->company,
            ]);

        return response()->json($customers);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        $products = Products::query()
            ->where('is_active', true)
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->where(function (Builder $sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'sku', 'stock_unit'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'unit' => $p->stock_unit]);

        return response()->json($products);
    }

    // ─────────────────────────────────────────────────────────────
    //  Internos
    // ─────────────────────────────────────────────────────────────

    public static function customerName(?Customer $c): string
    {
        if (! $c) {
            return '—';
        }
        $name = trim($c->first_name . ' ' . $c->last_name);

        return $name !== '' ? $name : ($c->company ?: ('Cliente #' . $c->id));
    }

    private function formView(MonitoringProgram $program, Collection $items, ?Customer $customer): View
    {
        return view('admin.monitoring.form', [
            'program'       => $program,
            'items'         => $items,
            'customerLabel' => $customer ? self::customerName($customer) . ($customer->company ? ' · ' . $customer->company : '') : '',
            'statuses'      => MonitoringProgram::STATUSES,
            'presets'       => self::PRESETS,
        ]);
    }

    private function validateProgram(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'customer_id'                  => ['required', 'integer', 'exists:customers,id'],
            'name'                         => ['required', 'string', 'max:150'],
            'start_date'                   => ['required', 'date'],
            'weeks'                        => ['required', 'integer', 'min:1', 'max:104'],
            'status'                       => ['required', Rule::in(array_keys(MonitoringProgram::STATUSES))],
            'notes'                        => ['nullable', 'string', 'max:5000'],
            'items'                        => ['nullable', 'array', 'max:60'],
            'items.*.id'                   => ['nullable', 'integer'],
            'items.*.kind'                 => ['required', Rule::in([MonitoringProgramItem::KIND_PARAMETER, MonitoringProgramItem::KIND_CHEMICAL])],
            'items.*.name'                 => ['required', 'string', 'max:120'],
            'items.*.unit'                 => ['nullable', 'string', 'max:20'],
            'items.*.limit_min'            => ['nullable', 'numeric', 'between:-999999999,999999999'],
            'items.*.limit_max'            => ['nullable', 'numeric', 'between:-999999999,999999999'],
            'items.*.product_id'           => ['nullable', 'integer', 'exists:products,id'],
            'items.*.planned_daily_dose'   => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'items.*.start_week'           => ['required', 'integer', 'min:1'],
            'items.*.end_week'             => ['required', 'integer', 'min:1'],
        ], $this->messages(), [
            'customer_id'                => 'cliente',
            'name'                       => 'nombre del programa',
            'start_date'                 => 'fecha de inicio',
            'weeks'                      => 'número de semanas',
            'status'                     => 'estado',
            'notes'                      => 'notas',
            'items.*.kind'               => 'tipo del elemento',
            'items.*.name'               => 'nombre del elemento',
            'items.*.unit'               => 'unidad',
            'items.*.limit_min'          => 'límite mínimo',
            'items.*.limit_max'          => 'límite máximo',
            'items.*.product_id'         => 'producto',
            'items.*.planned_daily_dose' => 'dosis diaria planeada',
            'items.*.start_week'         => 'semana de inicio',
            'items.*.end_week'           => 'semana final',
        ]);

        $validator->after(function ($v) use ($request) {
            $weeks = (int) $request->input('weeks');
            foreach ((array) $request->input('items', []) as $i => $row) {
                $label = 'Elemento ' . (((int) $i) + 1) . ' (' . ($row['name'] ?? 's/n') . ')';
                $start = (int) ($row['start_week'] ?? 0);
                $end = (int) ($row['end_week'] ?? 0);
                if ($weeks > 0 && ($start > $weeks || $end > $weeks)) {
                    $v->errors()->add("items.$i.end_week", "$label: las semanas deben estar entre 1 y $weeks.");
                } elseif ($end < $start) {
                    $v->errors()->add("items.$i.end_week", "$label: la semana final no puede ser menor que la inicial.");
                }
                $min = $row['limit_min'] ?? null;
                $max = $row['limit_max'] ?? null;
                if (($row['kind'] ?? '') === MonitoringProgramItem::KIND_PARAMETER
                    && $min !== null && $min !== '' && $max !== null && $max !== '' && (float) $min > (float) $max) {
                    $v->errors()->add("items.$i.limit_max", "$label: el límite mínimo no puede ser mayor que el máximo.");
                }
            }
        });

        return $validator->validate();
    }

    /** Crea/actualiza/borra los elementos del programa según el arreglo del formulario. */
    private function syncItems(MonitoringProgram $program, array $rows): void
    {
        $existing = $program->items()->get()->keyBy('id');
        $keep = [];

        foreach (array_values($rows) as $index => $row) {
            $isChemical = $row['kind'] === MonitoringProgramItem::KIND_CHEMICAL;
            $payload = [
                'kind'               => $row['kind'],
                'name'               => trim($row['name']),
                'unit'               => ($row['unit'] ?? '') !== '' ? trim($row['unit']) : null,
                'limit_min'          => $isChemical ? null : $this->nullableNumber($row['limit_min'] ?? null),
                'limit_max'          => $isChemical ? null : $this->nullableNumber($row['limit_max'] ?? null),
                'product_id'         => $isChemical && ! empty($row['product_id']) ? (int) $row['product_id'] : null,
                'planned_daily_dose' => $isChemical ? $this->nullableNumber($row['planned_daily_dose'] ?? null) : null,
                'start_week'         => (int) $row['start_week'],
                'end_week'           => (int) $row['end_week'],
                'sort_order'         => $index,
            ];

            $id = ! empty($row['id']) ? (int) $row['id'] : null;
            if ($id && $existing->has($id)) {
                /** @var MonitoringProgramItem $item */
                $item = $existing->get($id);
                $item->update($payload);
                if ($item->wasChanged(['limit_min', 'limit_max', 'kind'])) {
                    $this->recalculateRange($item);
                }
                $keep[] = $id;
            } else {
                $created = $program->items()->create($payload + ['added_by' => 'staff']);
                $keep[] = $created->id;
            }
        }

        $existing->except($keep)->each(fn (MonitoringProgramItem $item) => $item->delete());
    }

    /** Recalcula in_range de las lecturas ya guardadas tras cambiar los límites del elemento. */
    private function recalculateRange(MonitoringProgramItem $item): void
    {
        $readings = fn () => $item->entries()->where('entry_type', MonitoringEntry::TYPE_READING);

        if ($item->limit_min === null && $item->limit_max === null) {
            $readings()->update(['in_range' => null]);

            return;
        }

        $readings()->update(['in_range' => true]);
        $readings()->where(function ($q) use ($item) {
            if ($item->limit_min !== null) {
                $q->orWhere('value', '<', $item->limit_min);
            }
            if ($item->limit_max !== null) {
                $q->orWhere('value', '>', $item->limit_max);
            }
        })->update(['in_range' => false]);
    }

    private function nullableNumber($value): ?float
    {
        return ($value === null || $value === '') ? null : (float) $value;
    }

    private function validateEntry(Request $request, MonitoringProgram $program): array
    {
        $validator = Validator::make($request->all(), [
            'item_id'      => ['required', 'integer'],
            'equipment_id' => ['nullable', 'integer'],
            'entry_date'   => ['required', 'date'],
            'value'        => ['required', 'numeric', 'between:-99999999999,99999999999'],
            'note'         => ['nullable', 'string', 'max:255'],
        ], $this->messages(), [
            'item_id'      => 'elemento',
            'equipment_id' => 'equipo',
            'entry_date'   => 'fecha',
            'value'        => 'valor',
            'note'         => 'nota',
        ]);

        $validator->after(function ($v) use ($request, $program) {
            if (! $program->items()->whereKey($request->input('item_id'))->exists()) {
                $v->errors()->add('item_id', 'El elemento no pertenece a este programa.');
            }
            if ($request->filled('equipment_id')
                && ! MonitoringEquipment::where('customer_id', $program->customer_id)->whereKey($request->input('equipment_id'))->exists()) {
                $v->errors()->add('equipment_id', 'El equipo no pertenece a este cliente.');
            }
            if ($request->filled('entry_date') && strtotime((string) $request->input('entry_date')) !== false) {
                $date = Carbon::parse($request->input('entry_date'))->startOfDay();
                if ($date->lt($program->start_date->copy()->startOfDay()) || $date->gt($program->endDate()->startOfDay())) {
                    $v->errors()->add('entry_date', 'La fecha debe estar dentro del programa ('
                        . $program->start_date->format('d/m/Y') . ' al ' . $program->endDate()->format('d/m/Y') . ').');
                }
            }
        });

        $data = $validator->validate();
        $data['equipment_id'] = ! empty($data['equipment_id']) ? (int) $data['equipment_id'] : null;

        return $data;
    }

    private function validateEquipment(Request $request): array
    {
        $data = Validator::make($request->all(), [
            'name'      => ['required', 'string', 'max:120'],
            'type'      => ['required', Rule::in(array_keys(MonitoringEquipment::TYPES))],
            'location'  => ['nullable', 'string', 'max:150'],
            'notes'     => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ], $this->messages(), [
            'name'     => 'nombre del equipo',
            'type'     => 'tipo de equipo',
            'location' => 'ubicación',
            'notes'    => 'notas',
        ])->validate();

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    /** Filtros de la pestaña Registros / exportación. */
    private function entriesQuery(MonitoringProgram $program, Request $request): Builder
    {
        $query = MonitoringEntry::query()->where('program_id', $program->id);

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->integer('item_id'));
        }
        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->integer('equipment_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('entry_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('entry_date', '<=', $request->input('to'));
        }
        if ($request->boolean('out')) {
            $query->where('in_range', false);
        }

        return $query;
    }

    /** @return array{users: array<int,string>, customers: array<int,string>} */
    private function recorderNames(Collection $entries): array
    {
        $userIds = $entries->pluck('recorded_by_user_id')->filter()->unique();
        $customerIds = $entries->pluck('recorded_by_customer_id')->filter()->unique();

        return [
            'users'     => User::whereIn('id', $userIds)->get(['id', 'first_name', 'last_name'])
                ->mapWithKeys(fn ($u) => [$u->id => trim($u->first_name . ' ' . $u->last_name)])->all(),
            'customers' => Customer::whereIn('id', $customerIds)->get(['id', 'first_name', 'last_name', 'company'])
                ->mapWithKeys(fn ($c) => [$c->id => self::customerName($c)])->all(),
        ];
    }

    private function recorderLabel(MonitoringEntry $e, array $recorders): string
    {
        if ($e->recorded_by_user_id) {
            return 'Personal: ' . ($recorders['users'][$e->recorded_by_user_id] ?? '—');
        }
        if ($e->recorded_by_customer_id) {
            return 'Cliente: ' . ($recorders['customers'][$e->recorded_by_customer_id] ?? '—');
        }

        return '—';
    }

    /** Resumen semanal aplanado (una fila por parámetro/químico y semana). */
    private function weeksExportRows(MonitoringProgram $program, ?int $equipmentId): array
    {
        $rows = collect();

        foreach ($this->service->weekSummaries($program, $equipmentId) as $week) {
            $base = [
                'semana' => $week['n'],
                'inicio' => Carbon::parse($week['start'])->format('d/m/Y'),
                'fin'    => Carbon::parse($week['end'])->format('d/m/Y'),
            ];

            foreach ($week['parameters'] ?? [] as $p) {
                $rows->push($base + [
                    'tipo'       => 'Parámetro',
                    'elemento'   => $p['name'],
                    'unidad'     => $p['unit'] ?? '',
                    'registros'  => $p['count'] ?? 0,
                    'promedio'   => $p['avg'] ?? null,
                    'minimo'     => $p['min'] ?? null,
                    'maximo'     => $p['max'] ?? null,
                    'fuera'      => $p['out_of_range'] ?? 0,
                    'limite_min' => $p['limit_min'] ?? null,
                    'limite_max' => $p['limit_max'] ?? null,
                    'total'      => null,
                    'planeado'   => null,
                ]);
            }
            foreach ($week['chemicals'] ?? [] as $c) {
                $rows->push($base + [
                    'tipo'       => 'Químico',
                    'elemento'   => $c['name'],
                    'unidad'     => $c['unit'] ?? '',
                    'registros'  => $c['days'] ?? 0,
                    'promedio'   => null,
                    'minimo'     => null,
                    'maximo'     => null,
                    'fuera'      => null,
                    'limite_min' => null,
                    'limite_max' => null,
                    'total'      => $c['total'] ?? null,
                    'planeado'   => $c['planned_total'] ?? null,
                ]);
            }
        }

        $headings = ['Semana', 'Inicio', 'Fin', 'Tipo', 'Elemento', 'Unidad', 'Registros / días', 'Promedio', 'Mínimo', 'Máximo', 'Fuera de rango', 'Límite mín.', 'Límite máx.', 'Total dosificado', 'Total planeado'];

        return [$rows, $headings];
    }

    private function messages(): array
    {
        return [
            'required'       => 'El campo :attribute es obligatorio.',
            'string'         => 'El campo :attribute debe ser texto.',
            'numeric'        => 'El campo :attribute debe ser un número.',
            'integer'        => 'El campo :attribute debe ser un número entero.',
            'date'           => 'El campo :attribute debe ser una fecha válida.',
            'boolean'        => 'El campo :attribute no es válido.',
            'array'          => 'El campo :attribute no es válido.',
            'exists'         => 'El :attribute seleccionado no es válido.',
            'in'             => 'El :attribute seleccionado no es válido.',
            'max.string'     => 'El campo :attribute no puede tener más de :max caracteres.',
            'max.numeric'    => 'El campo :attribute no puede ser mayor que :max.',
            'max.integer'    => 'El campo :attribute no puede ser mayor que :max.',
            'max.array'      => 'El campo :attribute no puede tener más de :max elementos.',
            'min.numeric'    => 'El campo :attribute no puede ser menor que :min.',
            'min.integer'    => 'El campo :attribute no puede ser menor que :min.',
            'between.numeric' => 'El campo :attribute debe estar entre :min y :max.',
        ];
    }
}
