<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\WaterAnalysisParameter;
use App\Models\WaterAnalysisReport;
use App\Support\PortalContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Portal del cliente > Reporte de análisis químico del agua. Un registro por día
 * (dureza, pH, alcalinidad, conductividad, STD). Todo pertenece a la CUENTA
 * (auth('customer')); un registro ajeno responde 404.
 */
class WaterAnalysisController extends Controller
{
    public const PER_PAGE = 30;
    public const CHART_DAYS = 30;

    private function customerId(): int
    {
        return (int) auth('customer')->id();
    }

    /** Un registro ajeno responde 404 (no revela que existe). */
    private function owned(int|string $id): WaterAnalysisReport
    {
        return WaterAnalysisReport::where('customer_id', $this->customerId())->findOrFail($id);
    }

    /** @return \Illuminate\Support\Collection<int, WaterAnalysisParameter> */
    private function parameters()
    {
        return WaterAnalysisParameter::active()->ordered()->get();
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ], [
            'from.date'           => 'La fecha inicial no es válida.',
            'to.date'             => 'La fecha final no es válida.',
            'to.after_or_equal'   => 'La fecha final no puede ser anterior a la inicial.',
        ]);

        $parameters = $this->parameters();
        $customerId = $this->customerId();

        $query = WaterAnalysisReport::where('customer_id', $customerId)->with('results');
        if (! empty($filters['from'])) {
            $query->whereDate('report_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('report_date', '<=', $filters['to']);
        }
        $reports = $query->orderByDesc('report_date')->paginate(self::PER_PAGE)->withQueryString();

        // Filas del historial ya con el estado de cada valor contra los límites vigentes.
        $rows = $reports->getCollection()->map(function (WaterAnalysisReport $r) use ($parameters) {
            $byParam = $r->results->keyBy('parameter_id');
            $cells = [];
            foreach ($parameters as $p) {
                $v = $byParam->get($p->id)?->value;
                $cells[$p->id] = [
                    'value'  => $v,
                    'text'   => $p->formatValue($v),
                    'status' => $p->statusFor($v),
                ];
            }

            return ['report' => $r, 'cells' => $cells];
        });

        $today = now()->toDateString();
        $oldDate = old('report_date');

        return view('customers.water-analysis.index', [
            'parameters'  => $parameters,
            'reports'     => $reports,
            'rows'        => $rows,
            'filters'     => $filters,
            'today'       => $today,
            'initialDate' => $oldDate ?: $today,
            'initialDay'  => $this->dayPayload($oldDate ?: $today, $parameters),
            'chart'       => $this->chartData($parameters),
            'canCreate'   => PortalContext::can('water-analysis', 'create'),
            'canEdit'     => PortalContext::can('water-analysis', 'edit'),
            'canDelete'   => PortalContext::can('water-analysis', 'delete'),
        ]);
    }

    /** JSON con el registro de un día (para precargar el formulario al cambiar la fecha). */
    public function day(string $date): JsonResponse
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! $this->validDate($date)) {
            return response()->json(['message' => 'Fecha no válida.'], 422);
        }

        return response()->json($this->dayPayload($date, $this->parameters()));
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $values] = $this->validated($request);

        $existing = WaterAnalysisReport::where('customer_id', $this->customerId())
            ->whereDate('report_date', $data['report_date'])
            ->first();

        // Guardar sobre una fecha ya registrada actualiza ese día: requiere permiso de edición.
        if ($existing && ! PortalContext::can('water-analysis', 'edit')) {
            return back()->withInput()->withErrors([
                'report_date' => 'Ya existe un registro de esa fecha y no tienes permiso para modificarlo.',
            ]);
        }

        $report = $this->persist($existing, $data, $values);

        return redirect()->route('customer.water-analysis.index')->with(
            'success',
            $existing ? 'Registro del ' . $this->fmt($report) . ' actualizado.' : 'Registro del ' . $this->fmt($report) . ' guardado.'
        );
    }

    public function update(Request $request, string $report): RedirectResponse
    {
        $model = $this->owned($report);
        // La fecha identifica el registro: se conserva la del registro editado.
        $request->merge(['report_date' => $model->report_date->toDateString()]);
        [$data, $values] = $this->validated($request);

        $this->persist($model, $data, $values);

        return redirect()->route('customer.water-analysis.index')
            ->with('success', 'Registro del ' . $this->fmt($model) . ' actualizado.');
    }

    public function destroy(string $report): RedirectResponse
    {
        $model = $this->owned($report);
        $label = $this->fmt($model);
        $model->delete(); // los resultados caen por cascada

        return redirect()->route('customer.water-analysis.index')
            ->with('success', 'Registro del ' . $label . ' eliminado.');
    }

    // ── Internos ────────────────────────────────────────────────────────────

    private function fmt(WaterAnalysisReport $r): string
    {
        return $r->report_date->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    }

    private function validDate(string $date): bool
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') === $date;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Valida fecha, observaciones y los resultados (uno opcional por parámetro activo, al menos uno).
     *
     * @return array{0: array, 1: array<int, float>} [datos, valores por id de parámetro]
     */
    private function validated(Request $request): array
    {
        $parameters = $this->parameters();

        // Se acepta la coma como separador decimal.
        $raw = (array) $request->input('values', []);
        foreach ($raw as $k => $v) {
            if (is_string($v)) {
                $raw[$k] = str_replace(',', '.', trim($v));
            }
        }
        $request->merge(['values' => $raw]);

        $rules = [
            'report_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'notes'       => ['nullable', 'string', 'max:1000'],
            'values'      => ['array'],
        ];
        $messages = [
            'report_date.required'        => 'Indica la fecha del registro.',
            'report_date.date_format'     => 'La fecha del registro no es válida.',
            'report_date.before_or_equal' => 'La fecha del registro no puede ser futura.',
            'notes.max'                   => 'Las observaciones no pueden superar 1000 caracteres.',
        ];

        foreach ($parameters as $p) {
            $max = $p->code === 'ph' ? 14 : 9999999;
            $rules["values.{$p->id}"] = ['nullable', 'numeric', 'min:0', "max:{$max}"];
            $messages["values.{$p->id}.numeric"] = "{$p->name}: escribe un número válido.";
            $messages["values.{$p->id}.min"] = "{$p->name}: el valor no puede ser negativo.";
            $messages["values.{$p->id}.max"] = $p->code === 'ph'
                ? 'pH: el valor debe estar entre 0 y 14.'
                : "{$p->name}: el valor es demasiado grande.";
        }

        $validator = validator($request->all(), $rules, $messages);
        $validator->after(function ($v) use ($request, $parameters) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $any = $parameters->contains(fn ($p) => $this->filled($request->input("values.{$p->id}")));
            if (! $any) {
                $v->errors()->add('values', 'Captura al menos un resultado.');
            }
        });
        $data = $validator->validate();

        $values = [];
        foreach ($parameters as $p) {
            $v = $request->input("values.{$p->id}");
            if ($this->filled($v)) {
                $values[$p->id] = round((float) $v, min(3, (int) $p->decimals));
            }
        }

        return [$data, $values];
    }

    private function filled(mixed $v): bool
    {
        return $v !== null && $v !== '';
    }

    /** Crea o actualiza el registro del día y sincroniza sus resultados (vacío = se quita). */
    private function persist(?WaterAnalysisReport $report, array $data, array $values): WaterAnalysisReport
    {
        return DB::transaction(function () use ($report, $data, $values) {
            $notes = filled($data['notes'] ?? null) ? trim($data['notes']) : null;

            if ($report) {
                $report->update(['notes' => $notes]);
            } else {
                $report = WaterAnalysisReport::create([
                    'customer_id'           => $this->customerId(),
                    'report_date'           => $data['report_date'],
                    'notes'                 => $notes,
                    'recorded_by_member_id' => PortalContext::memberId(),
                ]);
            }

            foreach ($values as $parameterId => $value) {
                $report->results()->updateOrCreate(['parameter_id' => $parameterId], ['value' => $value]);
            }
            // Un parámetro que se dejó vacío al corregir el día se elimina del registro.
            // Solo entre los parámetros activos: los desactivados conservan lo que ya tenían.
            $report->results()
                ->whereIn('parameter_id', $this->parameters()->pluck('id'))
                ->whereNotIn('parameter_id', array_keys($values))
                ->delete();

            return $report->fresh();
        });
    }

    /** Datos de un día para el formulario (existe, id, valores, observaciones y URL de actualización). */
    private function dayPayload(string $date, $parameters): array
    {
        $report = $this->validDate($date)
            ? WaterAnalysisReport::where('customer_id', $this->customerId())->whereDate('report_date', $date)->with('results')->first()
            : null;

        $values = [];
        if ($report) {
            foreach ($report->results as $r) {
                $values[$r->parameter_id] = $r->value;
            }
        }

        return [
            'date'       => $date,
            'exists'     => (bool) $report,
            'id'         => $report?->id,
            'notes'      => $report?->notes,
            'values'     => (object) $values,
            'update_url' => $report ? route('customer.water-analysis.update', $report->id) : null,
        ];
    }

    /** Últimos 30 días (todos los parámetros) para la gráfica de tendencia; los días sin registro quedan en null. */
    private function chartData($parameters): array
    {
        $end = now()->startOfDay();
        $start = $end->copy()->subDays(self::CHART_DAYS - 1);

        $reports = WaterAnalysisReport::where('customer_id', $this->customerId())
            ->whereDate('report_date', '>=', $start->toDateString())
            ->whereDate('report_date', '<=', $end->toDateString())
            ->with('results')
            ->get()
            ->keyBy(fn ($r) => $r->report_date->toDateString());

        $labels = [];
        $dates = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->toDateString();
            $labels[] = $d->locale('es')->isoFormat('D MMM');
        }

        $series = [];
        foreach ($parameters as $p) {
            $points = [];
            foreach ($dates as $date) {
                $res = $reports->get($date)?->results->firstWhere('parameter_id', $p->id);
                $points[] = $res ? $res->value : null;
            }
            $series[] = [
                'id'       => $p->id,
                'code'     => $p->code,
                'name'     => $p->name,
                'unit'     => $p->unit,
                'min'      => $p->min_value,
                'max'      => $p->max_value,
                'decimals' => $p->decimals,
                'values'   => $points,
            ];
        }

        return ['labels' => $labels, 'dates' => $dates, 'series' => $series, 'has_data' => $reports->isNotEmpty()];
    }
}
