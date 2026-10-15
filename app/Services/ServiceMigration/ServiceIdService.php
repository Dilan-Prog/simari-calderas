<?php

namespace App\Services\ServiceMigration;

use App\Models\Quote;
use App\Models\ServicePage;
use App\Models\ServiceReport;
use App\Models\Setting;
use App\Models\TechnicalService;
use App\Support\ServiceProductDetector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "Servicio ID": vínculo de una cotización (y de sus servicios técnicos y
 * reportes) con una página de servicio web (ServicePage tipo "service").
 * Concentra asignación, propagación, sugerencias, progreso de la migración
 * y el interruptor del "modo nuevo".
 */
class ServiceIdService
{
    public const SETTING_NEW_MODE = 'services.new_mode';

    // ── Modo nuevo ────────────────────────────────────────────────────────────

    public static function newMode(): bool
    {
        return (bool) Setting::get(self::SETTING_NEW_MODE, false);
    }

    public function setNewMode(bool $on, ?int $userId = null): void
    {
        Setting::updateOrCreate(['key' => self::SETTING_NEW_MODE], ['type' => 'boolean', 'group_name' => 'services']);
        Setting::set(self::SETTING_NEW_MODE, $on ? '1' : '0', $userId);
    }

    /**
     * Reglas del modo nuevo para guardar una cotización desde el asistente.
     * Con el modo apagado no devuelve errores.
     *
     * @param  array<int,array<string,mixed>>  $items  líneas (items_json)
     * @return array<int,string>  mensajes de error
     */
    public function newModeItemErrors(array $items, ?int $servicePageId, ?Quote $existing = null): array
    {
        if (!self::newMode()) {
            return [];
        }

        $errors = [];
        $categoryIds = ServiceProductDetector::serviceCategoryIds();
        $patterns = ServiceProductDetector::patterns();
        // Las líneas que la cotización ya traía no se bloquean al editarla.
        $keep = $existing ? $existing->items()->pluck('product_id')->filter()->map(fn ($v) => (int) $v)->all() : [];
        $hasServiceLines = false;

        foreach ($items as $item) {
            if (!empty($item['service_page_id'])) {
                $hasServiceLines = true;
                continue;
            }
            $productId = !empty($item['product_id']) ? (int) $item['product_id'] : null;
            if ($productId && in_array($productId, $keep, true)) {
                continue;
            }
            $line = (object) [
                'product_id'      => $productId,
                'service_page_id' => null,
                'product_name'    => (string) ($item['product_name'] ?? ''),
            ];
            if (ServiceProductDetector::isServiceLine($line, $categoryIds, $patterns)) {
                $errors[] = '«' . $line->product_name . '» es un producto-servicio: elige el servicio desde las páginas de servicio.';
            }
        }

        if ($hasServiceLines && !$servicePageId) {
            $errors[] = 'La cotización tiene líneas de servicio: asigna el Servicio ID (servicio web).';
        }

        return $errors;
    }

    // ── Páginas de servicio elegibles ─────────────────────────────────────────

    /** Páginas de servicio (hoja) activas que se pueden asignar como Servicio ID. */
    public static function assignablePages()
    {
        return ServicePage::query()
            ->where('page_type', ServicePage::TYPE_SERVICE)
            ->where('is_active', true);
    }

    /** Etiqueta "#12 · Nombre" */
    public static function label(?ServicePage $page): string
    {
        return $page ? '#' . $page->id . ' · ' . $page->name : '';
    }

    // ── Asignación ────────────────────────────────────────────────────────────

    /**
     * Asigna (o limpia con null) el Servicio ID de una cotización. Con $sync,
     * lo propaga a sus servicios técnicos y reportes que aún no tengan uno.
     *
     * @return array{services:int, reports:int}
     */
    public function assign(Quote $quote, ?int $servicePageId, bool $sync = false): array
    {
        $quote->service_page_id = $servicePageId;
        $quote->save();

        return $sync && $servicePageId ? $this->propagate($quote) : ['services' => 0, 'reports' => 0];
    }

    /**
     * Asignación en lote.
     *
     * @param  array<int>  $quoteIds
     * @return array{quotes:int, services:int, reports:int}
     */
    public function assignMany(array $quoteIds, ?int $servicePageId, bool $sync = false): array
    {
        $totals = ['quotes' => 0, 'services' => 0, 'reports' => 0];

        DB::transaction(function () use ($quoteIds, $servicePageId, $sync, &$totals) {
            Quote::whereIn('id', $quoteIds)->get()->each(function (Quote $quote) use ($servicePageId, $sync, &$totals) {
                $r = $this->assign($quote, $servicePageId, $sync);
                $totals['quotes']++;
                $totals['services'] += $r['services'];
                $totals['reports'] += $r['reports'];
            });
        });

        return $totals;
    }

