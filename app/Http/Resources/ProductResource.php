<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $threshold = config('pos.low_stock_threshold', 5);

        return [
            'id'              => $this->id,
            'category_id'     => $this->category_id,
           // 'category_name'   => $this->category->category_name ?? null, // Optional if eager loaded
            'sku'             => $this->sku,
            'product_name'    => $this->product_name,
            'description'     => $this->description,
            'price'           => $this->price,
            'stock'           => $this->stock,
            'img_product'     => $this->img_product,
            
            // Flags
            'is_out_of_stock' => $this->stock == 0,
            'is_low_stock'    => $this->stock > 0 && $this->stock < $threshold,
            'can_sell'        => $this->stock > 0,
        ];
    }
}
