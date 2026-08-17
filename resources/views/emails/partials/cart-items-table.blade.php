<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;">
    @foreach ($cart->items as $item)
        @php
            $product = $item->product;
            $image = $product?->cover_image_url ?: optional($product?->images()->orderBy('sort_order')->first())->url;
            $unitPrice = (float) $item->unit_price_snapshot;
            $lineTotal = $item->lineTotal();
        @endphp
        <tr>
            <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;width:64px;" valign="top">
                @if ($image)
                    <img src="{{ $image }}" alt="{{ $product?->name }}" width="64" height="64" style="width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;display:block;">
                @else
                    <div style="width:64px;height:64px;border-radius:6px;background:#f2f3f5;"></div>
                @endif
            </td>
            <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;" valign="top">
                <div style="font-size:14px;font-weight:700;color:#141516;">{{ $product?->name ?? 'Producto' }}</div>
                <div style="font-size:12.5px;color:#6b7280;margin-top:4px;">Cantidad: {{ $item->quantity }} &middot; Precio unitario: ${{ number_format($unitPrice, 2) }}</div>
            </td>
            <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;text-align:right;white-space:nowrap;" valign="top">
                <div style="font-size:14px;font-weight:700;color:#141516;">${{ number_format($lineTotal, 2) }}</div>
            </td>
        </tr>
    @endforeach
    <tr>
        <td colspan="2" style="padding:14px 14px 0 0;text-align:right;font-size:13px;font-weight:700;color:#4b5563;">Total del carrito</td>
        <td style="padding:14px 0 0;text-align:right;font-size:16px;font-weight:800;color:#ff6213;white-space:nowrap;">${{ number_format($cart->total(), 2) }}</td>
    </tr>
</table>
