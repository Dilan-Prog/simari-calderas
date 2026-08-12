{{--
    Lógica del builder de pasos (modal +Agregar paso, mostrar/ocultar
    sub-campos, CRUD por fetch contra WorkflowStepController y
    reordenamiento drag&drop) vive ahora en el módulo bundleado
    resources/js/admin/workflow-builder.js — mismo comportamiento que el
    <script> inline que tenía este partial, solo movido para poder usar
    SortableJS vía import.
--}}
@push('scripts')
    @vite(['resources/js/admin/workflow-builder.js'])
@endpush
