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
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'slug'                 => $this->slug,
            'product_code'         => $this->product_code,
            'edition'              => $this->edition,
            'product_publish_year' => $this->product_publish_year,
            'product_exam_year'    => $this->product_exam_year,
            'cost_price'           => $this->cost_price !== null ? (float) $this->cost_price : null,
            'mrp'                  => $this->mrp !== null ? (float) $this->mrp : null,
            'discount_type'        => $this->discount_type,
            'discount_value'       => $this->discount_value !== null ? (float) $this->discount_value : null,
            'price'                => $this->price !== null ? (float) $this->price : null,
            'short_description'    => $this->short_description,
            'description'          => $this->description,
            'image'                => $this->thumbnail_image,
            'thumbnail_url'        => $this->thumbnail_image ? image($this->thumbnail_image) : null,
            'is_active'            => (bool) $this->is_active,
            'category_id'          => $this->category_id,
            'category'             => new CategoryResource($this->whenLoaded('category')),
            'brand_id'             => $this->brand_id,
            'brand'                => $this->whenLoaded('brand', function () {
                return $this->brand ? [
                    'id'   => $this->brand->id,
                    'name' => $this->brand->name,
                ] : null;
            }),
            'subcategory_id'       => $this->subcategory_id,
            'subcategory'          => $this->whenLoaded('subcategory', function () {
                return $this->subcategory ? [
                    'id'   => $this->subcategory->id,
                    'name' => $this->subcategory->name,
                    'slug' => $this->subcategory->slug,
                ] : null;
            }),
        ];
    }
}
