@extends('customers.layouts.master')

@section('title', 'Solicitar servicio')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    <style>
        .sr-wrap { max-width: 760px; margin: 0 auto; }
        .sr-note { font-size: .8125rem; color: var(--ts-text-muted, #6B7280); margin: 0 0 1.25rem; line-height: 1.5; }
        .sr-grid { display: grid; gap: 1rem; grid-template-columns: 1fr 1fr; }
        .sr-grid .sr-full { grid-column: 1 / -1; }
        .sr-hint { font-size: .75rem; color: var(--ts-text-muted, #6B7280); }
        .sr-error { font-size: .75rem; color: #DC2626; }
        .sr-chips { display: flex; flex-wrap: wrap; gap: .5rem; }
        .sr-chip { position: relative; }
        .sr-chip input { position: absolute; opacity: 0; pointer-events: none; }
        .sr-chip span {
            display: inline-flex; align-items: center; min-height: 40px; padding: 0 .875rem;
            border: 1px solid var(--ts-border, #D1D5DB); border-radius: 8px; font-size: .8125rem;
            color: var(--ts-text-body, #374151); background: #fff; cursor: pointer;
            transition: border-color .15s, background .15s, color .15s;
        }
        .sr-chip input:checked + span { border-color: var(--ts-primary, #ff6213); background: #FFF4EC; color: #C2410C; font-weight: 600; }
        .sr-chip input:focus-visible + span { box-shadow: 0 0 0 3px rgba(255, 98, 19, .2); }
        .sr-saved { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .5rem; }
        .sr-saved button {
            border: 1px dashed var(--ts-border, #D1D5DB); background: #F9FAFB; border-radius: 8px;
            padding: .375rem .75rem; font-size: .75rem; color: var(--ts-text-body, #374151); cursor: pointer;
        }
        .sr-saved button:hover { border-color: var(--ts-primary, #ff6213); color: #C2410C; }
        .sr-actions { display: flex; justify-content: flex-end; gap: .75rem; margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--ts-border-light, #F3F4F6); }
        .sr-pending { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .625rem; }
        .sr-pending li { display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; padding: .75rem 1rem; border: 1px solid #FED7AA; background: #FFF7ED; border-radius: 8px; font-size: .8125rem; color: #7C2D12; }
        .sr-pending strong { display: block; font-size: .875rem; }
        .sr-pending-badge { display: inline-flex; padding: .25rem .625rem; border-radius: 9999px; font-size: .6875rem; font-weight: 500; border: 1px solid #FED7AA; background: #fff; color: #C2410C; white-space: nowrap; }
        @media (max-width: 640px) {
            .sr-grid { grid-template-columns: 1fr; }
            .sr-actions .ts-btn { width: 100%; justify-content: center; height: 48px; }
        }
    </style>
@endpush

@section('content')
<div class="ts-page" id="ts-page">

    <div class="ts-breadcrumb">
        <span>Portal de servicios</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Solicitar servicio</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Solicitar un servicio</h1>
            <p class="ts-subtitle">Cuéntanos qué necesitas y la fecha que prefieres; nuestro equipo te confirmará la visita.</p>
        </div>
    </div>

    <div class="sr-wrap">

        @if($pendingRequests->isNotEmpty())
        <div class="ts-card">
            <div class="ts-card__title">Solicitudes en revisión</div>
            <p class="ts-card__subtitle">Aún no están confirmadas; te avisaremos la fecha definitiva.</p>
            <ul class="sr-pending">
                @foreach($pendingRequests as $req)
                <li>
                    <div>
                        <strong>{{ $req->service_type_label }}</strong>
                        {{ $req->service_number }}
                        &middot; Fecha preferida:
                        {{ $req->service_date ? $req->service_date->format('d/m/Y') : '—' }}
                        &middot; Enviada {{ $req->requested_at?->format('d/m/Y H:i') }}
                    </div>
                    <span class="sr-pending-badge">Solicitud enviada</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="ts-card">
            <div class="ts-card__title">Datos de la solicitud</div>
            <p class="ts-card__subtitle">Los campos con * son obligatorios.</p>
            <div class="ts-card__divider"></div>

            <form method="POST" action="{{ route('customer.service-requests.store') }}" id="sr-form" novalidate>
                @csrf

                <div class="sr-grid">

                    <div class="ts-field sr-full {{ $errors->has('service_type_id') ? 'has-error' : '' }}">
                        <label class="ts-label" for="sr-type">Tipo de servicio <span class="ts-label__req">*</span></label>
                        <div class="ts-field-select-wrap">
                            <select name="service_type_id" id="sr-type" class="ts-select-field" required>
                                <option value="">Seleccionar tipo...</option>
                                @foreach($serviceTypes as $typeId => $typeName)
                                    <option value="{{ $typeId }}" {{ (string) old('service_type_id') === (string) $typeId ? 'selected' : '' }}>
                                        {{ $typeName }}
                                    </option>
                                @endforeach
                            </select>
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </div>
                        @error('service_type_id') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ts-field {{ $errors->has('service_date') ? 'has-error' : '' }}">
                        <label class="ts-label" for="sr-date">Fecha preferida <span class="ts-label__req">*</span></label>
                        <input type="date" name="service_date" id="sr-date" class="ts-input"
                               min="{{ now()->format('Y-m-d') }}" value="{{ old('service_date') }}" required>
                        @error('service_date') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ts-field">
                        <span class="ts-label">Horario preferido</span>
                        <div class="sr-chips" role="radiogroup" aria-label="Horario preferido">
                            @foreach($timeWindows as $key => $label)
                                <label class="sr-chip">
                                    <input type="radio" name="time_window" value="{{ $key }}"
                                           {{ old('time_window', 'any') === $key ? 'checked' : '' }}>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('time_window') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ts-field sr-full {{ $errors->has('address') ? 'has-error' : '' }}">
                        <label class="ts-label" for="sr-address">Dirección del servicio <span class="ts-label__req">*</span></label>
                        @if($addresses->isNotEmpty())
                            <div class="sr-saved" aria-label="Tus direcciones guardadas">
                                @foreach($addresses as $addr)
                                    <button type="button" class="sr-use-address"
                                            data-address="{{ $addr['text'] }}"
                                            data-reference="{{ $addr['reference'] }}">
                                        {{ $addr['label'] }}{{ $addr['is_default'] ? ' (principal)' : '' }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        <input type="text" name="address" id="sr-address" class="ts-input" maxlength="255"
                               placeholder="Calle, número, colonia, ciudad"
                               value="{{ old('address', $addresses->firstWhere('is_default', true)['text'] ?? '') }}" required>
                        @error('address') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ts-field sr-full {{ $errors->has('reference') ? 'has-error' : '' }}">
                        <label class="ts-label" for="sr-reference">Referencias para llegar</label>
                        <input type="text" name="reference" id="sr-reference" class="ts-input" maxlength="255"
                               placeholder="Entre qué calles, planta, caseta, contacto en sitio..."
                               value="{{ old('reference', $addresses->firstWhere('is_default', true)['reference'] ?? '') }}">
                        @error('reference') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ts-field sr-full {{ $errors->has('description') ? 'has-error' : '' }}">
                        <label class="ts-label" for="sr-description">¿Qué necesitas? <span class="ts-label__req">*</span></label>
                        <textarea name="description" id="sr-description" class="ts-textarea" rows="5" maxlength="2000"
                                  placeholder="Describe el problema o el trabajo que requieres (equipo, marca, síntomas, urgencia...)"
                                  required>{{ old('description') }}</textarea>
                        <span class="sr-hint">Mínimo 10 caracteres. Mientras más detalle, mejor podremos preparar la visita.</span>
                        @error('description') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                </div>

                <div class="sr-actions">
                    <a href="{{ route('customer.technical-services.index') }}" class="ts-btn">Ver mis servicios</a>
                    <button type="submit" class="ts-btn ts-btn--primary" id="sr-submit">Enviar solicitud</button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('sr-form');
    if (!form) return;

    // Direcciones guardadas: rellenan dirección y referencias (el cliente puede editarlas).
    document.querySelectorAll('.sr-use-address').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('sr-address').value = btn.dataset.address || '';
            var ref = document.getElementById('sr-reference');
            if (ref && btn.dataset.reference) ref.value = btn.dataset.reference;
        });
    });

    // Evita el doble envío.
    form.addEventListener('submit', function () {
        var btn = document.getElementById('sr-submit');
        if (btn) { btn.disabled = true; btn.textContent = 'Enviando...'; }
    });
})();
</script>
@endpush
