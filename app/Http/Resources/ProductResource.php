<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'product_type' => $this->product_type,
            'brand' => $this->brand,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'promotional_price' => $this->promotional_price,
            'active' => $this->active,
            'weight' => $this->weight,
            'height' => $this->height,
            'width' => $this->width,
            'length' => $this->length,
            // Presente quando a query usa withSum('stocks as total_stock', 'quantity')
            'total_stock' => $this->when(
                array_key_exists('total_stock', $this->resource->getAttributes()),
                fn () => (int) ($this->total_stock ?? 0)
            ),
            'stocks' => ProductStockResource::collection($this->whenLoaded('stocks')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
