@if ($product->documents->count() > 0)
<div id="documentos" class="product-documents">
    <h2 class="product-documents__title">Documentos descargables</h2>
    <div class="product-documents__grid">
        @foreach ($product->documents as $document)
            <a href="{{ $document->url }}" download class="product-documents__item">
                <div class="product-documents__icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <div class="product-documents__name">{{ $document->original_name }}</div>
                    <div class="product-documents__meta">{{ \App\Models\ProductDocument::typeLabel($document->type) }}</div>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endif
