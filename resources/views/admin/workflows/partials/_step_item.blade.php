{{--
    Tarjeta de un paso del workflow, renderizada recursivamente para mostrar
    a sus hijos (branch_condition) indentados debajo. $depth controla el
    sangrado visual; $workflow se reenvía a las rutas de acciones.
--}}
@php
    $stepTypeLabels = ['action' => 'Acción', 'condition' => 'Condición', 'wait' => 'Espera'];
    $stepTypeLabel = $stepTypeLabels[$step->step_type] ?? $step->step_type;
@endphp
<div class="wf-step-card" style="margin-left: {{ $depth * 28 }}px;" data-step-id="{{ $step->id }}" data-step-type="{{ $step->step_type }}">
    <div class="wf-step-card-main">
        <span class="wf-step-badge wf-step-badge-{{ $step->step_type }}">{{ $stepTypeLabel }}</span>

        <div class="wf-step-summary">
            @if($step->step_type === 'action')
                <strong>{{ $step->action_type ?? 'Sin action_type' }}</strong>
                @if($step->action_config)
                    <code class="wf-step-config">{{ json_encode($step->action_config, JSON_UNESCAPED_UNICODE) }}</code>
                @endif
            @elseif($step->step_type === 'wait')
                <strong>Esperar</strong>
                @if($step->action_config)
                    <code class="wf-step-config">{{ json_encode($step->action_config, JSON_UNESCAPED_UNICODE) }}</code>
                @endif
            @elseif($step->step_type === 'condition')
                <strong>Condición</strong>
                @if($step->branch_condition)
                    <code class="wf-step-config">{{ json_encode($step->branch_condition, JSON_UNESCAPED_UNICODE) }}</code>
                @endif
            @endif
        </div>

        <div class="wf-step-actions">
            <button type="button" class="wf-step-btn wf-step-btn-add-child" title="Agregar sub-paso"
                data-parent-id="{{ $step->id }}">
                + Sub-paso
            </button>
            <button type="button" class="wf-step-btn wf-step-btn-edit" title="Editar"
                data-step-id="{{ $step->id }}"
                data-step-type="{{ $step->step_type }}"
                data-action-type="{{ $step->action_type }}"
                data-action-config="{{ $step->action_config ? json_encode($step->action_config, JSON_UNESCAPED_UNICODE) : '' }}"
                data-branch-condition="{{ $step->branch_condition ? json_encode($step->branch_condition, JSON_UNESCAPED_UNICODE) : '' }}">
                Editar
            </button>
            <button type="button" class="wf-step-btn wf-step-btn-delete" title="Borrar"
                data-step-id="{{ $step->id }}">
                Borrar
            </button>
        </div>
    </div>

    @if($step->children && $step->children->count())
        <div class="wf-step-children">
            @foreach($step->children as $child)
                @include('admin.workflows.partials._step_item', ['step' => $child, 'depth' => $depth + 1, 'workflow' => $workflow])
            @endforeach
        </div>
    @endif
</div>
