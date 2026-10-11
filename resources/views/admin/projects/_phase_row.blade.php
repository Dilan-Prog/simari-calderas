@php
    $pid = $phase?->assignees?->pluck('id')->all() ?? [];
@endphp
<div class="prj-phase" data-phase>
    <div class="prj-phase-bar">
        <span class="prj-phase-handle" aria-hidden="true">⋮⋮</span>
        <span class="prj-phase-index" data-phase-index></span>
        <input type="text" name="phases[{{ $i }}][name]" class="users-manager-input prj-phase-name" placeholder="Nombre de la fase" value="{{ $phase?->name }}">
        <button type="button" class="prj-icon-btn" data-phase-up title="Subir">↑</button>
        <button type="button" class="prj-icon-btn" data-phase-down title="Bajar">↓</button>
        <button type="button" class="prj-icon-btn prj-icon-danger" data-phase-remove title="Quitar fase">✕</button>
    </div>
    <div class="prj-phase-grid">
        <label>Estado
            <select name="phases[{{ $i }}][status]" class="users-manager-input">
                @foreach (\App\Models\ProjectPhase::STATUSES as $k => $l)
                    <option value="{{ $k }}" @selected(($phase?->status ?? 'pendiente') === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </label>
        <label>Avance (%)
            <input type="number" min="0" max="100" name="phases[{{ $i }}][progress]" class="users-manager-input" value="{{ $phase?->progress ?? 0 }}">
        </label>
        <label>Inicio
            <input type="date" name="phases[{{ $i }}][start_date]" class="users-manager-input" value="{{ $phase?->start_date ? \Illuminate\Support\Carbon::parse($phase->start_date)->format('Y-m-d') : '' }}">
        </label>
        <label>Fin
            <input type="date" name="phases[{{ $i }}][end_date]" class="users-manager-input" value="{{ $phase?->end_date ? \Illuminate\Support\Carbon::parse($phase->end_date)->format('Y-m-d') : '' }}">
        </label>
    </div>
    <label class="prj-block">Encargados
        <select name="phases[{{ $i }}][assignees][]" class="users-manager-input" multiple size="3">
            @foreach ($users as $u)
                <option value="{{ $u->id }}" @selected(in_array($u->id, $pid))>{{ $u->first_name }} {{ $u->last_name }}</option>
            @endforeach
        </select>
        <small class="prj-muted">Ctrl/Cmd + clic para elegir varios.</small>
    </label>
    <label class="prj-block">Descripción
        <textarea name="phases[{{ $i }}][description]" rows="2" class="users-manager-input">{{ $phase?->description }}</textarea>
    </label>
    <label class="prj-block">Requisitos
        <textarea name="phases[{{ $i }}][requirements]" rows="2" class="users-manager-input">{{ $phase?->requirements }}</textarea>
    </label>
</div>
