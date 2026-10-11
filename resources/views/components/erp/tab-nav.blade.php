@props(['tabs' => [], 'active' => null])
{{--
    Pestañas de Object Page. tabs: ['general' => 'General', 'fases' => 'Fases', ...]
    Los paneles son <div class="erp-tabpanel" data-panel="general">…</div> dentro del mismo contenedor
    con atributo data-erp-tabs. resources/js/admin/erp-ui.js alterna .is-active y recuerda la pestaña en #hash.
--}}
@php $active = $active ?? array_key_first($tabs); @endphp
<div class="erp-tabnav" role="tablist">
    @foreach ($tabs as $key => $label)
        <button type="button" role="tab" class="erp-tab {{ $key === $active ? 'is-active' : '' }}" data-tab="{{ $key }}">{{ $label }}</button>
    @endforeach
</div>
