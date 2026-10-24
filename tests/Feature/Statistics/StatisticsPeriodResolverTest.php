<?php

namespace Tests\Feature\Statistics;

use App\Services\Statistics\StatisticsFormatter;
use App\Services\Statistics\StatisticsPeriodResolver;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Rangos y comparación del selector de fechas de Estadísticas (sin base de datos).
 */
class StatisticsPeriodResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function at(string $datetime): void
    {
        Carbon::setTestNow(Carbon::parse($datetime, 'America/Mexico_City'));
    }

    private function fmt(array $r, string $a, string $b): string
    {
        return $r[$a]->format('Y-m-d H:i:s') . ' / ' . $r[$b]->format('Y-m-d H:i:s');
    }

    public function test_mes_el_31_no_desborda_al_mes_anterior(): void
    {
        // 31 mar − 1 mes = "31 feb" = 3 mar: el "mes anterior" salía marzo.
        $this->at('2026-03-31 10:00:00');
        $r = StatisticsPeriodResolver::resolve('mes', true);

        $this->assertSame('2026-03-01 00:00:00 / 2026-03-31 23:59:59', $this->fmt($r, 'from', 'to'));
        $this->assertSame('2026-02-01 00:00:00 / 2026-02-28 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
    }

    public function test_mes_comparacion_es_el_mismo_avance_de_dias(): void
    {
        $this->at('2026-10-24 15:00:00');
        $r = StatisticsPeriodResolver::resolve('mes', true);

        $this->assertSame('2026-10-01 00:00:00 / 2026-10-31 23:59:59', $this->fmt($r, 'from', 'to'));
        // 24 días transcurridos: 1–24 sep, no el septiembre completo.
        $this->assertSame('2026-09-01 00:00:00 / 2026-09-24 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
        $this->assertSame('mes anterior', $r['compareLabel']);
    }

    public function test_mes_de_31_dias_contra_mes_de_30_se_recorta_al_fin_del_anterior(): void
    {
        $this->at('2026-10-31 23:00:00');
        $r = StatisticsPeriodResolver::resolve('mes', true);

        $this->assertSame('2026-09-01 00:00:00 / 2026-09-30 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
    }

    public function test_primero_de_mes_compara_solo_el_dia_uno(): void
    {
        $this->at('2026-10-01 08:00:00');
        $r = StatisticsPeriodResolver::resolve('mes', true);

        $this->assertSame('2026-09-01 00:00:00 / 2026-09-01 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
    }

    public function test_enero_compara_contra_diciembre_del_anio_anterior(): void
    {
        $this->at('2026-01-15 12:00:00');
        $r = StatisticsPeriodResolver::resolve('mes', true);

        $this->assertSame('2025-12-01 00:00:00 / 2025-12-15 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
    }

    public function test_marzo_bisiesto_contra_febrero_de_29_dias(): void
    {
        $this->at('2028-03-31 12:00:00');
        $r = StatisticsPeriodResolver::resolve('mes', true);

        $this->assertSame('2028-02-01 00:00:00 / 2028-02-29 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
    }

    public function test_trimestre_el_31_de_diciembre_compara_contra_el_tercer_trimestre(): void
    {
        $this->at('2026-12-31 12:00:00');
        $r = StatisticsPeriodResolver::resolve('trimestre', true);

        $this->assertSame('2026-10-01 00:00:00 / 2026-12-31 23:59:59', $this->fmt($r, 'from', 'to'));
        $this->assertSame('2026-07-01 00:00:00 / 2026-09-30 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
    }

    public function test_trimestre_primero_contra_cuarto_del_anio_anterior(): void
    {
        $this->at('2026-02-10 12:00:00');
        $r = StatisticsPeriodResolver::resolve('trimestre', true);

        $this->assertSame('2026-01-01 00:00:00 / 2026-03-31 23:59:59', $this->fmt($r, 'from', 'to'));
        $this->assertSame('2025-10-01 00:00:00 / 2025-11-10 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
    }

    public function test_anio_compara_contra_el_mismo_avance_del_anio_anterior(): void
    {
        $this->at('2026-10-24 12:00:00');
        $r = StatisticsPeriodResolver::resolve('anio', true);

        $this->assertSame('2026-01-01 00:00:00 / 2026-12-31 23:59:59', $this->fmt($r, 'from', 'to'));
        $this->assertSame('2025-01-01 00:00:00 / 2025-10-24 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
    }

    public function test_hoy_compara_contra_ayer_y_semana_contra_semana_anterior(): void
    {
        // Miércoles 14 oct 2026.
        $this->at('2026-10-14 09:30:00');

        $hoy = StatisticsPeriodResolver::resolve('hoy', true);
        $this->assertSame('2026-10-14 00:00:00 / 2026-10-14 23:59:59', $this->fmt($hoy, 'from', 'to'));
        $this->assertSame('2026-10-13 00:00:00 / 2026-10-13 23:59:59', $this->fmt($hoy, 'compareFrom', 'compareTo'));

        $semana = StatisticsPeriodResolver::resolve('semana', true);
        $this->assertSame('2026-10-12 00:00:00 / 2026-10-18 23:59:59', $this->fmt($semana, 'from', 'to'));
        // Lun–Mié (3 días) de la semana anterior.
        $this->assertSame('2026-10-05 00:00:00 / 2026-10-07 23:59:59', $this->fmt($semana, 'compareFrom', 'compareTo'));
    }

    public function test_sin_comparacion_no_hay_rango_anterior(): void
    {
        $this->at('2026-10-24 12:00:00');
        $r = StatisticsPeriodResolver::resolve('mes', false);

        $this->assertFalse($r['compare']);
        $this->assertNull($r['compareFrom']);
        $this->assertNull($r['compareTo']);
        $this->assertNull(StatisticsFormatter::previousRange($r));
    }

    public function test_periodo_invalido_cae_al_mes_actual(): void
    {
        $this->at('2026-10-24 12:00:00');

        foreach ([null, '', 'foo', 'custom', 'MES', "mes'--"] as $slug) {
            $r = StatisticsPeriodResolver::resolve($slug);
            $this->assertSame('mes', $r['period'], var_export($slug, true));
        }
    }

    public function test_rango_personalizado_un_solo_dia(): void
    {
        $this->at('2026-10-24 12:00:00');
        $r = StatisticsPeriodResolver::resolve('mes', true, '2026-08-15', '2026-08-15');

        $this->assertSame('custom', $r['period']);
        $this->assertSame('2026-08-15 00:00:00 / 2026-08-15 23:59:59', $this->fmt($r, 'from', 'to'));
        $this->assertSame('2026-08-14 00:00:00 / 2026-08-14 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
    }

    public function test_rango_personalizado_que_cruza_de_anio(): void
    {
        $this->at('2026-10-24 12:00:00');
        $r = StatisticsPeriodResolver::resolve(null, true, '2025-12-20', '2026-01-09');

        // 21 días → los 21 días inmediatamente anteriores.
        $this->assertSame('2025-11-29 00:00:00 / 2025-12-19 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
        $this->assertSame('periodo anterior equivalente', $r['compareLabel']);
    }

    public function test_rango_personalizado_de_mes_completo_compara_contra_mes_calendario_anterior(): void
    {
        $this->at('2026-10-24 12:00:00');

        // Marzo (31 d) → febrero (28 d) completo, no los 31 días previos.
        $r = StatisticsPeriodResolver::resolve(null, true, '2026-03-01', '2026-03-31');
        $this->assertSame('2026-02-01 00:00:00 / 2026-02-28 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
        $this->assertSame('mes anterior', $r['compareLabel']);

        // Febrero (28 d) → enero completo.
        $r = StatisticsPeriodResolver::resolve(null, true, '2026-02-01', '2026-02-28');
        $this->assertSame('2026-01-01 00:00:00 / 2026-01-31 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));

        // Mes de 30 días → mes de 31.
        $r = StatisticsPeriodResolver::resolve(null, true, '2026-04-01', '2026-04-30');
        $this->assertSame('2026-03-01 00:00:00 / 2026-03-31 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));

        // Enero → diciembre del año anterior.
        $r = StatisticsPeriodResolver::resolve(null, true, '2026-01-01', '2026-01-31');
        $this->assertSame('2025-12-01 00:00:00 / 2025-12-31 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
    }

    public function test_rango_personalizado_mes_en_curso_compara_mismo_avance_del_mes_anterior(): void
    {
        $this->at('2026-10-24 12:00:00');

        // Es lo que produce el rápido "Este mes" del selector (1 → hoy).
        $r = StatisticsPeriodResolver::resolve(null, true, '2026-10-01', '2026-10-24');
        $this->assertSame('2026-09-01 00:00:00 / 2026-09-24 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));

        // 1–31 mar contra feb: el 31 no existe en febrero, se recorta.
        $r = StatisticsPeriodResolver::resolve(null, true, '2026-03-01', '2026-03-30');
        $this->assertSame('2026-02-01 00:00:00 / 2026-02-28 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
    }

    public function test_rango_personalizado_de_varios_meses_completos_compara_los_mismos_meses_antes(): void
    {
        $this->at('2026-10-24 12:00:00');

        // Trimestre (ene–mar) → oct–dic del año anterior.
        $r = StatisticsPeriodResolver::resolve(null, true, '2026-01-01', '2026-03-31');
        $this->assertSame('2025-10-01 00:00:00 / 2025-12-31 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));

        // Año completo → año anterior (aunque 2024 sea bisiesto).
        $r = StatisticsPeriodResolver::resolve(null, true, '2025-01-01', '2025-12-31');
        $this->assertSame('2024-01-01 00:00:00 / 2024-12-31 23:59:59', $this->fmt($r, 'compareFrom', 'compareTo'));
        $this->assertSame('año anterior', $r['compareLabel']);
    }

    public function test_rango_personalizado_invertido_se_ordena(): void
    {
        $this->at('2026-10-24 12:00:00');
        $r = StatisticsPeriodResolver::resolve(null, false, '2026-09-30', '2026-09-01');

        $this->assertSame('2026-09-01 00:00:00 / 2026-09-30 23:59:59', $this->fmt($r, 'from', 'to'));
    }

    public function test_fechas_invalidas_se_ignoran_y_usan_el_preset(): void
    {
        $this->at('2026-10-24 12:00:00');

        $casos = [
            ['2026-02-30', '2026-03-05'],  // día inexistente
            ['2026-13-01', '2026-03-05'],  // mes inexistente
            ['26-01-01', '2026-03-05'],    // formato
            ['2026-01-01', ''],            // falta una
            ['', ''],
            ['abc', 'def'],
            ['0001-01-01', '2026-01-01'],  // año fuera de rango
            ['2026-01-01', '9999-12-31'],  // año fuera de rango
            ['2026-01-01 00:00:00', '2026-01-31'],
        ];

        foreach ($casos as [$from, $to]) {
            $r = StatisticsPeriodResolver::resolve('trimestre', true, $from, $to);
            $this->assertSame('trimestre', $r['period'], "$from / $to");
            $this->assertNotNull($r['compareFrom']);
        }
    }

    public function test_query_reproduce_el_filtro_para_conservarlo_entre_secciones(): void
    {
        $this->at('2026-10-24 12:00:00');

        $this->assertSame(['period' => 'trimestre', 'compare' => 1], StatisticsPeriodResolver::resolve('trimestre', true)['query']);
        $this->assertSame(['period' => 'mes'], StatisticsPeriodResolver::resolve('mes', false)['query']);
        $this->assertSame(
            ['date_from' => '2026-09-01', 'date_to' => '2026-09-30', 'compare' => 1],
            StatisticsPeriodResolver::resolve('mes', true, '2026-09-30', '2026-09-01')['query']
        );
    }

    public function test_meses_de_tendencia_no_se_repiten_ni_saltan_a_fin_de_mes(): void
    {
        foreach (['2026-03-31', '2026-05-31', '2026-08-31', '2026-10-31', '2026-01-30'] as $day) {
            $this->at($day . ' 12:00:00');
            $months = StatisticsPeriodResolver::trailingMonths(6);

            $this->assertCount(6, $months);
            $keys = array_map(fn ($m) => $m['from']->format('Y-m'), $months);
            $this->assertSame(6, count(array_unique($keys)), "$day: " . implode(',', $keys));
            $this->assertSame(Carbon::now()->format('Y-m'), end($keys), $day);
            $this->assertSame(Carbon::now()->copy()->subMonthsNoOverflow(5)->format('Y-m'), $keys[0], $day);
        }
    }

    public function test_variacion_porcentual_y_puntos(): void
    {
        $period = ['compare' => true, 'compareLabel' => 'mes anterior', 'compareRangeLabel' => '01 sep – 24 sep 2026'];
        $kpi = ['label' => 'X', 'value' => '1', 'trend' => 'estatico', 'trendUp' => true, 'hint' => 'estatico'];
        $fmt = fn (float $v) => (string) $v;

        $up = StatisticsFormatter::withComparison($kpi, $period, 150, 100, $fmt);
        $this->assertSame('+50.0%', $up['trend']);
        $this->assertTrue($up['trendUp']);
        $this->assertStringContainsString('mes anterior', $up['hint']);
        $this->assertStringContainsString('vs 100', $up['hint']);

        $down = StatisticsFormatter::withComparison($kpi, $period, 75, 100, $fmt);
        $this->assertSame('-25.0%', $down['trend']);
        $this->assertFalse($down['trendUp']);

        // Menos es mejor (lead time, rebotes): bajar es favorable.
        $better = StatisticsFormatter::withComparison($kpi, $period, 75, 100, $fmt, true);
        $this->assertTrue($better['trendUp']);
        $worse = StatisticsFormatter::withComparison($kpi, $period, 125, 100, $fmt, true);
        $this->assertFalse($worse['trendUp']);
        $same = StatisticsFormatter::withComparison($kpi, $period, 100, 100, $fmt, true);
        $this->assertTrue($same['trendUp']);

        // Sin base previa no se inventa un porcentaje.
        $nuevo = StatisticsFormatter::withComparison($kpi, $period, 10, 0, $fmt);
        $this->assertSame('Nuevo', $nuevo['trend']);
        $cero = StatisticsFormatter::withComparison($kpi, $period, 0, 0, $fmt);
        $this->assertSame('0%', $cero['trend']);
        $igual = StatisticsFormatter::withComparison($kpi, $period, 80, 80, $fmt);
        $this->assertSame('0%', $igual['trend']);
        $this->assertTrue($igual['trendUp']);

        // Métricas que ya son %: diferencia en puntos porcentuales.
        $pp = StatisticsFormatter::withComparison($kpi, $period, 46.2, 40.0, $fmt, false, true);
        $this->assertSame('+6.2 pp', $pp['trend']);

        // Sin comparación activa o sin valor previo: el KPI queda intacto.
        $this->assertSame($kpi, StatisticsFormatter::withComparison($kpi, ['compare' => false], 1, 1, $fmt));
        $this->assertSame($kpi, StatisticsFormatter::withComparison($kpi, $period, 1, null, $fmt));
    }
}
