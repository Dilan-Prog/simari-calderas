{{-- Barra de navegación del módulo de Automatizaciones (Workflows, Ejecuciones, Credenciales, Plantillas) --}}
<div class="wf-tabs">
    <a href="{{ route('admin.workflows.index') }}"
       class="wf-tab{{ request()->routeIs('admin.workflows.index') ? ' wf-tab-active' : '' }}">
        Workflows
    </a>
    <a href="{{ route('admin.workflow-executions.index') }}"
       class="wf-tab{{ request()->routeIs('admin.workflow-executions.*') ? ' wf-tab-active' : '' }}">
        Ejecuciones
    </a>
    <a href="{{ route('admin.credentials.index') }}"
       class="wf-tab{{ request()->routeIs('admin.credentials.*') ? ' wf-tab-active' : '' }}">
        Credenciales
    </a>
    <a href="{{ route('admin.workflows.templates') }}"
       class="wf-tab{{ request()->routeIs('admin.workflows.templates') ? ' wf-tab-active' : '' }}">
        Plantillas
    </a>
</div>
