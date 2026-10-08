<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignSpecimenResource extends JsonResource
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
            'quantity' => $this->quantity,
            'note' => $this->note,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'slug' => $this->product->slug,
                    'sku' => $this->product->sku,
                    'price' => $this->product->price,
                    'thumbnail_image' => $this->product->thumbnail_image,
                    'brand_id' => $this->product->brand_id,
                ];
            }),

            'teacher' => $this->whenLoaded('teacher', function () {
                return [
                    'id' => $this->teacher->id,
                    'name' => $this->teacher->teacher_name,
                    'designation' => $this->teacher->designation,
                    'department' => $this->teacher->department,
                    'institution_id' => $this->teacher->institution_id,
                ];
            }),

            'library' => $this->whenLoaded('library', function () {
                return [
                    'id' => $this->library->id,
                    'name' => $this->library->library_name,
                    'code' => $this->library->code,
                    'phone' => $this->library->phone,
                    'address' => $this->library->address,
                ];
            }),

            'institution' => $this->whenLoaded('institution', function () {
                return [
                    'id' => $this->institution->id,
                    'name' => $this->institution->institution_name,
                    'code' => $this->institution->code,
                    'phone' => $this->institution->phone,
                    'address' => $this->institution->address,
                ];
            }),

            'recipient_type' => $this->recipientType(),
        ];
    }

    private function recipientType(): ?string
    {
        if ($this->teacher_id) {
            return 'teacher';
        }

        if ($this->institution_id) {
            return 'institution';
        }

        if ($this->library_id) {
            return 'library';
        }

        return null;
    }
}
