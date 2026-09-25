<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fase 5 del plan de integración N8N (ver
 * C:\Users\dilon\.claude\plans\replicated-frolicking-clarke.md). Lista blanca
 * explícita de campos (no Products::toArray() crudo) para no fugar columnas
 * internas nuevas que se agreguen a la tabla sin decidir a propósito si son
 * parte del contrato público de la API.
 *
 * `stock_by_warehouse` solo aparece cuando el controller precargó la
 * relación ad-hoc `warehouseStocks` vía setRelation() (Products no tiene esa
 * relación declarada -- WarehouseProductStock se consulta aparte en
 * ProductController::show() para no tener que tocar el modelo compartido).
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'sku'                => $this->sku,
            'model'              => $this->model,
            'name'               => $this->name,
            'slug'               => $this->slug,
            'short_description'  => $this->short_description,
            'description'        => $this->description,
            'category'           => $this->whenLoaded('category', fn () => $this->category ? [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ] : null),
            'brand'              => $this->whenLoaded('brand', fn () => $this->brand ? [
                'id'   => $this->brand->id,
                'name' => $this->brand->name,
            ] : null),
            'price'              => $this->price !== null ? (float) $this->price : null,
            'compare_price'      => $this->compare_price !== null ? (float) $this->compare_price : null,
            'cost'               => $this->cost !== null ? (float) $this->cost : null,
            'price_includes_tax' => (bool) $this->price_includes_tax,
            'currency'           => $this->currency,
            'stock'              => (int) $this->stock,
            'stock_unit'         => $this->stock_unit,
            'availability'       => $this->availability,
            'availability_label' => $this->availability_label,
            'is_purchasable'     => $this->is_purchasable,
            'is_active'          => (bool) $this->is_active,
            'is_featured'        => (bool) $this->is_featured,
            'is_new'             => (bool) $this->is_new,
            'is_recommended'     => (bool) $this->is_recommended,
            'weight'             => $this->weight !== null ? (float) $this->weight : null,
            'height'             => $this->height !== null ? (float) $this->height : null,
            'width'              => $this->width !== null ? (float) $this->width : null,
            'length'             => $this->length !== null ? (float) $this->length : null,
            'cover_image_url'    => $this->cover_image_url,
            'tags'               => $this->tags,
            'stock_by_warehouse' => $this->when(
                $this->relationLoaded('warehouseStocks'),
                fn () => $this->warehouseStocks->map(fn ($stock) => [
                    'warehouse_id'   => $stock->warehouse_id,
                    'warehouse_name' => $stock->warehouse?->name,
                    'quantity'       => (int) $stock->quantity,
                ])->values()
            ),
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
