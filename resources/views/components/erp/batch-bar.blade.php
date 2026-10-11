@props(['noun' => 'documentos'])
{{--
    Barra de acciones en lote (oscura). Se muestra sola cuando hay filas marcadas:
    cada fila lleva <input type="checkbox" class="erp-row-check" value="id"> y la cabecera
    <input type="checkbox" data-erp-select-all>. Los botones van en el slot y pueden leer
    window.erpSelectedIds() (devuelve los ids marcados). Lo maneja resources/js/admin/erp-ui.js.
--}}
<div class="erp-batch" data-erp-batch data-noun="{{ $noun }}">
    <span><b data-erp-batch-count>0</b> {{ $noun }} seleccionados</span>
    {{ $slot }}
</div>
