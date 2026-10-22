@extends('admin.layouts.embed')

@section('title', 'Cotización creada')

@section('content')
<div style="font-family:'Inter',sans-serif;padding:48px 24px;text-align:center;">
    <h1 style="font-size:20px;margin:0 0 8px;">Cotización {{ $quote->quote_number }} creada</h1>
    <p style="color:#6B7280;margin:0 0 20px;">Se guardó correctamente.</p>
    <button type="button" id="quoteSavedClose" class="btn-primary">Cerrar</button>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var payload = {
        type: 'equiterm:quote-saved',
        quote_id: {{ (int) $quote->id }},
        quote_number: @json($quote->quote_number),
        total: {{ (float) $quote->total }},
        currency: @json($quote->currency ?: 'MXN')
    };
    function notify() {
        if (window.parent && window.parent !== window) {
            window.parent.postMessage(payload, window.location.origin);
        }
    }
    document.getElementById('quoteSavedClose').addEventListener('click', notify);
    notify();
})();
</script>
@endpush