    /**
     * Copia el Servicio ID de la cotización a sus servicios técnicos y a los
     * reportes de esos servicios que todavía no tengan uno (nunca pisa).
     *
     * @return array{services:int, reports:int}
     */
    public function propagate(Quote $quote): array
    {
        if (!$quote->service_page_id) {
            return ['services' => 0, 'reports' => 0];
        }

        $services = TechnicalService::where('from_quote_id', $quote->id)->get(['id', 'service_page_id']);

        $servicesUpdated = TechnicalService::where('from_quote_id', $quote->id)
            ->whereNull('service_page_id')
            ->update(['service_page_id' => $quote->service_page_id]);

        $reportsUpdated = 0;
        if ($services->isNotEmpty()) {
            // Cada reporte hereda el de su servicio técnico (que ya puede traer uno propio).
            $byService = TechnicalService::whereIn('id', $services->pluck('id'))->pluck('service_page_id', 'id');
            ServiceReport::whereIn('service_id', $services->pluck('id'))
                ->whereNull('service_page_id')
                ->get(['id', 'service_id'])
                ->each(function (ServiceReport $report) use ($byService, &$reportsUpdated) {
                    $pageId = $byService[$report->service_id] ?? null;
                    if ($pageId) {
                        ServiceReport::where('id', $report->id)->update(['service_page_id' => $pageId]);
                        $reportsUpdated++;
                    }
                });
        }

        return ['services' => $servicesUpdated, 'reports' => $reportsUpdated];
    }

    // ── Sugerencias ───────────────────────────────────────────────────────────

    private const STOPWORDS = ['de', 'del', 'la', 'el', 'los', 'las', 'y', 'e', 'en', 'a', 'para', 'por', 'con', 'un', 'una', 'al', 'o'];

    /** @return array<int,string> palabras significativas, normalizadas. */
    public static function tokens(?string $text): array
    {
        $words = preg_split('/[^a-z0-9]+/', ServiceProductDetector::normalize($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            $words,
            fn ($w) => strlen($w) > 1 && !in_array($w, self::STOPWORDS, true)
        )));
    }

    /** Similitud de palabras 0..1 (Jaccard). */
    public static function similarity(?string $a, ?string $b): float
    {
        $ta = self::tokens($a);
        $tb = self::tokens($b);
        if (!$ta || !$tb) {
            return 0.0;
        }
        $inter = count(array_intersect($ta, $tb));
        $union = count(array_unique(array_merge($ta, $tb)));

        return $union ? $inter / $union : 0.0;
    }

    /**
     * Sugiere UNA página de servicio para los nombres de línea detectados,
     * solo si la coincidencia es clara (>= 0.5 y sin empate cercano) y todas
     * las líneas apuntan a la misma página. Nunca asigna.
     *
     * @param  iterable<string>  $lineNames
     * @param  Collection<int,ServicePage>  $pages
     */
    public function suggest(iterable $lineNames, Collection $pages): ?ServicePage
    {
        $chosen = [];

        foreach ($lineNames as $name) {
            $scores = $pages->map(fn ($p) => ['page' => $p, 'score' => self::similarity($name, $p->name)])
                ->sortByDesc('score')->values();

            $top = $scores->first();
            if (!$top || $top['score'] < 0.5) {
                continue;
            }
            $second = $scores->get(1);
            if ($second && ($top['score'] - $second['score']) < 0.15) {
                return null; // ambiguo
            }
            $chosen[$top['page']->id] = $top['page'];
        }

        return count($chosen) === 1 ? array_values($chosen)[0] : null;
    }

    // ── Progreso ──────────────────────────────────────────────────────────────

    /**
     * Universo de la migración: cotizaciones con Servicio ID o con líneas de
     * producto-servicio. Migradas = las que ya tienen Servicio ID.
     *
     * @return array{migrated:int, total:int, percent:int}
     */
    public function progress(): array
    {
        $base = Quote::query()->where(function ($q) {
            $q->whereNotNull('quotes.service_page_id')
                ->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))->from('quote_items')
                        ->whereColumn('quote_items.quote_id', 'quotes.id');
                    ServiceProductDetector::scopeServiceLines($sub);
                });
        });

        $total = (clone $base)->count();
        $migrated = (clone $base)->whereNotNull('quotes.service_page_id')->count();

        return [
            'migrated' => $migrated,
            'total'    => $total,
            'percent'  => $total > 0 ? (int) floor($migrated * 100 / $total) : 100,
        ];
    }
}
