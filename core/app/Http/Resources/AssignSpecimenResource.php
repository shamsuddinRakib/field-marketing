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
        $recipientType = $this->recipientType();

        return [
            'id'               => $this->id,
            'customer_type'    => $recipientType,
            'recipient_type'   => $recipientType,
            'assign_inst_type' => $this->assign_inst_type,
            'address'          => $this->address,
            'upazila_id'       => $this->upazila_id,
            'district_id'      => $this->district_id,
            'division_id'      => $this->division_id,
            'note'             => $this->note,
            'status'           => $this->status,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,

            'upazila' => $this->whenLoaded('upazila', function () {
                $upazila = $this->upazila ?? ($this->teacher?->upazila ?? $this->teacher?->institution?->upazila ?? $this->library?->upazila ?? $this->institution?->upazila);
                return $upazila ? [
                    'id'      => $upazila->id,
                    'name'    => $upazila->upazila_name,
                    'bn_name' => $upazila->upazila_bn_name,
                ] : null;
            }),

            'district' => $this->whenLoaded('district', function () {
                $district = $this->district ?? ($this->teacher?->district ?? $this->teacher?->institution?->district ?? $this->library?->district ?? $this->institution?->district);
                return $district ? [
                    'id'      => $district->district_id,
                    'name'    => $district->district_name,
                    'bn_name' => $district->district_bn_name,
                ] : null;
            }),

            'division' => $this->whenLoaded('division', function () {
                $division = $this->division ?? ($this->teacher?->division ?? $this->teacher?->institution?->division ?? $this->library?->division ?? $this->institution?->division);
                return $division ? [
                    'id'      => $division->id,
                    'name'    => $division->name,
                    'bn_name' => $division->bn_name,
                ] : null;
            }),

            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id'         => $item->id,
                        'product_id' => $item->product_id,
                        'quantity'   => $item->quantity,
                        'product'    => $item->product ? [
                            'id'              => $item->product->id,
                            'name'            => $item->product->name,
                            'slug'            => $item->product->slug,
                            'sku'             => $item->product->sku,
                            'price'           => $item->product->price,
                            'thumbnail_image' => $item->product->thumbnail_image,
                            'brand_id'        => $item->product->brand_id,
                        ] : null,
                    ];
                });
            }),

            'teacher' => $this->whenLoaded('teacher', function () {
                $inst = $this->teacher->institution;
                return [
                    'id'               => $this->teacher->id,
                    'name'             => $this->teacher->teacher_name,
                    'phone'            => $this->teacher->phone,
                    'designation'      => $this->teacher->designation,
                    'department'       => $this->teacher->department,
                    'institution_id'   => $this->teacher->institution_id,
                    'institution_name' => $inst?->institution_name,
                    'institution'      => $inst ? [
                        'id'      => $inst->id,
                        'name'    => $inst->institution_name,
                        'code'    => $inst->code,
                        'address' => $inst->address,
                    ] : null,
                ];
            }),

            'library' => $this->whenLoaded('library', function () {
                return [
                    'id'      => $this->library->id,
                    'name'    => $this->library->library_name,
                    'code'    => $this->library->code,
                    'phone'   => $this->library->phone,
                    'address' => $this->library->address,
                ];
            }),

            'institution' => $this->whenLoaded('institution', function () {
                return [
                    'id'      => $this->institution->id,
                    'name'    => $this->institution->institution_name,
                    'phone'   => $this->institution->phone ?? null,
                    'code'    => $this->institution->code,
                    'address' => $this->institution->address,
                ];
            }),
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
