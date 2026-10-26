{{--
    Tarjeta "Negocios" de la bandeja de leads. Variables: $lead, $users.
    1) Sin cliente: pide agregarlo como cliente primero.  2) Con cliente: "+ Nuevo negocio" (formulario) y un bloque plegable
    por negocio ($lead->deals, el mas reciente primero) con folio, etapa, monto y cotizaciones (ligar / quitar / crear en modal).
--}}
@php
    $customer = $lead->customer_id ? $lead->customer : null;
    $deals = $customer ? $lead->deals->loadMissing(['quotes', 'stage']) : collect();
    $money = fn ($amount, $currency) => '$' . number_format((float) $amount, 2) . ' ' . ($currency ?: 'MXN');
    $customerName = $customer ? trim($customer->first_name . ' ' . $customer->last_name) : '';
    $hasDeals = $deals->isNotEmpty();
    $available = $customer ? \App\Models\Quote::where('customer_id', $lead->customer_id)->orderByDesc('id')->limit(60)->get() : collect();
@endphp
<details class="lw-card lw-sec lw-deal" data-sec="negocio" open>
<summary class="lw-sec-h"><h3>{{ $hasDeals ? 'Negocios (' . $deals->count() . ')' : 'Negocio' }}</h3><span class="lw-sec-chev" aria-hidden="true"></span></summary>
<div class="lw-sec-b">

    @if (! $customer)
        <p class="lw-muted">Para crear un negocio primero agrega al lead como cliente: así el negocio nace con sus datos ya dados de alta.</p>
        <button type="button" class="erp-btn erp-btn--sm lw-deal-btn" data-lead-customer="{{ route('admin.leads-inbox.customer.form', $lead) }}">Agregar como cliente</button>

    @else
        @unless ($hasDeals)
        <dl class="lw-kv lw-deal-client">
            <dt>Cliente</dt><dd>{{ $customerName ?: '—' }}</dd>
            <dt>Empresa</dt><dd>{{ $customer->company ?: '—' }}</dd>
            <dt>Correo</dt><dd>{{ $customer->email ?: '—' }}</dd>
            <dt>Teléfono</dt><dd>{{ $customer->phone ? \App\Support\PhoneFormat::pretty($customer->phone) : '—' }}</dd>
            @if ($customer->website)<dt>Sitio web</dt><dd>{{ $customer->website }}</dd>@endif
        </dl>
        @endunless

        <details class="lw-deal-new" data-sec="negocio-nuevo" @if (! $hasDeals || $errors->any()) open @endif>
            <summary class="lw-deal-new-h"><span class="lw-deal-plus" aria-hidden="true">+</span> Nuevo negocio</summary>
            <form method="POST" action="{{ route('admin.leads-inbox.deal.store', $lead) }}" class="lw-deal-form">
                @csrf
                <label>Nombre del negocio
                    <input class="erp-input" type="text" name="name" maxlength="255" value="{{ old('name') }}" placeholder="Negocio - {{ $customer->company ?: $customerName }}">
                </label>
                <div class="lw-deal-row">
                    <label>Monto estimado
                        <input class="erp-input" type="text" name="amount" inputmode="decimal" autocomplete="off" data-money value="{{ old('amount') }}" placeholder="0.00">
                    </label>
                    <label>Moneda
                        <select class="erp-select" name="currency">
                            <option value="MXN" @selected(old('currency', 'MXN') === 'MXN')>MXN</option>
                            <option value="USD" @selected(old('currency') === 'USD')>USD</option>
                        </select>
                    </label>
                </div>
                <label>Fecha estimada de cierre
                    <input class="erp-input" type="date" name="expected_close_date" value="{{ old('expected_close_date') }}">
                </label>
                <label>Responsable
                    <select class="erp-select" name="owner_id">
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected((string) old('owner_id', $lead->assigned_to ?: auth()->id()) === (string) $u->id)>{{ $u->first_name }} {{ $u->last_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Notas
                    <textarea class="erp-input" name="notes" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
                </label>
                <button class="erp-btn erp-btn--primary erp-btn--sm lw-deal-btn" type="submit">Crear negocio</button>
            </form>
        </details>

        @foreach ($deals as $deal)
            @php
                $linked = $deal->quotes->sortByDesc(fn ($q) => $q->pivot->id ?? $q->id)->values();
                $free = $available->whereNotIn('id', $linked->pluck('id'));
            @endphp
            <details class="lw-deal-item" data-deal="{{ $deal->id }}" @if ($loop->first) open @endif>
                <summary class="lw-deal-item-h">
                    <span class="lw-deal-folio">{{ $deal->folio }}</span>
                    <span class="lw-deal-stage">{{ $deal->stage?->name ?? '—' }}</span>
                    <strong class="lw-deal-amt">{{ $money($deal->amount, $deal->currency) }}</strong>
                </summary>
                <div class="lw-deal-item-b">
                    <dl class="lw-kv lw-deal-client">
                        <dt>Folio</dt><dd><a class="lw-mini" href="{{ route('admin.deals.show', $deal) }}">{{ $deal->folio }}</a></dd>
                        <dt>Etapa</dt><dd>{{ $deal->stage?->name ?? '—' }}</dd>
                        <dt>Monto</dt><dd><strong>{{ $money($deal->amount, $deal->currency) }}</strong></dd>
                        <dt>Cliente</dt><dd>{{ $customerName ?: '—' }}@if ($customer->company) · {{ $customer->company }}@endif</dd>
                    </dl>

                    <h4 class="lw-deal-sub">Cotizaciones ({{ $linked->count() }})</h4>
                    @forelse ($linked as $q)
                        <div class="lw-deal-quote">
                            <a class="lw-mini" href="{{ route('admin.quotes.show', $q) }}">{{ $q->quote_number }}</a>
                            <span class="lw-deal-quote-meta">{{ $money($q->total, $q->currency) }} · {{ \App\Models\Quote::statusLabel($q->status) }}</span>
                            <form method="POST" action="{{ route('admin.leads-inbox.deal.quotes.unlink', [$lead, $deal, $q]) }}" onsubmit="return confirm('¿Quitar esta cotización del negocio?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="lw-deal-x" title="Quitar del negocio" aria-label="Quitar {{ $q->quote_number }} del negocio">&times;</button>
                            </form>
                        </div>
                    @empty
                        <p class="lw-muted">Aún no hay cotizaciones ligadas. El monto del negocio se toma de la cotización más reciente.</p>
                    @endforelse

                    <form method="POST" action="{{ route('admin.leads-inbox.deal.quotes.link', [$lead, $deal]) }}" class="lw-deal-form lw-deal-link">
                        @csrf
                        <label>Ligar una cotización
                            <select class="erp-select" name="quote_id">
                                <option value="">{{ $free->count() ? 'Elegir cotización del cliente…' : 'Sin cotizaciones disponibles' }}</option>
                                @foreach ($free as $q)
                                    <option value="{{ $q->id }}">{{ $q->quote_number }} · {{ $money($q->total, $q->currency) }} · {{ \App\Models\Quote::statusLabel($q->status) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <input class="erp-input" type="text" name="quote_number" maxlength="40" placeholder="o escribe el folio (COT-2026-0001)" aria-label="Folio de la cotización">
                        <button class="erp-btn erp-btn--sm lw-deal-btn" type="submit">Ligar cotización</button>
                    </form>

                    <button type="button" class="erp-btn erp-btn--primary erp-btn--sm lw-deal-btn" data-lead-quote="{{ route('admin.quotes.create', ['customer_id' => $lead->customer_id, 'deal_id' => $deal->id, 'embed' => 1]) }}">+ Crear cotización</button>
                </div>
            </details>
        @endforeach
    @endif
</div>
</details>
