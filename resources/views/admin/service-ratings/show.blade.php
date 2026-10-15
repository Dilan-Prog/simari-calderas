@extends('admin.layouts.master')
@section('title')
    Opinión de servicio - Admin
@endsection
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
@endpush
@section('content')
    @php
        $stars = fn ($n) => str_repeat('★', (int) $n) . str_repeat('☆', 5 - (int) $n);
        $statusLabels = ['pending' => 'Pendiente', 'submitted' => 'Enviada', 'skipped' => 'Omitida'];
        $tones = ['submitted' => 'ok', 'pending' => 'neu', 'skipped' => 'warn'];
    @endphp
    <div class="erp-page">
        <x-erp.page-header :title="'Opinión de ' . $rating->customer_name"
            :crumbs="[['Servicios', null], ['Opiniones de servicio', route('admin.service-ratings.index')], [$rating->customer_name, null]]">
            <a class="erp-btn" href="{{ route('admin.service-ratings.index') }}">Volver al listado</a>
        </x-erp.page-header>

        @if (session('success'))<div class="erp-card" role="status" style="padding:10px 14px;margin-bottom:12px;">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="erp-card" role="alert" style="padding:10px 14px;margin-bottom:12px;color:#b91c1c;">{{ $errors->first() }}</div>@endif

        <x-erp.card title="Resumen">
            <x-erp.facts :items="[
                ['label' => 'Cliente', 'value' => $rating->customer_name],
                ['label' => 'Reporte', 'value' => $rating->report?->report_number],
                ['label' => 'Calificación', 'value' => $rating->rating ? $stars($rating->rating) . ' (' . $rating->rating . '/5)' : '—', 'sub' => $rating->card_label],
                ['label' => 'Fecha', 'value' => ($rating->submitted_at ?? $rating->created_at)?->format('d/m/Y H:i')],
            ]" />
            <p style="margin:12px 0 0;"><x-erp.chip :tone="$tones[$rating->status] ?? 'neu'" :label="$statusLabels[$rating->status] ?? $rating->status" /></p>
        </x-erp.card>

        <x-erp.card title="Respuestas por variable" style="margin-top:14px;">
            @if ($rating->answers->isEmpty())
                <x-erp.empty title="Sin respuestas" text="El cliente no calificó variables individuales." />
            @else
                <div class="erp-table-wrap"><table class="erp-table">
                    <thead><tr><th>Variable</th><th>Puntaje</th></tr></thead>
                    <tbody>
                        @foreach ($rating->answers as $a)
                            <tr><td>{{ $a->question_label }}</td><td><span style="color:#f59e0b;">{{ $stars($a->score) }}</span> ({{ $a->score }}/5)</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            @endif
        </x-erp.card>

        <x-erp.card title="Comentario" style="margin-top:14px;">
            @if ($rating->comment)
                <p style="white-space:pre-line;margin:0;">{{ $rating->comment }}</p>
            @else
                <p style="margin:0;color:#6b7280;">Sin comentario.</p>
            @endif
        </x-erp.card>

        <x-erp.card title="Servicio web" sub="El servicio de la página pública al que pertenece esta opinión." style="margin-top:14px;">
            <form method="POST" action="{{ route('admin.service-ratings.assign-service', $rating) }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                @csrf @method('PUT')
                <label class="erp-field erp-field--grow">Servicio
                    <select class="erp-select" name="service_page_id">
                        <option value="">— Sin servicio —</option>
                        @foreach ($servicePages as $sp)
                            <option value="{{ $sp->id }}" @selected($rating->service_page_id === $sp->id)>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="erp-btn erp-btn--primary" type="submit">Guardar</button>
            </form>
        </x-erp.card>
    </div>
@endsection
