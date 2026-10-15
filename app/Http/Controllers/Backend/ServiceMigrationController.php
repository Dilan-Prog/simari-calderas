<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\ServicePage;
use App\Services\ServiceMigration\ServiceIdService;
use App\Support\ServiceProductDetector;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Migración de cotizaciones a servicios web: asignar el "Servicio ID"
 * (quotes.service_page_id) a las cotizaciones ya atendidas por producto-servicio.
 */
class ServiceMigrationController extends Controller
{
    public function __construct(private ServiceIdService $ids) {}

    // ── Pantalla ──────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);

        $quotes = $query->with([
            'customer:id,first_name,last_name,company',
            'servicePage:id,name,slug,page_type,parent_id',
            'items' => fn ($q) => $q->orderBy('sort_order'),
            'items.product:id,name,category_id',
        ])->paginate(25)->withQueryString();

        $categoryIds = ServiceProductDetector::serviceCategoryIds();
        $patterns = ServiceProductDetector::patterns();
        $pages = ServiceIdService::assignablePages()->orderBy('name')->get(['id', 'name']);

        $rows = $quotes->getCollection()->map(function (Quote $quote) use ($categoryIds, $patterns, $pages) {
            $lines = $quote->items->filter(fn ($i) => ServiceProductDetector::isServiceLine($i, $categoryIds, $patterns))->values();
            $suggestion = (!$quote->service_page_id && $lines->isNotEmpty())
                ? $this->ids->suggest($lines->pluck('product_name'), $pages)
                : null;

            return ['quote' => $quote, 'lines' => $lines, 'suggestion' => $suggestion];
        });

        return view('admin.service-migration.index', [
            'quotes'     => $quotes,
            'rows'       => $rows,
            'progress'   => $this->ids->progress(),
            'newMode'    => ServiceIdService::newMode(),
            'statuses'   => $this->statuses(),
            'patterns'   => $patterns,
            'canEdit'    => auth()->user()->hasPermission('quotes', 'edit'),
            'canNewMode' => auth()->user()->hasPermission('service-pages', 'edit'),
            'filters'    => $this->filters($request),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request)->with([
            'customer:id,first_name,last_name,company',
            'servicePage:id,name',
            'items.product:id,name,category_id',
        ]);

        $categoryIds = ServiceProductDetector::serviceCategoryIds();
        $patterns = ServiceProductDetector::patterns();

        return response()->streamDownload(function () use ($query, $categoryIds, $patterns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Folio', 'Cliente', 'Fecha', 'Estado', 'Líneas de servicio detectadas', 'Servicio ID', 'Servicio web']);

            $query->reorder()->chunkById(200, function ($chunk) use ($out, $categoryIds, $patterns) {
                foreach ($chunk as $quote) {
                    $lines = $quote->items
                        ->filter(fn ($i) => ServiceProductDetector::isServiceLine($i, $categoryIds, $patterns))
                        ->pluck('product_name')->implode(' | ');

                    fputcsv($out, [
                        $quote->quote_number,
                        $this->customerName($quote),
                        $quote->created_at?->format('Y-m-d'),
                        Quote::statusLabel($quote->status),
                        $lines,
                        $quote->service_page_id,
                        $quote->servicePage?->name,
                    ]);
                }
            }, 'quotes.id', 'id');

            fclose($out);
        }, 'migracion-cotizaciones-servicios-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ── Búsqueda de páginas de servicio (selector) ───────────────────────────

    public function searchServicePages(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->isAdmin() || collect(['quotes', 'technical-services', 'service-reports', 'service-pages'])
                ->contains(fn ($m) => $user->hasPermission($m))),
            403
        );

        $q = trim((string) $request->get('q', ''));

        $pages = ServiceIdService::assignablePages()
            ->with('parent:id,name')
            ->when($q !== '', function (Builder $b) use ($q) {
                if (ctype_digit(ltrim($q, '#'))) {
                    $b->where(fn ($w) => $w->where('id', (int) ltrim($q, '#'))->orWhere('name', 'like', "%{$q}%"));
                } else {
                    $b->where('name', 'like', "%{$q}%");
                }
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'parent_id']);

        return response()->json($pages->map(fn ($p) => [
            'id'       => $p->id,
            'name'     => $p->name,
            'label'    => '#' . $p->id . ' · ' . $p->name,
            'category' => $p->parent?->name,
        ])->values());
    }

    // ── Asignación (una cotización) ───────────────────────────────────────────

    public function assignQuote(Request $request, Quote $quote)
    {
        $data = $request->validate([
            'service_page_id' => ['nullable', $this->servicePageRule($quote->service_page_id)],
            'sync'            => 'nullable|boolean',
        ]);

        $servicePageId = !empty($data['service_page_id']) ? (int) $data['service_page_id'] : null;
        $result = $this->ids->assign($quote, $servicePageId, (bool) ($data['sync'] ?? false));
        $quote->load('servicePage');

        if ($request->expectsJson()) {
            return response()->json([
                'success'         => true,
                'service_page_id' => $quote->service_page_id,
                'label'           => ServiceIdService::label($quote->servicePage),
                'url'             => $quote->servicePage ? url($quote->servicePage->publicPath()) : null,
                'propagated'      => $result,
                'message'         => $servicePageId ? 'Servicio ID asignado.' : 'Servicio ID quitado.',
                'progress'        => $this->ids->progress(),
            ]);
        }

        return redirect()->route('admin.quotes.show', $quote)
            ->with('success', $servicePageId ? 'Servicio ID asignado correctamente.' : 'Servicio ID quitado.');
    }

