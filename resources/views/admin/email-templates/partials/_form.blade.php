{{--
    Formulario compartido por create.blade.php y edit.blade.php.
    Variables esperadas:
      - $emailTemplate (opcional): presente solo en modo edición.
      - $formAction, $formMethod: destino y verbo HTTP del <form>.
--}}
@php
    $isEdit = isset($emailTemplate);
    $typeOptions = [
        'campaign'      => 'Campaña',
        'transactional' => 'Transaccional',
        'notification'  => 'Notificación',
        'welcome'       => 'Bienvenida',
        'other'         => 'Otro',
    ];
@endphp

<form id="emailTemplateForm" method="POST" action="{{ $formAction }}"
      data-is-edit="{{ $isEdit ? '1' : '0' }}"
      data-preview-url="{{ $isEdit ? route('admin.email-templates.preview', $emailTemplate) : '' }}">
    @csrf
    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="et-form-card">
        <h2 class="et-form-section-title">Datos generales</h2>
        <div class="et-form-grid">
            <div class="et-form-field">
                <label class="et-form-label" for="etName">Nombre <span class="req">*</span></label>
                <input type="text" id="etName" name="name" class="et-form-input @error('name') is-invalid @enderror"
                       value="{{ old('name', $emailTemplate->name ?? '') }}" maxlength="255" required>
                @error('name') <span class="et-form-error">{{ $message }}</span> @enderror
            </div>

            <div class="et-form-field">
                <label class="et-form-label" for="etType">Tipo <span class="req">*</span></label>
                <select id="etType" name="type" class="et-form-select @error('type') is-invalid @enderror" required>
                    @php $currentType = old('type', $emailTemplate->type ?? ''); @endphp
                    <option value="" disabled {{ $currentType === '' ? 'selected' : '' }}>Selecciona un tipo</option>
                    @foreach($typeOptions as $value => $label)
                        <option value="{{ $value }}" {{ $currentType === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                    {{-- El campo es texto libre en el backend (sin enum), así que si la
                         plantilla trae un tipo fuera de esta lista sugerida se agrega
                         como opción extra para no perder el valor guardado. --}}
                    @if($currentType !== '' && !array_key_exists($currentType, $typeOptions))
                        <option value="{{ $currentType }}" selected>{{ $currentType }}</option>
                    @endif
                </select>
                @error('type') <span class="et-form-error">{{ $message }}</span> @enderror
            </div>

            <div class="et-form-field et-form-field-full">
                <label class="et-form-label" for="etSubject">Asunto <span class="req">*</span></label>
                <input type="text" id="etSubject" name="subject" class="et-form-input @error('subject') is-invalid @enderror"
                       value="{{ old('subject', $emailTemplate->subject ?? '') }}" maxlength="255" required>
                <span class="et-form-hint">Admite tokens, por ejemplo: Hola @{{contact.name}}</span>
                @error('subject') <span class="et-form-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <h2 class="et-form-section-title">Contenido HTML</h2>

        <div class="et-token-bar">
            <span class="et-token-bar-label">Insertar token:</span>
            <button type="button" class="et-token-btn" data-token="@{{contact.name}}">@{{contact.name}}</button>
            <button type="button" class="et-token-btn" data-token="@{{contact.email}}">@{{contact.email}}</button>
            <button type="button" class="et-token-btn" data-token="@{{contact.company}}">@{{contact.company}}</button>
            <button type="button" class="et-token-btn" data-token="@{{deal.amount}}">@{{deal.amount}}</button>
            <button type="button" class="et-token-btn" data-token="@{{deal.name}}">@{{deal.name}}</button>
        </div>

        <div class="et-editor-grid">
            <div class="et-form-field">
                <label class="et-form-label" for="etHtmlBody">HTML de la plantilla <span class="req">*</span></label>
                <textarea id="etHtmlBody" name="html_body" class="et-form-textarea et-code-textarea @error('html_body') is-invalid @enderror"
                          rows="18" required>{{ old('html_body', $emailTemplate->html_body ?? '') }}</textarea>
                @error('html_body') <span class="et-form-error">{{ $message }}</span> @enderror
            </div>

            <div class="et-form-field">
                <div class="et-preview-header">
                    <label class="et-form-label">Vista previa</label>
                    @if($isEdit)
                        <button type="button" id="etRefreshPreview" class="et-preview-refresh-btn">Actualizar vista previa</button>
                    @endif
                </div>

                @if($isEdit)
                    {{-- Modo edición: la plantilla ya existe en BD, así que el preview
                         se resuelve del lado del servidor vía
                         route('admin.email-templates.preview', $emailTemplate) — mismo
                         helper (EmailTemplateService::render) usado al enviar correos
                         reales, con un Customer de ejemplo. --}}
                    <div class="et-preview-subject" id="etPreviewSubject">Asunto: —</div>
                    <div class="et-preview-frame-wrap">
                        <iframe id="etPreviewFrame" class="et-preview-frame" title="Vista previa del correo"></iframe>
                    </div>
                @else
                    {{-- Modo creación: la plantilla todavía no existe en BD (no hay
                         $emailTemplate ni ruta de preview válida), así que aquí NO se
                         hace fetch al backend. Limitación conocida: este preview solo
                         inyecta el HTML crudo tal cual se escribió en el textarea, SIN
                         resolver los tokens {{contact.*}}/{{deal.*}} contra datos de
                         ejemplo -- eso solo ocurre en edit.blade.php después de guardar
                         por primera vez. --}}
                    <div class="et-preview-notice">
                        La vista previa con datos de ejemplo (tokens resueltos) estará disponible después de guardar la plantilla por primera vez. Mientras tanto se muestra el HTML tal cual, sin reemplazar tokens.
                    </div>
                    <div class="et-preview-frame-wrap">
                        <iframe id="etPreviewFrame" class="et-preview-frame" title="Vista previa del correo"></iframe>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="et-form-actions">
        <a href="{{ route('admin.email-templates.index') }}" class="et-btn-cancel">Cancelar</a>
        <button type="submit" class="et-btn-save">{{ $isEdit ? 'Guardar cambios' : 'Crear plantilla' }}</button>
    </div>
</form>

@include('admin.email-templates.partials._scripts')
