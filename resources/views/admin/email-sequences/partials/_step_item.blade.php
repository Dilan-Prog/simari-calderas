{{--
    Tarjeta de un paso (orden + plantilla + días de espera) de la secuencia.
    $step es la instancia EmailSequenceStep (con template cargado);
    $templates es la lista completa de plantillas para el <select>.
--}}
<div class="es-step-card" data-step-id="{{ $step->id }}">
    <span class="es-step-order">{{ $step->order }}</span>

    <div class="es-step-fields">
        <div class="es-step-field">
            <label>Plantilla</label>
            <select class="es-step-select" disabled>
                <option value="">Selecciona una plantilla</option>
                @foreach($templates as $template)
                    <option value="{{ $template->id }}" {{ $step->template_id == $template->id ? 'selected' : '' }}>{{ $template->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="es-step-field">
            <label>Días de espera</label>
            <input type="number" class="es-step-number" value="{{ $step->delay_days }}" min="0" disabled>
        </div>
    </div>

    <button type="button" class="es-step-btn-remove" data-step-id="{{ $step->id }}">Quitar</button>
</div>
