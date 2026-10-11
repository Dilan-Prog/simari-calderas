{{-- Tabla de documentos asociados. Vars: $docs, $type, $numberField, $routeName, $extra (callable) --}}
@if ($docs->isEmpty())
    <p class="prj-muted">Sin documentos asociados.</p>
@else
    <div class="table-scroll">
        <table class="clients-manager-table prj-table">
            <thead><tr><th>FOLIO</th><th>ESTADO</th><th>DETALLE</th><th>ACCIONES</th></tr></thead>
            <tbody>
            @foreach ($docs as $d)
                <tr>
                    <td><a href="{{ route($routeName, $d->id) }}"><strong>{{ $d->{$numberField} }}</strong></a></td>
                    <td>{{ $d->status }}</td>
                    <td>{{ $extra($d) }}</td>
                    <td>
                        <a href="{{ route($routeName, $d->id) }}" class="prj-link">Ver</a>
                        @if ($type !== 'order' && auth()->user()->hasPermission('projects', 'edit'))
                            <form method="POST" action="{{ route('admin.projects.detach', $project) }}" style="display:inline" onsubmit="return confirm('¿Desasociar del proyecto?');">
                                @csrf @method('DELETE')
                                <input type="hidden" name="type" value="{{ $type }}">
                                <input type="hidden" name="id" value="{{ $d->id }}">
                                <button type="submit" class="prj-link prj-link-danger">Desasociar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
