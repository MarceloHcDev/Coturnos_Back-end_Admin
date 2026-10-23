<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProductStock */
class ProductStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'size' => $this->size,
            'quantity' => $this->quantity,
            'min_quantity' => $this->min_quantity,
            'status' => $this->status, // ok | low | out
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'brand' => $this->product->brand,
                'active' => $this->product->active,
            ]),
            'updated_at' => $this->updated_at,
        ];
    }
}
