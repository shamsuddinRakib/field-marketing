<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstituitionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $name = $this->institution_name ?: $this->name;

        return [
            'id' => $this->id,
            'name' => $name,
            'code' => $this->code,
            'email' => $this->email,
            'address' => $this->address,
            'assign_inst_type' => $this->autoDetectInstType($name),

            'upazila_id' => $this->upazila_id,
            'upazila_name' => $this->upazila?->upazila_name,

            'district_id' => $this->district_id,
            'district_name' => $this->district?->district_name,

            'division_id' => $this->division_id,
            'division_name' => $this->division?->name,
        ];
    }

    private function autoDetectInstType(?string $name): string
    {
        if (!$name) {
            return 'school';
        }

        $lower = mb_strtolower($name);
        if (str_contains($lower, 'college') || str_contains($lower, 'কলেজ')) {
            return 'college';
        }
        if (str_contains($lower, 'school') || str_contains($lower, 'স্কুল') || str_contains($lower, 'বিদ্যালয়') || str_contains($lower, 'মাদ্রাসা') || str_contains($lower, 'madrasah')) {
            return 'school';
        }
        if (str_contains($lower, 'coaching') || str_contains($lower, 'কোচিং')) {
            return 'coaching';
        }
        if (str_contains($lower, 'batch') || str_contains($lower, 'ব্যাচ')) {
            return 'batch';
        }

        return 'school';
    }
}
