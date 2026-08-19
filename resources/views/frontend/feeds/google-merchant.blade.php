<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
  <channel>
    <title>Equiterm Industries — Catálogo</title>
    <link>{{ url('/') }}</link>
    <description>Feed de productos de Equiterm Industries para Google Merchant Center.</description>
@foreach ($products as $product)
    @php
        // cover_image_url casi nunca se captura en el alta normal de un
        // producto (solo lo fija el consolidador de duplicados de la
        // Galería) — la portada real de la mayoría de los productos es la
        // primera imagen de su galería, mismo criterio ya usado en
        // admin/products/index.blade.php y en el correo de carrito.
        $mainImageUrl = $product->cover_image_url ?? $product->images->first()?->url;
        $extraImages = $product->images->where('url', '!=', $mainImageUrl)->take(10);
    @endphp
    <item>
      <g:id>{{ $product->id }}</g:id>
      <title>{{ \Illuminate\Support\Str::limit($product->resolveVariables($product->name), 150, '') }}</title>
      <description>{{ \Illuminate\Support\Str::limit($product->resolveVariables($product->description ?: $product->short_description) ?: $product->resolveVariables($product->name), 5000, '') }}</description>
      <link>{{ route('product.show', $product->slug) }}</link>
      <g:image_link>{{ $mainImageUrl }}</g:image_link>
@foreach ($extraImages as $image)
      <g:additional_image_link>{{ $image->url }}</g:additional_image_link>
@endforeach
      <g:availability>{{ $product->google_merchant_availability }}</g:availability>
      {{-- Siempre el precio final con IVA — el monto que el cliente
           realmente paga, requerido así por Google. No se manda
           price/sale_price por separado para evitar mezclar el
           "antes" (compare_price, guardado sin su propio IVA propio)
           con un precio final que sí lo lleva. --}}
      <g:price>{{ number_format($product->final_price, 2, '.', '') }} MXN</g:price>
@if ($product->brand)
      <g:brand>{{ $product->brand->name }}</g:brand>
@endif
      <g:condition>new</g:condition>
@if ($product->sku)
      <g:mpn>{{ $product->sku }}</g:mpn>
@else
      <g:identifier_exists>no</g:identifier_exists>
@endif
@if ($product->category)
      <g:product_type>{{ $product->category->parent ? $product->category->parent->name . ' > ' . $product->category->name : $product->category->name }}</g:product_type>
@endif
    </item>
@endforeach
  </channel>
</rss>
