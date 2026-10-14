<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpotSaleResource extends JsonResource
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
            'user_id' => $this->user_id,
            'total_amount' => $this->total,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'status' => $this->status,
            'items' => SpotSaleItemResource::collection($this->whenLoaded('items')),
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
            'library' => new LibraryResource($this->whenLoaded('library')),
        ];
    }
}
