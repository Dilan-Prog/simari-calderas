{{-- Servicio ID de la cotización (página de servicio web). Vars: $quote --}}
@php
    $sidUser = auth()->user();
    $sidCanEdit = $sidUser && $sidUser->hasPermission('quotes', 'edit');
    $sidPage = $quote->service_page_id ? ($quote->servicePage ?? \App\Models\ServicePage::find($quote->service_page_id)) : null;
@endphp
<div class="quote-service-id" style="margin-top:8px;display:flex;flex-wrap:wrap;gap:8px 12px;align-items:flex-start;font-size:13px;">
    <span style="padding-top:7px;">
        <strong>Servicio ID:</strong>
        <span id="quoteServiceIdText">
            @if ($sidPage)
                <a href="{{ url($sidPage->publicPath()) }}" target="_blank" rel="noopener">#{{ $sidPage->id }} · {{ $sidPage->name }}</a>
            @else
                <span style="color:#6B7280;">Sin asignar</span>
            @endif
        </span>
    </span>
    @if ($sidCanEdit)
        <div id="quoteServiceIdPicker" style="min-width:260px;">
            @include('admin.service-migration._picker', [
                'servicePage' => $sidPage,
                'saveUrl' => route('admin.quotes.service-page', $quote),
                'sync' => true,
            ])
            <span style="font-size:11px;color:#6B7280;">Se copia a sus servicios técnicos y reportes que aún no tengan uno.</span>
        </div>
        <script>
        document.addEventListener('smp:saved', function (e) {
            var box = document.getElementById('quoteServiceIdText');
            if (!box || !e.detail) return;
            box.textContent = '';
            if (e.detail.service_page_id) {
                var a = document.createElement('a');
                a.href = e.detail.url || '#'; a.target = '_blank'; a.rel = 'noopener';
                a.textContent = e.detail.label;
                box.appendChild(a);
            } else {
                var s = document.createElement('span');
                s.style.color = '#6B7280'; s.textContent = 'Sin asignar';
                box.appendChild(s);
            }
        });
        </script>
    @endif
</div>
