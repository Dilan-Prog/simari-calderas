{{--
    Gráficas del Reporte de Análisis Químico dentro de Monitoreo (portal). Usa el partial compartido
    partials.water-analysis-charts (el mismo que la ficha admin del cliente).
    Variables: $analysis (App\Services\WaterAnalysisInsights::forCustomer).
--}}
@php
    $periodUrls = [];
    foreach (\App\Services\WaterAnalysisInsights::PERIODS as $p) {
        $periodUrls[$p] = route('customer.monitoring.index', ['dias' => $p]);
    }
    $emptyAction = \App\Support\PortalContext::can('water-analysis', 'create')
        ? ['url' => route('customer.water-analysis.index'), 'label' => 'Registrar el análisis de hoy']
        : null;
@endphp
@include('partials.water-analysis-charts', [
    'analysis'    => $analysis,
    'periodUrls'  => $periodUrls,
    'idp'         => 'ma',
    'heading'     => 'Análisis químico del agua',
    'subheading'  => 'Resumen de tus registros diarios de dureza, pH, alcalinidad, conductividad y STD.',
    'emptyAction' => $emptyAction,
])