    // ── Asignación en lote ────────────────────────────────────────────────────

    public function bulkAssign(Request $request)
    {
        $data = $request->validate([
            'quote_ids'       => 'required|array|min:1|max:500',
            'quote_ids.*'     => 'integer|exists:quotes,id',
            'service_page_id' => ['required', $this->servicePageRule()],
            'sync'            => 'nullable|boolean',
        ]);

        $result = $this->ids->assignMany($data['quote_ids'], (int) $data['service_page_id'], (bool) ($data['sync'] ?? false));

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'result' => $result, 'progress' => $this->ids->progress()]);
        }

        return back()->with('success', "Servicio ID asignado a {$result['quotes']} cotizaciones.");
    }

    // ── Modo nuevo ────────────────────────────────────────────────────────────

    public function toggleNewMode(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['enabled' => 'required|boolean']);
        $this->ids->setNewMode((bool) $data['enabled'], auth()->id());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'enabled' => ServiceIdService::newMode()]);
        }

        return redirect()->route('admin.service-migration.index')
            ->with('success', $data['enabled'] ? 'Modo nuevo activado.' : 'Modo nuevo desactivado.');
    }

    // ── Auxiliares ────────────────────────────────────────────────────────────

    /**
     * Regla: existe, es página de servicio (hoja) y está activa. $keepId permite
     * conservar el valor que el registro ya tiene aunque la página se haya desactivado.
     */
    public static function servicePageRule(?int $keepId = null)
    {
        return Rule::exists('service_pages', 'id')->where(function ($q) use ($keepId) {
            $q->where('page_type', ServicePage::TYPE_SERVICE)
                ->where(function ($w) use ($keepId) {
                    $w->where('is_active', true);
                    if ($keepId) {
                        $w->orWhere('id', $keepId);
                    }
                });
        });
    }

    private function statuses(): array
    {
        $out = [];
        foreach (['draft', 'sent', 'accepted', 'rejected', 'expired'] as $s) {
            $out[$s] = Quote::statusLabel($s);
        }

        return $out;
    }

    private function filters(Request $request): array
    {
        return [
            'search'    => trim((string) $request->get('search', '')),
            'status'    => (string) $request->get('status', ''),
            'customer'  => trim((string) $request->get('customer', '')),
            'date_from' => (string) $request->get('date_from', ''),
            'date_to'   => (string) $request->get('date_to', ''),
            // Por omisión: solo las que aún no tienen Servicio ID.
            'servicio'  => in_array($request->get('servicio'), ['sin', 'con', 'todas'], true) ? $request->get('servicio') : 'sin',
            'lineas'    => $request->boolean('lineas'),
        ];
    }

    private function filteredQuery(Request $request): Builder
    {
        $f = $this->filters($request);
        $query = Quote::query()->latest('quotes.created_at')->latest('quotes.id');

        if ($f['search'] !== '') {
            $s = $f['search'];
            $query->where('quotes.quote_number', 'like', "%{$s}%");
        }
        if ($f['status'] !== '') {
            $query->where('quotes.status', $f['status']);
        }
        if ($f['customer'] !== '') {
            $c = $f['customer'];
            $query->where(function ($q) use ($c) {
                $q->where('quotes.guest_name', 'like', "%{$c}%")
                    ->orWhere('quotes.guest_company', 'like', "%{$c}%")
                    ->orWhereHas('customer', fn ($w) => $w->where('first_name', 'like', "%{$c}%")
                        ->orWhere('last_name', 'like', "%{$c}%")->orWhere('company', 'like', "%{$c}%"));
            });
        }
        if ($f['date_from'] !== '') {
            $query->whereDate('quotes.created_at', '>=', $f['date_from']);
        }
        if ($f['date_to'] !== '') {
            $query->whereDate('quotes.created_at', '<=', $f['date_to']);
        }
        if ($f['servicio'] === 'sin') {
            $query->whereNull('quotes.service_page_id');
        } elseif ($f['servicio'] === 'con') {
            $query->whereNotNull('quotes.service_page_id');
        }
        if ($f['lineas']) {
            $query->whereExists(function ($sub) {
                $sub->selectRaw('1')->from('quote_items')->whereColumn('quote_items.quote_id', 'quotes.id');
                ServiceProductDetector::scopeServiceLines($sub);
            });
        }

        return $query;
    }

    private function customerName(Quote $quote): string
    {
        if ($quote->customer) {
            return $quote->customer->company ?: trim($quote->customer->first_name . ' ' . $quote->customer->last_name);
        }

        return $quote->guest_company ?: (string) $quote->guest_name;
    }
}
