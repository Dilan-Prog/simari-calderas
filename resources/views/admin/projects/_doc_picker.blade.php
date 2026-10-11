{{-- Selector discreto "Proyecto" para cotización / servicio técnico. Vars: $doc, $type (quote|service|report|order) --}}
@if (auth()->user()?->hasPermission('projects'))
    @push('styles')
        @vite(['resources/css/admin/pages/erp-projects.css'])
    @endpush
    @php
        $canLink = auth()->user()->hasPermission('projects', 'edit');
        $current = $doc->project_id ? \App\Models\Project::find($doc->project_id) : null;
        $options = $canLink
            ? \App\Models\Project::where('status', '!=', 'cerrado')->orderByDesc('id')->limit(100)->get(['id', 'project_number', 'name'])
            : collect();
    @endphp
    <div class="prj-docpicker">
        <span>Proyecto:</span>
        @if ($current)
            <a href="{{ route('admin.projects.show', $current) }}"><strong>{{ $current->project_number }}</strong></a>
            <span>{{ $current->name }}</span>
        @else
            <span class="prj-muted">Sin proyecto</span>
        @endif
        @if ($canLink)
            <form method="POST" action="{{ route('admin.projects.link-document') }}" style="display:inline-flex;gap:6px;align-items:center;">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="id" value="{{ $doc->id }}">
                <select name="project_id" class="users-manager-input" aria-label="Proyecto">
                    <option value="">— Sin proyecto —</option>
                    @foreach ($options as $o)
                        <option value="{{ $o->id }}" @selected((int) $doc->project_id === $o->id)>{{ $o->project_number }} · {{ \Illuminate\Support\Str::limit($o->name, 40) }}</option>
                    @endforeach
                    @if ($current && $current->status === 'cerrado')
                        <option value="{{ $current->id }}" selected>{{ $current->project_number }} (cerrado)</option>
                    @endif
                </select>
                <button type="submit" class="prj-btn" style="padding:5px 10px;font-size:13px;">Guardar</button>
            </form>
            @if ($type === 'quote' && !$current && auth()->user()->hasPermission('projects', 'create'))
                <a href="{{ route('admin.projects.create', ['quote' => $doc->id]) }}" class="prj-link">Crear proyecto desde esta cotización</a>
            @endif
        @endif
    </div>
@endif
