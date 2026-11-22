<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourierMarketingItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'courier_marketing_id' => $this->courier_marketing_id,
            'product_id'           => $this->product_id,
            'quantity'             => $this->quantity !== null ? (int) $this->quantity : 0,
            'received_quantity'    => $this->received_quantity !== null ? (int) $this->received_quantity : null,
            'product'              => $this->whenLoaded('product', function () {
                if (!$this->product) {
                    return null;
                }

                return [
                    'id'              => $this->product->id,
                    'name'            => $this->product->name,
                    'slug'            => $this->product->slug,
                    'product_code'    => $this->product->product_code,
                    'edition'         => $this->product->edition,
                    'price'           => $this->product->price !== null ? (float) $this->product->price : null,
                    'thumbnail_image' => $this->product->thumbnail_image,
                    'thumbnail_url'   => $this->product->thumbnail_image ? image($this->product->thumbnail_image) : null,
                    'brand_id'        => $this->product->brand_id,
                ];
            }),
        ];
    }
}

