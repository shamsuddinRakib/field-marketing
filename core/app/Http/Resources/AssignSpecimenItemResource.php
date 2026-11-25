<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignSpecimenItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'assign_specimen_id' => $this->assign_specimen_id ? (int) $this->assign_specimen_id : null,
            'product_id'         => $this->product_id ? (int) $this->product_id : null,
            'quantity'           => $this->quantity !== null ? (int) $this->quantity : 0,
            'current_stock'      => (int) (\App\Models\backend\CurrentStock::where('user_id', auth('sanctum')->id())->where('product_id', $this->product_id)->value('current_stock') ?? 0),
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
            'product'            => $this->whenLoaded('product', function () {
                if (!$this->product) {
                    return null;
                }

                return [
                    'id'              => $this->product->id,
                    'name'            => $this->product->name,
                    'slug'            => $this->product->slug,
                    // 'sku'             => $this->product->sku,
                    'product_code'    => $this->product->product_code,
                    'edition'         => $this->product->edition,
                    'price'           => $this->product->price !== null ? (float) $this->product->price : null,
                    'current_stock'   => (int) (\App\Models\backend\CurrentStock::where('user_id', auth('sanctum')->id())->where('product_id', $this->product_id)->value('current_stock') ?? 0),
                    'thumbnail_image' => $this->product->thumbnail_image,
                    'thumbnail_url'   => $this->product->thumbnail_image ? image($this->product->thumbnail_image) : null,
                    'brand_id'        => $this->product->brand_id ? (int) $this->product->brand_id : null,
                    'brand'           => $this->product->relationLoaded('brand') && $this->product->brand ? [
                        'id'   => $this->product->brand->id,
                        'name' => $this->product->brand->name,
                    ] : null,
                    'category_id'     => $this->product->category_id ? (int) $this->product->category_id : null,
                    'category'        => $this->product->relationLoaded('category') && $this->product->category ? [
                        'id'   => $this->product->category->id,
                        'name' => $this->product->category->name,
                        'slug' => $this->product->category->slug,
                    ] : null,
                    'subcategory_id'  => $this->product->subcategory_id ? (int) $this->product->subcategory_id : null,
                    'subcategory'     => $this->product->relationLoaded('subcategory') && $this->product->subcategory ? [
                        'id'   => $this->product->subcategory->id,
                        'name' => $this->product->subcategory->name,
                        'slug' => $this->product->subcategory->slug,
                    ] : null,
                ];
            }),
        ];
    }
}

