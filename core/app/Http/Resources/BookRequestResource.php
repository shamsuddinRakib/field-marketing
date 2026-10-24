<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        // return [
        //     'id' => $this->id,
        //     'quantity' => $this->quantity,
        //     'note' => $this->note,
        //     'request_date' => $this->request_date,
        //     'status' => $this->status,

        //     'product' => $this->whenLoaded('product', function () {
        //         return [
        //             'id' => $this->product->id,
        //             'name' => $this->product->name,
        //             'slug' => $this->product->slug,
        //             'sku' => $this->product->sku,
        //             'brand_id' => $this->product->brand_id,
        //         ];
        //     }),
        // ];

        return [
            'id' => $this->id,
            'note' => $this->note,
            'request_date' => $this->request_date,
            'status' => $this->status,
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'product' => $item->relationLoaded('product') && $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'slug' => $item->product->slug,
                            'sku' => $item->product->sku,
                            'brand_id' => $item->product->brand_id,
                        ] : null,
                    ];
                });
            }),
        ];
    }
}
