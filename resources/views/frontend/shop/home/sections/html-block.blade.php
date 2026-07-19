@php $config = $section->config ?? []; @endphp
@if (!empty($config['html']))
<section class="home-html-block">
    {!! $config['html'] !!}
</section>
@endif
