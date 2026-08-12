@extends('admin.layouts.master')

@section('title', 'Dashboards - Admin')

@section('content')
<div style="padding: 32px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <h1 style="font-size:24px; font-weight:700; margin:0;">Dashboards</h1>
        <a href="{{ route('admin.dashboards.create') }}" class="btn btn-primary">+ Nuevo dashboard</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Propietario</th>
                <th>Visibilidad</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dashboards as $dashboard)
                <tr>
                    <td><a href="{{ route('admin.dashboards.show', $dashboard) }}">{{ $dashboard->name }}</a></td>
                    <td>{{ $dashboard->owner?->full_name }}</td>
                    <td>
                        @if ($dashboard->is_shared)
                            <span class="badge badge-success">Compartido</span>
                        @else
                            <span class="badge badge-secondary">Privado</span>
                        @endif
                    </td>
                    <td style="display:flex; gap:8px;">
                        <a href="{{ route('admin.dashboards.edit', $dashboard) }}">Editar</a>
                        <form action="{{ route('admin.dashboards.destroy', $dashboard) }}" method="POST" onsubmit="return confirm('¿Eliminar este dashboard?');" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No hay dashboards disponibles todavía.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
