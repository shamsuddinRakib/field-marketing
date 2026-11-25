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
        return [
            'id' => $this->id,
            'marketing_representative_id' => $this->marketing_representative_id,
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'slug' => $this->product?->slug,
                'brand_id' => $this->product?->brand_id,
            ],
            'return_type' => $this->return_type ?? 'specimen',
            'return_type_label' => $this->typeLabel(),
            'returned_quantity' => (int) $this->returned_quantity,
            'note' => $this->note,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'received_by' => $this->received_by,
            'received_date' => $this->received_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
