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
        return [
            'id' => $this->id,
            'name' => $this->institution_name,
            'code' => $this->code,
            'email' => $this->email,
            'address' => $this->address,

            'upazila_id' => $this->upazila_id,
            'upazila_name' => $this->upazila?->upazila_name,

            'district_id' => $this->district_id,
            'district_name' => $this->district?->district_name,

            'division_id' => $this->division_id,
            'division_name' => $this->division?->name,
        ];
    }
}
