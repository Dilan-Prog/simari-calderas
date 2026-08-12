@extends('admin.layouts.master')

@section('title', 'Nuevo dashboard - Admin')

@section('content')
<div style="padding: 32px; max-width: 640px;">
    <h1 style="font-size:24px; font-weight:700; margin-bottom:24px;">Nuevo dashboard</h1>

    <form action="{{ route('admin.dashboards.store') }}" method="POST" id="dashboard-form">
        @csrf

        <div class="form-group" style="margin-bottom:16px;">
            <label for="name">Nombre</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
        </div>

        <div class="form-group" style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
            <input type="checkbox" name="is_shared" id="is_shared" value="1" {{ old('is_shared') ? 'checked' : '' }}>
            <label for="is_shared" style="margin:0;">Compartir con todo el equipo</label>
        </div>

        <div style="margin-top:24px; display:flex; gap:12px;">
            <a href="{{ route('admin.dashboards.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Crear dashboard</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('dashboard-form').addEventListener('submit', function (e) {
    // El controlador store() responde JSON (no redirect), así que lo
    // manejamos por fetch y navegamos manualmente al editor del dashboard
    // recién creado.
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': @json(csrf_token()),
            'Accept': 'application/json',
        },
        body: formData,
    })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.dashboard) {
                window.location.href = @json(route('admin.dashboards.edit', ['dashboard' => '__ID__'])).replace('__ID__', data.dashboard.id);
            } else {
                alert('No se pudo crear el dashboard.');
            }
        })
        .catch(() => alert('No se pudo crear el dashboard.'));
});
</script>
@endpush
