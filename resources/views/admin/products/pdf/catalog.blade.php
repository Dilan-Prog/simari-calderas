{{--
    Catálogo de Productos (PDF).
    Recibe: $products — Collection de App\Models\Products con
    category.parent.parent y brand precargados (el controller es
    responsable de la query, esta vista solo consume la colección).

    El Excel de ProductsExport (app/Exports/ProductsExport.php) expone 24
    columnas. Aquí se reducen a 8 — Nombre, SKU, Categoría, Marca, Precio,
    Stock, Disponibilidad, Activo — porque en una hoja A4 (incluso en
    orientación landscape) el resto de columnas del Excel (Modelo, SKU
    Proveedor, Proveedor(es), Subcategoría/Categoría Hija por separado,
    descripciones largas, Precio Comparativo, Costo, Moneda, Destacado,
    Nuevo, Recomendado, Publicar Web, Imagen URL) no caben de forma
    legible; este PDF es una referencia rápida de catálogo, no un
    reemplazo del Excel para edición/importación masiva.
--}}
@extends('admin.pdf.layouts.catalog')

@section('title', 'Catálogo de Productos')

@section('document-label', 'Catálogo')
@section('document-title', 'Catálogo de Productos')
@section('document-meta-extra')
    <p>Total de productos: <span>{{ $products->count() }}</span></p>
@endsection

@section('footer-center', 'Catálogo generado el ' . now()->format('d/m/Y H:i'))

@section('content')
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:22%;">Nombre</th>
                <th style="width:12%;">SKU</th>
                <th style="width:20%;">Categoría</th>
                <th style="width:12%;">Marca</th>
                <th class="th-right" style="width:12%;">Precio</th>
                <th class="th-right" style="width:8%;">Stock</th>
                <th style="width:8%;">Disponibilidad</th>
                <th style="width:6%;">Activo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                @php
                    [$categoriaPrincipal, $subcategoria, $categoriaHija] = $product->category?->levelNames() ?? ['', '', ''];
                    $categoria = collect([$categoriaPrincipal, $subcategoria, $categoriaHija])
                        ->filter(fn ($nivel) => filled($nivel))
                        ->implode(' > ');
                    $disponibilidad = match ($product->availability) {
                        'out_of_stock' => 'Agotado',
                        'on_order' => 'Sobre Pedido',
                        default => 'Disponible',
                    };
                @endphp
                <tr>
                    <td class="td-name">{{ $product->name }}</td>
                    <td class="td-sku">{{ $product->sku }}</td>
                    <td>{{ $categoria ?: '—' }}</td>
                    <td>{{ $product->brand->name ?? '—' }}</td>
                    <td class="td-right">${{ number_format($product->price, 2) }}</td>
                    <td class="td-right">{{ $product->stock }}</td>
                    <td>{{ $disponibilidad }}</td>
                    <td>{{ $product->is_active ? 'Sí' : 'No' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
