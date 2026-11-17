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

        $totalQuantity = $this->relationLoaded('items')
            ? (int) $this->items->sum('quantity')
            : ($this->quantity !== null ? (int) $this->quantity : 0);

        return [
            'id'               => $this->id,
            'user_id'          => $this->user_id ? (int) $this->user_id : null,
            'customer_type'    => $recipientType,
            'recipient_type'   => $recipientType,
            'teacher_id'       => $this->teacher_id ? (int) $this->teacher_id : null,
            'library_id'       => $this->library_id ? (int) $this->library_id : null,
            'institution_id'   => isset($this->institution_id) && $this->institution_id ? (int) $this->institution_id : null,
            'assign_inst_type' => $this->assign_inst_type,
            'total_quantity'   => $totalQuantity,
            'address'          => $this->address,
            'upazila_id'       => $this->upazila_id ? (int) $this->upazila_id : null,
            'district_id'      => $this->district_id ? (int) $this->district_id : null,
            'division_id'      => $this->division_id ? (int) $this->division_id : null,
            'note'             => $this->note,
            'status'           => $this->status,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),

            'upazila' => $this->whenLoaded('upazila', function () {
                $upazila = $this->upazila ?? ($this->teacher?->upazila ?? $this->teacher?->institution?->upazila ?? $this->library?->upazila ?? ($this->institution?->upazila ?? null));
                return $upazila ? [
                    'id'      => $upazila->id,
                    'name'    => $upazila->upazila_name,
                    'bn_name' => $upazila->upazila_bn_name,
                ] : null;
            }),

            'district' => $this->whenLoaded('district', function () {
                $district = $this->district ?? ($this->teacher?->district ?? $this->teacher?->institution?->district ?? $this->library?->district ?? ($this->institution?->district ?? null));
                return $district ? [
                    'id'      => $district->district_id,
                    'name'    => $district->district_name,
                    'bn_name' => $district->district_bn_name,
                ] : null;
            }),

            'division' => $this->whenLoaded('division', function () {
                $division = $this->division ?? ($this->teacher?->division ?? $this->teacher?->institution?->division ?? $this->library?->division ?? ($this->institution?->division ?? null));
                return $division ? [
                    'id'      => $division->id,
                    'name'    => $division->name,
                    'bn_name' => $division->bn_name,
                ] : null;
            }),

            'items' => AssignSpecimenItemResource::collection($this->whenLoaded('items')),

            'teacher' => $this->whenLoaded('teacher', function () {
                if (!$this->teacher) {
                    return null;
                }

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
                if (!$this->library) {
                    return null;
                }

                return [
                    'id'      => $this->library->id,
                    'name'    => $this->library->library_name,
                    'code'    => $this->library->code,
                    'phone'   => $this->library->phone,
                    'address' => $this->library->address,
                ];
            }),

            'institution' => $this->whenLoaded('institution', function () {
                if (!$this->institution) {
                    return null;
                }

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

        if (!empty($this->institution_id)) {
            return 'institution';
        }

        if ($this->library_id) {
            return 'library';
        }

        return null;
    }
}
