{{--
    Barra de navegación del módulo de Automatizaciones (Workflows, Ejecuciones,
    Credenciales, Plantillas). CSS incluida aquí mismo (no en cada página que
    la incluye) para que siempre viaje con el partial — antes vivía solo en
    workflows/index.blade.php y las otras 3 páginas la mostraban sin estilo.
--}}
<style>
.wf-tabs { display: flex; align-items: center; gap: 24px; border-bottom: 1px solid #E5E7EB; }
.wf-tab { display: inline-flex; align-items: center; height: 44px; padding: 0 2px; font-size: 14px; font-weight: 500; color: var(--text-description-color, #6B7280); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: color .15s, border-color .15s; font-family: var(--font-family, 'Inter', sans-serif); }
.wf-tab:hover { color: #111827; }
.wf-tab-active, .wf-tab-active:hover { color: var(--secondary-color, #ff6213); border-bottom-color: var(--secondary-color, #ff6213); }
</style>
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
