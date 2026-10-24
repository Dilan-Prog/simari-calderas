{{-- Servicio ID de la cotización (página de servicio web), como campo de la tarjeta de vínculos. Vars: $quote --}}
@php
    $sidUser = auth()->user();
    $sidCanEdit = $sidUser && $sidUser->hasPermission('quotes', 'edit');
    $sidPage = $quote->service_page_id ? ($quote->servicePage ?? \App\Models\ServicePage::find($quote->service_page_id)) : null;
@endphp
<div class="ql-field">
    <div class="ql-label">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        Servicio ID <span class="ql-label-sub">· servicio web</span>
    </div>
    <div class="ql-current" id="quoteServiceIdText">
        @if ($sidPage)
            <a class="ql-chip" href="{{ url($sidPage->publicPath()) }}" target="_blank" rel="noopener">
                <strong>#{{ $sidPage->id }}</strong><span>{{ \Illuminate\Support\Str::limit($sidPage->name, 48) }}</span>
            </a>
        @else
            <span class="ql-empty">Sin asignar</span>
        @endif
    </div>
    @if ($sidCanEdit)
        <div id="quoteServiceIdPicker">
            @include('admin.service-migration._picker', [
                'servicePage' => $sidPage,
                'saveUrl' => route('admin.quotes.service-page', $quote),
                'sync' => true,
            ])
        </div>
        <p class="ql-hint">Se copia a sus servicios técnicos y reportes que aún no tengan uno.</p>
        <script>
        document.addEventListener('smp:saved', function (e) {
            var box = document.getElementById('quoteServiceIdText');
            if (!box || !e.detail) return;
            box.textContent = '';
            if (e.detail.service_page_id) {
                var a = document.createElement('a');
                a.className = 'ql-chip';
                a.href = e.detail.url || '#'; a.target = '_blank'; a.rel = 'noopener';
                var label = String(e.detail.label || '');
                var sep = label.indexOf(' · ');
                var strong = document.createElement('strong');
                strong.textContent = sep > -1 ? label.slice(0, sep) : label;
                a.appendChild(strong);
                if (sep > -1) {
                    var span = document.createElement('span');
                    span.textContent = label.slice(sep + 3);
                    a.appendChild(span);
                }
                box.appendChild(a);
            } else {
                var s = document.createElement('span');
                s.className = 'ql-empty'; s.textContent = 'Sin asignar';
                box.appendChild(s);
            }
        });
        </script>
    @endif
</div>
