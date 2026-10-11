{{-- Tabla de documentos asociados. Vars: $docs, $type, $numberField, $routeName, $extra (callable) --}}
@if ($docs->isEmpty())
    <p class="prj-muted">Sin documentos asociados.</p>
@else
    <div class="erp-table-wrap">
        <table class="erp-table">
            <thead><tr><th>Folio</th><th>Estado</th><th>Detalle</th><th class="is-right">Acciones</th></tr></thead>
            <tbody>
            @foreach ($docs as $d)
                <tr>
                    <td class="erp-cell-folio"><a href="{{ route($routeName, $d->id) }}">{{ $d->{$numberField} }}</a></td>
                    <td>@if ($d->status)<x-erp.status :value="$d->status" />@else — @endif</td>
                    <td>{{ $extra($d) }}</td>
                    <td class="is-right">
                        <a href="{{ route($routeName, $d->id) }}" class="erp-btn erp-btn--link">Ver</a>
                        @if ($type !== 'order' && auth()->user()->hasPermission('projects', 'edit'))
                            <form method="POST" action="{{ route('admin.projects.detach', $project) }}" class="prj-inline" onsubmit="return confirm('¿Desasociar del proyecto?');">
                                @csrf @method('DELETE')
                                <input type="hidden" name="type" value="{{ $type }}">
                                <input type="hidden" name="id" value="{{ $d->id }}">
                                <button type="submit" class="erp-btn erp-btn--link prj-link-danger">Desasociar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
