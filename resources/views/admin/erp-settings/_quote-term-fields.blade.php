@php $isNew = $isNew ?? false; @endphp
<div class="pform-field">
    <label class="pform-label" for="{{ $idp }}_title">Título</label>
    <input type="text" id="{{ $idp }}_title" name="title" class="pform-input" maxlength="150" required
        value="{{ $isNew ? old('title') : $f['title'] }}" placeholder="Ej. Garantía 1 año con el fabricante">
    @if ($isNew) @error('title')<p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p>@enderror @endif
</div>
<div class="pform-field">
    <label class="pform-label" for="{{ $idp }}_body">Texto</label>
    <textarea id="{{ $idp }}_body" name="body" class="pform-textarea" rows="4" maxlength="20000" required
        placeholder="Texto que aparecerá en la cotización y su PDF.">{{ $isNew ? old('body') : $f['body'] }}</textarea>
    @if ($isNew) @error('body')<p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p>@enderror @endif
</div>
<div class="pform-field">
    <label class="pform-label" for="{{ $idp }}_applies">Aplica a</label>
    <select id="{{ $idp }}_applies" name="applies_to" class="pform-select">
        @foreach (\App\Models\QuoteTerm::APPLIES as $k => $lbl)
            <option value="{{ $k }}" @selected(($isNew ? old('applies_to', 'both') : $f['applies_to']) === $k)>{{ $lbl }}</option>
        @endforeach
    </select>
</div>
<div class="pform-field">
    <label class="pform-label" for="{{ $idp }}_sort">Orden</label>
    <input type="number" id="{{ $idp }}_sort" name="sort_order" class="pform-input" min="0" style="max-width:120px;"
        value="{{ $isNew ? old('sort_order') : $f['sort_order'] }}" placeholder="auto">
</div>
<div class="pform-field" style="display:flex;gap:18px;flex-wrap:wrap;">
    <label><input type="checkbox" name="is_default" value="1" @checked($isNew ? old('is_default') : $f['is_default'])> Marcado por defecto al cotizar</label>
    <label><input type="checkbox" name="is_active" value="1" @checked($isNew ? old('is_active', true) : $f['is_active'])> Activo</label>
</div>
<button type="submit" class="pform-btn primary">{{ $submit }}</button>
