<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // public function toArray(Request $request): array
    // {
    //     return [
    //         'id' => $this->id,
    //         'name' => $this->teacher_name,
    //         'phone' => $this->phone,
    //         'address' => $this->address,
    //         'created_at' => $this->created_at,
    //         'updated_at' => $this->updated_at,
    //     ];
    // }


    public function toArray(Request $request): array
    {
        $institution = $this->institution;

        return [
            'id' => $this->id,
            'name' => $this->teacher_name,
            'phone' => $this->phone,
            'designation' => $this->designation,
            'department' => $this->department,
            'subject' => $this->subject,
            'class_name' => $this->class_name,

            'institution' => $institution ? [
                'id' => $institution->id,
                'name' => $institution->institution_name,
                'code' => $institution->code,
            ] : null,

            'upazila' => [
                'id' => $this->upazila_id ?: $institution?->upazila_id,
                'name' => $this->upazila?->upazila_name
                    ?? $institution?->upazila?->upazila_name,
            ],

            'district' => [
                'id' => $this->district_id ?: $institution?->district_id,
                'name' => $this->district?->district_name
                    ?? $institution?->district?->district_name,
            ],

            'division' => [
                'id' => $this->division_id ?: $institution?->division_id,
                'name' => $this->division?->name
                    ?? $this->division?->bn_name
                    ?? $institution?->division?->name
                    ?? $institution?->division?->bn_name,
            ],

            'address' => $this->address ?: $institution?->address,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
