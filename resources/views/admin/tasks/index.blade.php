@extends('admin.layouts.master')

@section('title', 'Tareas - Admin')

@push('styles')
<style>
:root {
    --background--white:          #ffffff;
    --header-footer-color:        #1A2535;
    --text-subwhite-color:        #D1D5DC;
    --text-description-color:     #6B7280;
    --secondary-color:            #ff6213;
    --font-family:                'Inter', sans-serif;
    --shadow-sm:                  0 1px 2px rgba(0,0,0,.06);
}

.tasks-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }

.tasks-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.tasks-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.tasks-breadcrumb-current { color: #374151; }
.tasks-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.tasks-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }

.tasks-filters { display: flex; gap: 8px; }
.tasks-filter-link { display: inline-flex; align-items: center; height: 36px; padding: 0 14px; border-radius: 7px; font-size: 13px; font-weight: 600; text-decoration: none; color: #4b5563; background: #f2f3f5; }
.tasks-filter-link.active { background: var(--secondary-color); color: #fff; }

.tasks-table-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.tasks-table-scroll { overflow-x: auto; }
.tasks-table { width: 100%; border-collapse: collapse; min-width: 760px; }
.tasks-table thead tr { background: var(--header-footer-color); height: 44px; }
.tasks-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.tasks-table tbody tr { height: 56px; border-bottom: 1px solid #F3F4F6; cursor: pointer; }
.tasks-table tbody tr:last-child { border-bottom: none; }
.tasks-table tbody tr:hover { background: rgba(255,247,237,.4); }
.tasks-table td { padding: 10px 20px; vertical-align: middle; font-size: 13px; color: #111827; }
.tasks-td-title { font-weight: 600; }
.tasks-td-desc { color: var(--text-description-color); font-size: 12.5px; max-width: 360px; }
.tasks-pill { display: inline-flex; align-items: center; height: 22px; padding: 0 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; background: #eef2ff; color: #4338ca; }
.tasks-pill--open { background: #eef2ff; color: #4338ca; }
.tasks-pill--pending { background: #fef3c7; color: #92400e; }
.tasks-pill--in_progress { background: #dbeafe; color: #1e40af; }
.tasks-pill--in_review { background: #fae8ff; color: #86198f; }
.tasks-pill--closed { background: #f2f3f5; color: #6b7280; }
.tasks-link { color: var(--secondary-color); font-weight: 600; text-decoration: none; }
.tasks-link:hover { text-decoration: underline; }
.tasks-muted { color: #9CA3AF; }
.tasks-empty { padding: 60px 20px; text-align: center; color: var(--text-description-color); font-size: 14px; }
.tasks-new-btn { display: inline-flex; align-items: center; height: 36px; padding: 0 16px; border-radius: 7px; font-size: 13px; font-weight: 700; color: #fff; background: var(--secondary-color); border: none; cursor: pointer; text-decoration: none; }

/* Modal crear/ver/editar -- autocontenido en esta página (no depende de CSS global del admin). */
.task-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(17,24,39,.5); z-index: 1000; align-items: center; justify-content: center; }
.task-modal-overlay.active { display: flex; }
.task-modal-box { background: #fff; border-radius: 10px; width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto; padding: 24px; }
.task-modal-title { font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 18px; }
.task-modal-field { margin-bottom: 14px; }
.task-modal-field label { display: block; font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 5px; }
.task-modal-field input[type="text"], .task-modal-field input[type="datetime-local"], .task-modal-field select, .task-modal-field textarea {
    width: 100%; box-sizing: border-box; border: 1px solid #d1d5db; border-radius: 6px; padding: 9px 11px; font-size: 13.5px; font-family: var(--font-family); color: #111827;
}
.task-modal-field textarea { resize: vertical; min-height: 70px; }
.task-modal-field input[readonly] { background: #f3f4f6; color: #6b7280; cursor: not-allowed; }
.task-modal-field-hint { font-size: 11.5px; color: #9ca3af; margin-top: 4px; }
.task-modal-related { font-size: 12.5px; color: var(--text-description-color); background: #f9fafb; border-radius: 6px; padding: 10px 11px; margin-bottom: 14px; }
.task-modal-related strong { color: #374151; }
.task-modal-related-row { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
.task-modal-customer-line { margin: 2px 0 0; }
.task-modal-quickview-btn { font-size: 12px; font-weight: 700; color: var(--secondary-color); background: none; border: none; cursor: pointer; padding: 0; white-space: nowrap; }
.task-modal-quickview-panel { display: none; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #e5e7eb; font-size: 12.5px; color: #374151; }
.task-modal-quickview-panel.active { display: block; }
.task-modal-quickview-panel div { margin-bottom: 4px; }
.task-modal-quickview-products { margin-top: 10px; display: flex; flex-direction: column; gap: 8px; }
.task-modal-quickview-product { display: flex; align-items: center; gap: 8px; }
.task-modal-quickview-product img { width: 34px; height: 34px; object-fit: cover; border-radius: 5px; border: 1px solid #e5e7eb; flex-shrink: 0; background: #f3f4f6; }
.task-modal-quickview-product-placeholder { width: 34px; height: 34px; border-radius: 5px; border: 1px solid #e5e7eb; background: #f3f4f6; flex-shrink: 0; }
.task-modal-quickview-product-name { font-weight: 600; color: #111827; font-size: 12.5px; }
.task-modal-quickview-product-sku { color: #9ca3af; font-size: 11.5px; }
.task-modal-related-link { color: var(--secondary-color); font-weight: 600; text-decoration: none; font-size: 12.5px; display: inline-block; margin-top: 8px; }
.task-modal-related-link:hover { text-decoration: underline; }
.task-modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
.task-modal-btn { height: 38px; padding: 0 18px; border-radius: 7px; font-size: 13px; font-weight: 700; cursor: pointer; border: none; }
.task-modal-btn--cancel { background: #f2f3f5; color: #374151; }
.task-modal-btn--save { background: var(--secondary-color); color: #fff; }
.task-modal-btn--delete { background: #fff; color: #b91c1c; border: 1px solid #fecaca; }
.task-modal-btn--delete:hover { background: #fef2f2; }
</style>
@endpush

@section('content')
<div class="tasks-page">
    <div class="tasks-header">
        <div>
            <div class="tasks-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Panel de Control</a>
                <span>/</span>
                <span class="tasks-breadcrumb-current">Tareas</span>
            </div>
            <h1 class="tasks-title">Tareas</h1>
            <p class="tasks-subtitle">Pendientes generados automáticamente por las automatizaciones (ej. "sin correo", "sin WhatsApp") y tareas manuales.</p>
        </div>
        <div class="tasks-filters">
            <a href="{{ route('admin.tasks.index', ['status' => 'open']) }}" class="tasks-filter-link {{ $status === 'open' ? 'active' : '' }}">Abiertas</a>
            <a href="{{ route('admin.tasks.index', ['status' => 'all']) }}" class="tasks-filter-link {{ $status === 'all' ? 'active' : '' }}">Todas</a>
            <button type="button" class="tasks-new-btn" id="taskNewBtn">+ Nueva tarea</button>
        </div>
    </div>

    <div class="tasks-table-card">
        @if ($tasks->isEmpty())
            <div class="tasks-empty">No hay tareas {{ $status === 'open' ? 'abiertas' : '' }} por ahora.</div>
        @else
            <div class="tasks-table-scroll">
                <table class="tasks-table">
                    <thead>
                        <tr>
                            <th>Tarea</th>
                            <th>Relacionado con</th>
                            <th>Origen</th>
                            <th>Asignada a</th>
                            <th>Estado</th>
                            <th>Creada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr class="task-row" data-task="{{ json_encode([
                                'id' => $task->id,
                                'title' => $task->title,
                                'description' => $task->description,
                                'due_at' => optional($task->due_at)->format('Y-m-d\TH:i'),
                                'status' => $task->status,
                                'closed_at' => optional($task->closed_at)->format('d/m/Y H:i'),
                                'assigned_to' => $task->assigned_to,
                                'related_label' => $task->taskableLabel(),
                                'related_url' => $task->taskableUrl(),
                                'customer' => $task->taskableCustomerInfo(),
                                'quick_view' => $task->taskableQuickView(),
                                'quote_items' => $task->taskableQuoteItems(),
                            ]) }}">
                                <td>
                                    <div class="tasks-td-title">{{ $task->title }}</div>
                                    @if ($task->description)
                                        <div class="tasks-td-desc">{{ $task->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($task->taskableUrl())
                                        <a href="{{ $task->taskableUrl() }}" class="tasks-link" onclick="event.stopPropagation()">{{ $task->taskableLabel() }}</a>
                                    @elseif ($task->taskableLabel())
                                        {{ $task->taskableLabel() }}
                                    @else
                                        <span class="tasks-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($task->createdByWorkflow)
                                        <span class="tasks-pill">{{ $task->createdByWorkflow->name }}</span>
                                    @else
                                        <span class="tasks-muted">Manual</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($task->assignee)
                                        {{ $task->assignee->full_name }}
                                    @else
                                        <span class="tasks-muted">Sin asignar</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="tasks-pill tasks-pill--{{ $task->status }}">{{ \App\Models\Task::statusLabel($task->status) }}</span>
                                </td>
                                <td class="tasks-muted">{{ $task->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{ $tasks->links() }}
</div>

{{-- Modal compartido: crear (form vacío -> POST) y ver/editar (form prellenado -> PUT). --}}
<div class="task-modal-overlay" id="taskModalOverlay">
    <div class="task-modal-box">
        <h2 class="task-modal-title" id="taskModalTitle">Nueva tarea</h2>
        <form method="POST" id="taskForm" action="{{ route('admin.tasks.store') }}">
            @csrf
            <div id="taskFormMethod"></div>

            <div class="task-modal-related" id="taskModalRelated" style="display:none;">
                <div class="task-modal-related-row">
                    <div>
                        <strong id="taskModalRelatedLabel"></strong>
                        <p class="task-modal-customer-line" id="taskModalCustomerName" style="display:none;"></p>
                        <p class="task-modal-customer-line" id="taskModalCustomerCompany" style="display:none;"></p>
                        <p class="task-modal-customer-line" id="taskModalCustomerPhone" style="display:none;"></p>
                        <p class="task-modal-customer-line" id="taskModalCustomerEmail" style="display:none;"></p>
                        <p class="task-modal-customer-line" id="taskModalCustomerRfc" style="display:none;"></p>
                    </div>
                    <button type="button" class="task-modal-quickview-btn" id="taskModalQuickViewBtn" style="display:none;">Vista rápida ⌄</button>
                </div>
                <div class="task-modal-quickview-panel" id="taskModalQuickViewPanel"></div>
                <a href="#" target="_blank" class="task-modal-related-link" id="taskModalRelatedLink" style="display:none;">Ver cotización completa ↗</a>
            </div>

            <div class="task-modal-field">
                <label>Título *</label>
                <input type="text" name="title" id="taskFieldTitle" required maxlength="255">
                <p class="task-modal-field-hint" id="taskFieldTitleHint" style="display:none;">No se puede modificar una vez creada la tarea.</p>
            </div>
            <div class="task-modal-field">
                <label>Descripción</label>
                <textarea name="description" id="taskFieldDescription"></textarea>
            </div>
            <div class="task-modal-field">
                <label>Fecha límite</label>
                <input type="datetime-local" name="due_at" id="taskFieldDueAt">
                <p class="task-modal-field-hint" id="taskFieldDueAtHint" style="display:none;">No se puede modificar una vez creada la tarea.</p>
            </div>
            <div class="task-modal-field">
                <label>Estado</label>
                <select name="status" id="taskFieldStatus">
                    @foreach (\App\Models\Task::STATUSES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="task-modal-field-hint" id="taskFieldClosedAtHint" style="display:none;"></p>
            </div>
            <div class="task-modal-field">
                <label>Asignada a</label>
                <select name="assigned_to" id="taskFieldAssignedTo">
                    <option value="">Sin asignar</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="task-modal-actions">
                <button type="button" class="task-modal-btn task-modal-btn--delete" id="taskModalDeleteBtn" style="display:none;">Eliminar</button>
                <div style="flex:1;"></div>
                <button type="button" class="task-modal-btn task-modal-btn--cancel" id="taskModalCancel">Cancelar</button>
                <button type="submit" class="task-modal-btn task-modal-btn--save">Guardar</button>
            </div>
        </form>
        <form method="POST" id="taskDeleteForm" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById('taskModalOverlay');
    const title = document.getElementById('taskModalTitle');
    const form = document.getElementById('taskForm');
    const methodField = document.getElementById('taskFormMethod');
    const related = document.getElementById('taskModalRelated');
    const relatedLabel = document.getElementById('taskModalRelatedLabel');
    const relatedLink = document.getElementById('taskModalRelatedLink');
    const customerName = document.getElementById('taskModalCustomerName');
    const customerCompany = document.getElementById('taskModalCustomerCompany');
    const customerPhone = document.getElementById('taskModalCustomerPhone');
    const customerEmail = document.getElementById('taskModalCustomerEmail');
    const customerRfc = document.getElementById('taskModalCustomerRfc');
    const quickViewBtn = document.getElementById('taskModalQuickViewBtn');
    const quickViewPanel = document.getElementById('taskModalQuickViewPanel');
    const fieldTitle = document.getElementById('taskFieldTitle');
    const fieldTitleHint = document.getElementById('taskFieldTitleHint');
    const fieldDescription = document.getElementById('taskFieldDescription');
    const fieldDueAt = document.getElementById('taskFieldDueAt');
    const fieldDueAtHint = document.getElementById('taskFieldDueAtHint');
    const fieldStatus = document.getElementById('taskFieldStatus');
    const fieldClosedAtHint = document.getElementById('taskFieldClosedAtHint');
    const fieldAssignedTo = document.getElementById('taskFieldAssignedTo');
    const deleteBtn = document.getElementById('taskModalDeleteBtn');
    const deleteForm = document.getElementById('taskDeleteForm');

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    function setLine(el, label, value) {
        if (value) {
            el.style.display = 'block';
            el.textContent = label + ': ' + value;
        } else {
            el.style.display = 'none';
        }
    }

    function openCreate() {
        title.textContent = 'Nueva tarea';
        form.action = '{{ route('admin.tasks.store') }}';
        methodField.innerHTML = '';
        related.style.display = 'none';
        quickViewPanel.classList.remove('active');
        fieldTitle.value = '';
        fieldTitle.removeAttribute('readonly');
        fieldTitleHint.style.display = 'none';
        fieldDescription.value = '';
        fieldDueAt.value = '';
        fieldDueAt.removeAttribute('readonly');
        fieldDueAtHint.style.display = 'none';
        fieldStatus.value = 'open';
        fieldClosedAtHint.style.display = 'none';
        fieldAssignedTo.value = '';
        deleteBtn.style.display = 'none';
        overlay.classList.add('active');
    }

    function openEdit(task) {
        title.textContent = 'Detalle de la tarea';
        form.action = '{{ url('admin/tareas') }}/' + task.id;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

        if (task.related_label) {
            related.style.display = 'block';
            relatedLabel.textContent = task.related_label;
            setLine(customerName, 'Cliente', task.customer ? task.customer.name : null);
            setLine(customerCompany, 'Empresa', task.customer ? task.customer.company : null);
            setLine(customerPhone, 'Teléfono', task.customer ? task.customer.phone : null);
            setLine(customerEmail, 'Correo', task.customer ? task.customer.email : null);
            setLine(customerRfc, 'RFC', task.customer ? task.customer.rfc : null);

            quickViewPanel.classList.remove('active');
            if (task.quick_view) {
                quickViewBtn.style.display = 'inline-block';
                quickViewBtn.textContent = 'Vista rápida ⌄';
                quickViewBtn.onclick = function () {
                    const isOpen = quickViewPanel.classList.toggle('active');
                    quickViewBtn.textContent = isOpen ? 'Vista rápida ⌃' : 'Vista rápida ⌄';
                    if (isOpen) {
                        const qv = task.quick_view;
                        const items = task.quote_items || [];
                        const productsHtml = items.length ? (
                            '<div class="task-modal-quickview-products">' +
                            items.map(function (item) {
                                const img = item.image_url
                                    ? '<img src="' + escapeHtml(item.image_url) + '" alt="">'
                                    : '<div class="task-modal-quickview-product-placeholder"></div>';
                                return '<div class="task-modal-quickview-product">' + img +
                                    '<div><div class="task-modal-quickview-product-name">' + escapeHtml(item.name) + '</div>' +
                                    '<div class="task-modal-quickview-product-sku">' + escapeHtml(item.sku || 'Sin SKU') + '</div></div></div>';
                            }).join('') +
                            '</div>'
                        ) : '';
                        quickViewPanel.innerHTML =
                            '<div><strong>N.o cotización:</strong> ' + qv.quote_number + '</div>' +
                            '<div><strong>Estatus:</strong> ' + qv.status_label + '</div>' +
                            '<div><strong>Total:</strong> ' + qv.total + '</div>' +
                            (qv.sent_at ? '<div><strong>Enviada:</strong> ' + qv.sent_at + '</div>' : '') +
                            (qv.valid_until ? '<div><strong>Vigente hasta:</strong> ' + qv.valid_until + '</div>' : '') +
                            productsHtml;
                    }
                };
            } else {
                quickViewBtn.style.display = 'none';
            }

            if (task.related_url) {
                relatedLink.style.display = 'inline-block';
                relatedLink.href = task.related_url;
            } else {
                relatedLink.style.display = 'none';
            }
        } else {
            related.style.display = 'none';
        }

        fieldTitle.value = task.title || '';
        fieldTitle.setAttribute('readonly', 'readonly');
        fieldTitleHint.style.display = 'block';
        fieldDescription.value = task.description || '';
        fieldDueAt.value = task.due_at || '';
        fieldDueAt.setAttribute('readonly', 'readonly');
        fieldDueAtHint.style.display = 'block';
        fieldStatus.value = task.status || 'open';
        if (task.closed_at) {
            fieldClosedAtHint.style.display = 'block';
            fieldClosedAtHint.textContent = 'Cerrada el ' + task.closed_at;
        } else {
            fieldClosedAtHint.style.display = 'none';
        }
        fieldAssignedTo.value = task.assigned_to || '';

        deleteBtn.style.display = 'inline-block';
        deleteBtn.onclick = function () {
            if (!confirm('¿Eliminar esta tarea? Esto no se puede deshacer.')) return;
            deleteForm.action = '{{ url('admin/tareas') }}/' + task.id;
            deleteForm.submit();
        };

        overlay.classList.add('active');
    }

    function closeModal() {
        overlay.classList.remove('active');
    }

    document.getElementById('taskNewBtn').addEventListener('click', openCreate);
    document.getElementById('taskModalCancel').addEventListener('click', closeModal);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });

    document.querySelectorAll('.task-row').forEach(function (row) {
        row.addEventListener('click', function () {
            openEdit(JSON.parse(row.dataset.task));
        });
    });
})();
</script>
@endsection
