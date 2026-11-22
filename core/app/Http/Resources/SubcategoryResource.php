<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubcategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'category_id' => $this->category_id,
            'image'       => $this->icon,
            'is_active'   => (bool) $this->is_active,
            'category'    => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
