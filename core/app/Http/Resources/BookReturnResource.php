<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,

            'institution' => [
                'id' => $this->institution?->id,
                'name' => $this->institution?->name,
                'email' => $this->institution?->email,
                'phone' => $this->institution?->phone,
                'address' => $this->institution?->address,
                'status' => $this->institution?->status,
            ],

            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'slug' => $this->product?->slug,
                'brand_id' => $this->product?->brand_id,
            ],

            'issued_quantity' => $this->issued_quantity,
            'returned_quantity' => $this->returned_quantity,
            'note' => $this->note,
            'status' => $this->status,
            'received_by' => $this->received_by,
            'received_date' => $this->received_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
