{{--
    Estilos y script de la tabla de datos estilo hoja de cálculo (misma vista que Ventas > Listado de Productos).
    Uso: <x-erp.datagrid-assets /> en la vista, y en la tabla:
         <table class="erp-table" data-dg="clave-unica" data-dg-noun="cuentas por cobrar"> … ver erp-datagrid.js.
--}}
@push('styles')
    @vite(['resources/css/admin/pages/erp-datagrid.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-datagrid.js'])
@endpush
