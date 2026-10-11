{{-- Buscador para asociar un documento existente. Vars: $type, $label --}}
<form method="POST" action="{{ route('admin.projects.attach', $project) }}" class="prj-attach" data-attach-box data-type="{{ $type }}" data-url="{{ route('admin.projects.documents') }}" data-project="{{ $project->id }}">
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">
    <input type="hidden" name="id" value="" data-attach-id>
    <label>{{ $label }}
        <input type="text" class="users-manager-input" placeholder="Buscar por folio..." autocomplete="off" data-attach-input>
    </label>
    <div class="prj-suggest" data-attach-list hidden></div>
    <button type="submit" class="prj-btn prj-btn-primary" data-attach-submit disabled>Asociar</button>
</form>
