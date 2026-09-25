<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fase 5 del plan de integración N8N. `product_count` solo se calcula en
 * CollectionController::show() (evita un COUNT() por fila en index()) --
 * cuando no se anexó, simplemente no aparece en el JSON (whenNotNull).
 */
class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'type'            => $this->type,
            'match_type'      => $this->match_type,
            'image_url'       => $this->image_url,
            'sort_order'      => $this->sort_order,
            'is_active'       => (bool) $this->is_active,
            'seo_title'       => $this->seo_title,
            'seo_description' => $this->seo_description,
            'product_count'   => $this->whenNotNull($this->product_count ?? null),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
