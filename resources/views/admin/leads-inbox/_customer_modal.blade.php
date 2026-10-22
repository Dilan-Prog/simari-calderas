{{-- Modal "Agregar como cliente" (fragmento que la bandeja pide por fetch). Variables: $lead, $duplicate, $prefill, $documentTypes, $sources --}}
<style>
.lcm-overlay { position: fixed; inset: 0; z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(23,25,28,.55); }
.lcm-box { background: var(--erp-surface, #fff); border-radius: 16px; width: 100%; max-width: 680px; max-height: 92vh; overflow-y: auto; padding: 24px 26px; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
.lcm-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
.lcm-eyebrow { font-size: 11.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--erp-muted, #6b7280); margin: 0 0 2px; }
.lcm-title { font-size: 22px; font-weight: 800; color: var(--erp-ink, #17191c); margin: 0; line-height: 1.15; }
.lcm-actions { display: flex; gap: 10px; flex-shrink: 0; }
.lcm-dup { border: 1px solid var(--erp-warn-dot, #f59e0b); background: var(--erp-warn-bg, #fef3c7); color: var(--erp-warn, #a16207); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; font-size: 13px; }
.lcm-dup strong { color: var(--erp-ink, #17191c); }
.lcm-dup form { margin-top: 8px; }
.lcm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; }
.lcm-full { grid-column: 1 / -1; }
.lcm-field label { display: block; font-size: 12.5px; font-weight: 700; color: var(--erp-text, #374151); margin-bottom: 5px; }
.lcm-field label .req { color: var(--erp-bad, #b91c1c); }
.lcm-field input, .lcm-field select, .lcm-field textarea { width: 100%; box-sizing: border-box; border: 1px solid #d4d7dc; border-radius: 8px; padding: 9px 11px; font-size: 13.5px; font-family: inherit; color: var(--erp-ink, #17191c); background: #fff; }
.lcm-field input:focus, .lcm-field select:focus, .lcm-field textarea:focus { outline: none; border-color: var(--erp-accent, #f97316); box-shadow: 0 0 0 3px rgba(249,115,22,.12); }
.lcm-field textarea { min-height: 70px; resize: vertical; }
.lcm-error { padding: 10px 14px; margin-bottom: 14px; border-radius: 8px; color: var(--erp-bad, #b91c1c); background: var(--erp-bad-bg, #fde8e8); font-size: 13px; }
.lcm-hint { font-size: 12px; color: var(--erp-muted, #6b7280); margin: 14px 0 0; }
@media (max-width: 640px) { .lcm-grid { grid-template-columns: 1fr; } .lcm-head { flex-direction: column; align-items: stretch; } }
</style>
<div class="lcm-overlay" data-lcm role="dialog" aria-modal="true" aria-labelledby="lcmTitle">
    <div class="lcm-box">
        @if ($lead->customer_id)
            <div class="lcm-head">
                <div><p class="lcm-eyebrow">Lead · {{ $lead->source_label }}</p><h2 class="lcm-title" id="lcmTitle">Ya es cliente</h2></div>
                <div class="lcm-actions"><button type="button" class="erp-btn" data-lcm-close>Cerrar</button></div>
            </div>
            <p>Este lead ya está ligado a
                <a class="erp-btn erp-btn--link" href="{{ route('admin.clients.information', $lead->customer_id) }}">{{ trim(($lead->customer?->first_name ?? '') . ' ' . ($lead->customer?->last_name ?? '')) ?: 'un cliente' }}</a>.</p>
        @else
            <div class="lcm-head">
                <div>
                    <p class="lcm-eyebrow">Lead · {{ $lead->source_label }}</p>
                    <h2 class="lcm-title" id="lcmTitle">Agregar como cliente</h2>
                </div>
                <div class="lcm-actions">
                    <button type="button" class="erp-btn" data-lcm-close>Cancelar</button>
                    <button type="submit" form="lcmForm" class="erp-btn erp-btn--primary">Guardar cliente</button>
                </div>
            </div>

            <div class="lcm-error" data-lcm-error role="alert" hidden></div>

            @if ($duplicate)
                <div class="lcm-dup" role="status">
                    Ya existe un cliente con este {{ $lead->phone_normalized && $duplicate->phone ? 'teléfono' : 'correo' }}:
                    <strong>{{ trim($duplicate->first_name . ' ' . $duplicate->last_name) }}</strong>@if ($duplicate->company) · {{ $duplicate->company }}@endif.
                    Para no duplicarlo, puedes ligar este lead a él.
                    <form method="POST" action="{{ route('admin.leads-inbox.customer.store', $lead) }}">
                        @csrf
                        <input type="hidden" name="link_customer_id" value="{{ $duplicate->id }}">
                        <button type="submit" class="erp-btn erp-btn--sm">Vincular a este cliente</button>
                    </form>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.leads-inbox.customer.store', $lead) }}" id="lcmForm">
                @csrf
                <div class="lcm-grid">
                    <div class="lcm-field lcm-full">
                        <label for="lcmName">Nombre completo <span class="req">*</span></label>
                        <input type="text" id="lcmName" name="full_name" required maxlength="200" value="{{ old('full_name', $prefill['full_name']) }}" placeholder="Nombre y apellidos de la persona de contacto">
                    </div>
                    <div class="lcm-field">
                        <label for="lcmCompany">Empresa</label>
                        <input type="text" id="lcmCompany" name="company" maxlength="150" value="{{ old('company') }}">
                    </div>
                    <div class="lcm-field">
                        <label for="lcmEmail">Correo</label>
                        <input type="email" id="lcmEmail" name="email" maxlength="150" value="{{ old('email', $prefill['email']) }}">
                    </div>
                    <div class="lcm-field">
                        <label for="lcmPhone">Teléfono</label>
                        <input type="text" id="lcmPhone" name="phone" maxlength="30" value="{{ old('phone', $prefill['phone']) }}">
                    </div>
                    <div class="lcm-field">
                        <label for="lcmRfc">RFC</label>
                        <input type="text" id="lcmRfc" name="rfc" maxlength="20" value="{{ old('rfc') }}">
                    </div>
                    <div class="lcm-field">
                        <label for="lcmTipo">Tipo de persona</label>
                        <select id="lcmTipo" name="tipo_persona">
                            <option value="">Sin especificar</option>
                            <option value="fisica" @selected(old('tipo_persona') === 'fisica')>Persona física</option>
                            <option value="moral" @selected(old('tipo_persona') === 'moral')>Persona moral</option>
                        </select>
                    </div>
                    <div class="lcm-field">
                        <label for="lcmDoc">Tipo de documento <span class="req">*</span></label>
                        <select id="lcmDoc" name="document_type" required>
                            @foreach ($documentTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('document_type', 'ine') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lcm-field">
                        <label for="lcmSource">Origen del cliente</label>
                        <select id="lcmSource" name="source">
                            @foreach ($sources as $value => $label)
                                <option value="{{ $value }}" @selected(old('source', $prefill['source']) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lcm-field lcm-full">
                        <label for="lcmNotes">Notas</label>
                        <textarea id="lcmNotes" name="notes" maxlength="1000">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <p class="lcm-hint">Se crea en tu base de clientes y el lead queda ligado a él, junto con su conversación de WhatsApp.</p>
            </form>
        @endif
    </div>
</div>
