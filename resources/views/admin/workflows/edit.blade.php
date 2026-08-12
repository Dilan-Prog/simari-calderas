@extends('admin.layouts.fullscreen')

@section('title', 'Editar Workflow - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/workflow-canvas.css'])
@endpush

@section('content')
<div id="workflow-canvas-root"
     data-workflow-id="{{ $workflow->id }}"
     data-workflow-name="{{ $workflow->name }}"
     data-workflow-active="{{ $workflow->is_active ? 1 : 0 }}"
     data-canvas-url="{{ route('admin.workflows.canvas', $workflow) }}"
     data-update-url="{{ route('admin.workflows.update', $workflow) }}"
     data-toggle-url="{{ route('admin.workflows.toggle', $workflow) }}"
     data-undo-url="{{ route('admin.workflows.undo', $workflow) }}"
     data-redo-url="{{ route('admin.workflows.redo', $workflow) }}"
     data-test-url="{{ route('admin.workflows.test', $workflow) }}"
     data-steps-url="{{ route('admin.workflow-steps.store', $workflow) }}"
     data-layout-url="{{ route('admin.workflow-steps.layout', $workflow) }}"
     data-variables-url="{{ route('admin.workflow-variables.index', $workflow) }}"
     data-notes-url="{{ route('admin.workflow-notes.index', $workflow) }}"
     data-executions-url="{{ route('admin.workflow-executions.index', ['workflow_id' => $workflow->id]) }}"
     data-back-url="{{ route('admin.workflows.index') }}"
     data-user-initials="{{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1) . substr(auth()->user()->last_name ?? '', 0, 1)) }}"
     style="height: 100vh; width: 100vw;">
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/workflow-canvas.jsx'])
@endpush
