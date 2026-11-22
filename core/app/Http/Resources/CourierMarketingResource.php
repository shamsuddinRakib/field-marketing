<?php

namespace App\Http\Resources;

use App\Models\backend\CourierMarketing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourierMarketingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalQuantity = $this->relationLoaded('items')
            ? (int) $this->items->sum('quantity')
            : (int) ($this->quantity ?? 0);

        $totalReceivedQuantity = $this->relationLoaded('items')
            ? (int) $this->items->sum(fn ($item) => (int) ($item->received_quantity ?? 0))
            : null;

        return [
            'id'                          => $this->id,
            'marketing_representative_id' => (int) $this->marketing_representative_id,
            'teacher_id'                  => $this->teacher_id ? (int) $this->teacher_id : null,
            'institution_id'              => $this->institution_id ? (int) $this->institution_id : null,
            'courier_company'             => $this->courier_company,
            'request_date'                => $this->request_date?->toDateString(),
            'dispatch_date'               => $this->dispatch_date?->toDateString(),
            'delivery_date'               => $this->delivery_date?->toDateString(),
            'status'                      => $this->status,
            'status_label'                => CourierMarketing::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status)),
            'total_quantity'              => $totalQuantity,
            'total_received_quantity'     => $totalReceivedQuantity,
            'note'                        => $this->note,
            'received_at'                 => $this->received_at?->toIso8601String(),
            'created_at'                  => $this->created_at?->toIso8601String(),
            'updated_at'                  => $this->updated_at?->toIso8601String(),

            'representative' => $this->whenLoaded('representative', function () {
                if (!$this->representative) {
                    return null;
                }

                return [
                    'id'          => $this->representative->id,
                    'user_id'     => $this->representative->user_id ? (int) $this->representative->user_id : null,
                    'employee_id' => $this->representative->employee_id,
                    'name'        => $this->representative->user?->name,
                    'phone'       => $this->representative->user?->phone,
                ];
            }),

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
                    'subject'          => $this->teacher->subject,
                    'address'          => $this->teacher->address ?: $inst?->address,
                    'institution_id'   => $this->teacher->institution_id,
                    'institution_name' => $inst?->institution_name,
                    'upazila'          => $this->formatUpazila($this->teacher->upazila ?? $inst?->upazila),
                    'district'         => $this->formatDistrict($this->teacher->district ?? $inst?->district),
                    'division'         => $this->formatDivision($this->teacher->division ?? $inst?->division),
                ];
            }),

            'institution' => $this->whenLoaded('institution', function () {
                if (!$this->institution) {
                    return null;
                }

                return [
                    'id'       => $this->institution->id,
                    'name'     => $this->institution->institution_name,
                    'code'     => $this->institution->code,
                    'address'  => $this->institution->address,
                    'upazila'  => $this->formatUpazila($this->institution->upazila),
                    'district' => $this->formatDistrict($this->institution->district),
                    'division' => $this->formatDivision($this->institution->division),
                ];
            }),

            'items' => CourierMarketingItemResource::collection($this->whenLoaded('items')),
        ];
    }

    private function formatUpazila($upazila): ?array
    {
        return $upazila ? [
            'id'      => $upazila->id,
            'name'    => $upazila->upazila_name,
            'bn_name' => $upazila->upazila_bn_name,
        ] : null;
    }

    private function formatDistrict($district): ?array
    {
        return $district ? [
            'id'      => $district->district_id,
            'name'    => $district->district_name,
            'bn_name' => $district->district_bn_name,
        ] : null;
    }

    private function formatDivision($division): ?array
    {
        return $division ? [
            'id'      => $division->id,
            'name'    => $division->name,
            'bn_name' => $division->bn_name,
        ] : null;
    }
}
