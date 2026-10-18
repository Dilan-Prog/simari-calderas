@php
    $sv = \App\Http\Controllers\Backend\SurveySettingController::panelData();
    $typeLabels = \Illuminate\Support\Facades\Schema::hasTable('service_report_types') ? \App\Models\ServiceReportType::pluck('label', 'key')->all() : [];
@endphp
<div class="pform-panel" id="encuesta">
    <div class="erp-panel-header">
        <span class="erp-panel-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/></svg>
        </span>
        <div class="erp-panel-heading">
            <h2 class="pform-panel-title">Encuesta de satisfacción · Califica este servicio</h2>
            <p class="erp-panel-subtitle">Tarjetas de calificación, variables de la encuesta y textos que ve el cliente al firmar el reporte.</p>
        </div>
    </div>

    @if ($errors->any() && old('survey_title') !== null)
        <div class="erp-empty-state" style="color:#b91c1c;"><p>{{ $errors->first() }}</p></div>
    @endif

    {{-- ── Tarjetas ─────────────────────────────────────────────────────── --}}
    <h3 class="pform-label" style="font-size:14px; margin:6px 0 8px;">Tarjetas de calificación</h3>
    <div class="table-scroll" style="margin-bottom:14px;">
        <table class="clients-manager-table">
            <thead>
                <tr><th>TEXTO</th><th style="width:100px;">ESTRELLAS</th><th style="width:80px;">COLOR</th><th style="width:110px;">ESTADO</th><th style="width:260px;">ACCIONES</th></tr>
            </thead>
            <tbody>
                @forelse ($sv['cards'] as $card)
                    <tr>
                        <td><input type="text" class="pform-input" name="label" value="{{ $card->label }}" maxlength="60" required form="svCard{{ $card->id }}" aria-label="Texto"></td>
                        <td>
                            <select class="pform-select" name="stars" form="svCard{{ $card->id }}" aria-label="Estrellas">
                                @foreach (range(1, 5) as $n)<option value="{{ $n }}" @selected($card->stars === $n)>{{ $n }} ★</option>@endforeach
                            </select>
                        </td>
                        <td><input type="color" name="color" value="{{ $card->color }}" form="svCard{{ $card->id }}" aria-label="Color" style="width:44px;height:34px;padding:0;border:0;background:none;"></td>
                        <td><label style="display:flex;align-items:center;gap:6px;font-size:13px;"><input type="checkbox" name="is_active" value="1" form="svCard{{ $card->id }}" @checked($card->is_active)> {{ $card->is_active ? 'Activa' : 'Inactiva' }}</label></td>
                        <td>
                            <div class="actions-container">
                                <button type="submit" form="svCard{{ $card->id }}" class="pform-btn outline" style="padding:6px 10px;">Guardar</button>
                                <button type="submit" form="svCardUp{{ $card->id }}" class="pform-btn outline" style="padding:6px 10px;" title="Subir" aria-label="Subir">↑</button>
                                <button type="submit" form="svCardDown{{ $card->id }}" class="pform-btn outline" style="padding:6px 10px;" title="Bajar" aria-label="Bajar">↓</button>
                                <button type="submit" form="svCardDel{{ $card->id }}" class="pform-btn outline" style="padding:6px 10px;color:#dc2626;border-color:#fca5a5;">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:20px;color:#6b7280;">Sin tarjetas. Agrega al menos una para que la encuesta aparezca.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @foreach ($sv['cards'] as $card)
        <form id="svCard{{ $card->id }}" method="POST" action="{{ route('admin.erp-settings.survey.cards.update', $card) }}">@csrf @method('PUT')</form>
        <form id="svCardUp{{ $card->id }}" method="POST" action="{{ route('admin.erp-settings.survey.cards.move', $card) }}">@csrf <input type="hidden" name="direction" value="up"></form>
        <form id="svCardDown{{ $card->id }}" method="POST" action="{{ route('admin.erp-settings.survey.cards.move', $card) }}">@csrf <input type="hidden" name="direction" value="down"></form>
        <form id="svCardDel{{ $card->id }}" method="POST" action="{{ route('admin.erp-settings.survey.cards.destroy', $card) }}" onsubmit="return confirm('¿Eliminar la tarjeta &quot;{{ $card->label }}&quot;? Las calificaciones ya enviadas conservan su texto.');">@csrf @method('DELETE')</form>
    @endforeach

    <form method="POST" action="{{ route('admin.erp-settings.survey.cards.store') }}" style="display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;margin-bottom:26px;">
        @csrf
        <div class="pform-field" style="margin-bottom:0;flex:1;min-width:160px;"><label class="pform-label" for="svNewLabel">Nueva tarjeta</label><input type="text" id="svNewLabel" class="pform-input" name="label" maxlength="60" placeholder="Ej. Muy bueno" required></div>
        <div class="pform-field" style="margin-bottom:0;width:110px;"><label class="pform-label" for="svNewStars">Estrellas</label>
            <select id="svNewStars" class="pform-select" name="stars">@foreach (range(1, 5) as $n)<option value="{{ $n }}">{{ $n }} ★</option>@endforeach</select></div>
        <div class="pform-field" style="margin-bottom:0;"><label class="pform-label" for="svNewColor">Color</label><input type="color" id="svNewColor" name="color" value="#22c55e" style="width:44px;height:38px;padding:0;border:0;background:none;"></div>
        <button type="submit" class="pform-btn primary">+ Agregar</button>
    </form>

    {{-- ── Variables (chips) ────────────────────────────────────────────── --}}
    <h3 class="pform-label" style="font-size:14px; margin:6px 0 4px;">Variables de la encuesta</h3>
    <p class="pform-hint" style="margin-bottom:10px;">Se muestran como chips seleccionables según el nivel que elija el cliente: con 4 o 5 estrellas aparecen las de “Lo mejor”; con menos, las de “A mejorar”. Elegir un chip es opcional.</p>
    @foreach (['positive' => 'Lo mejor', 'negative' => 'A mejorar'] as $kind => $kindLabel)
        @php $group = $sv['questions']->where('kind', $kind); @endphp
        <h4 class="pform-label" style="font-size:13px; margin:10px 0 6px;">{{ $kindLabel }} <span style="font-weight:400;color:#6b7280;">({{ $kind === 'positive' ? '4 y 5 estrellas' : '1 a 3 estrellas' }})</span></h4>
        <div class="table-scroll" style="margin-bottom:10px;">
            <table class="clients-manager-table">
                <thead><tr><th>VARIABLE</th><th style="width:150px;">TIPO</th><th style="width:110px;">ESTADO</th><th style="width:260px;">ACCIONES</th></tr></thead>
                <tbody>
                    @forelse ($group as $q)
                        <tr>
                            <td><input type="text" class="pform-input" name="label" value="{{ $q->label }}" maxlength="120" required form="svQ{{ $q->id }}" aria-label="Variable"></td>
                            <td>
                                <select class="pform-select" name="kind" form="svQ{{ $q->id }}" aria-label="Tipo">
                                    <option value="positive" @selected($q->kind === 'positive')>Lo mejor</option>
                                    <option value="negative" @selected($q->kind === 'negative')>A mejorar</option>
                                </select>
                            </td>
                            <td><label style="display:flex;align-items:center;gap:6px;font-size:13px;"><input type="checkbox" name="is_active" value="1" form="svQ{{ $q->id }}" @checked($q->is_active)> {{ $q->is_active ? 'Activa' : 'Inactiva' }}</label></td>
                            <td>
                                <div class="actions-container">
                                    <button type="submit" form="svQ{{ $q->id }}" class="pform-btn outline" style="padding:6px 10px;">Guardar</button>
                                    <button type="submit" form="svQUp{{ $q->id }}" class="pform-btn outline" style="padding:6px 10px;" title="Subir" aria-label="Subir">↑</button>
                                    <button type="submit" form="svQDown{{ $q->id }}" class="pform-btn outline" style="padding:6px 10px;" title="Bajar" aria-label="Bajar">↓</button>
                                    <button type="submit" form="svQDel{{ $q->id }}" class="pform-btn outline" style="padding:6px 10px;color:#dc2626;border-color:#fca5a5;">Eliminar</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;padding:16px;color:#6b7280;">Sin variables de “{{ $kindLabel }}”. Ese nivel mostrará solo el comentario.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
    @foreach ($sv['questions'] as $q)
        <form id="svQ{{ $q->id }}" method="POST" action="{{ route('admin.erp-settings.survey.questions.update', $q) }}">@csrf @method('PUT')</form>
        <form id="svQUp{{ $q->id }}" method="POST" action="{{ route('admin.erp-settings.survey.questions.move', $q) }}">@csrf <input type="hidden" name="direction" value="up"></form>
        <form id="svQDown{{ $q->id }}" method="POST" action="{{ route('admin.erp-settings.survey.questions.move', $q) }}">@csrf <input type="hidden" name="direction" value="down"></form>
        <form id="svQDel{{ $q->id }}" method="POST" action="{{ route('admin.erp-settings.survey.questions.destroy', $q) }}" onsubmit="return confirm('¿Eliminar la variable &quot;{{ $q->label }}&quot;? Las calificaciones ya enviadas conservan su texto.');">@csrf @method('DELETE')</form>
    @endforeach

    <form method="POST" action="{{ route('admin.erp-settings.survey.questions.store') }}" style="display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;margin-bottom:26px;">
        @csrf
        <div class="pform-field" style="margin-bottom:0;flex:1;min-width:180px;"><label class="pform-label" for="svNewQ">Nueva variable</label><input type="text" id="svNewQ" class="pform-input" name="label" maxlength="120" placeholder="Ej. Puntualidad" required></div>
        <div class="pform-field" style="margin-bottom:0;width:160px;"><label class="pform-label" for="svNewKind">Tipo</label>
            <select id="svNewKind" class="pform-select" name="kind"><option value="positive">Lo mejor</option><option value="negative">A mejorar</option></select></div>
        <button type="submit" class="pform-btn primary">+ Agregar</button>
    </form>

    {{-- ── Textos y ajustes ─────────────────────────────────────────────── --}}
    <h3 class="pform-label" style="font-size:14px; margin:6px 0 8px;">Textos y ajustes</h3>
    <form method="POST" action="{{ route('admin.erp-settings.survey.settings') }}">
        @csrf @method('PUT')
        <label style="display:flex;align-items:center;gap:8px;font-size:14px;margin-bottom:14px;">
            <input type="checkbox" name="survey_enabled" value="1" @checked($sv['enabled'])> Encuesta activa (el cliente ve “Califica este servicio” al firmar)
        </label>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;">
            @foreach ([
                ['survey_title', 'survey.title', 'Título', 120],
                ['survey_subtitle', 'survey.subtitle', 'Subtítulo', 255],
                ['survey_questions_title', 'survey.questions_title', 'Título de la encuesta', 255],
                ['survey_comment_label', 'survey.comment_label', 'Etiqueta del comentario', 120],
                ['survey_submit_label', 'survey.submit_label', 'Texto del botón', 60],
                ['survey_name_label', 'survey.name_label', 'Etiqueta del nombre del cliente', 60],
                ['survey_good_title', 'survey.good_title', 'Título de “Lo mejor” (4 y 5 estrellas)', 120],
                ['survey_bad_title', 'survey.bad_title', 'Título de “A mejorar” (1 a 3 estrellas)', 120],
            ] as [$name, $key, $label, $max])
                <div class="pform-field" style="margin-bottom:0;">
                    <label class="pform-label" for="{{ $name }}">{{ $label }}</label>
                    <input type="text" id="{{ $name }}" class="pform-input" name="{{ $name }}" value="{{ old($name, $sv['texts'][$key]) }}" maxlength="{{ $max }}">
                </div>
            @endforeach
            <div class="pform-field" style="margin-bottom:0;grid-column:1/-1;">
                <label class="pform-label" for="survey_thanks_message">Mensaje de gracias</label>
                <input type="text" id="survey_thanks_message" class="pform-input" name="survey_thanks_message" value="{{ old('survey_thanks_message', $sv['texts']['survey.thanks_message']) }}" maxlength="500">
                <p class="pform-hint">Respaldo general. Los dos mensajes de abajo se muestran según el nivel elegido.</p>
            </div>
            <div class="pform-field" style="margin-bottom:0;grid-column:1/-1;">
                <label class="pform-label" for="survey_thanks_good">Mensaje de gracias · 4 y 5 estrellas</label>
                <input type="text" id="survey_thanks_good" class="pform-input" name="survey_thanks_good" value="{{ old('survey_thanks_good', $sv['texts']['survey.thanks_good']) }}" maxlength="500">
                <p class="pform-hint">Usa <code>{tecnico}</code> para insertar el nombre del técnico.</p>
            </div>
            <div class="pform-field" style="margin-bottom:0;grid-column:1/-1;">
                <label class="pform-label" for="survey_thanks_bad">Mensaje de gracias · 1 a 3 estrellas</label>
                <input type="text" id="survey_thanks_bad" class="pform-input" name="survey_thanks_bad" value="{{ old('survey_thanks_bad', $sv['texts']['survey.thanks_bad']) }}" maxlength="500">
                <p class="pform-hint">Admite <code>{tecnico}</code>.</p>
            </div>
        </div>

        <h3 class="pform-label" style="font-size:14px; margin:18px 0 4px;">Servicio web por defecto según el tipo de reporte</h3>
        <p class="pform-hint" style="margin-bottom:8px;">Se usa para asignar el servicio web a las calificaciones de reportes que no lo tengan.</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;">
            @foreach ($sv['reportTypes'] as $type)
                <div class="pform-field" style="margin-bottom:0;">
                    <label class="pform-label" for="sbt_{{ $type }}">{{ $typeLabels[$type] ?? $type }}</label>
                    <select id="sbt_{{ $type }}" class="pform-select" name="service_by_type[{{ $type }}]">
                        <option value="">— Sin servicio por defecto —</option>
                        @foreach ($sv['servicePages'] as $sp)
                            <option value="{{ $sp->id }}" @selected((int) ($sv['map'][$type] ?? 0) === $sp->id)>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>
        <div style="margin-top:16px;"><button type="submit" class="pform-btn primary">Guardar ajustes de la encuesta</button></div>
    </form>
</div>
