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
    // public function toArray(Request $request): array
    // {
    //     return [
    //         'id' => $this->id,
    //         'user_id' => $this->user_id,
    //         'total_amount' => $this->total,
    //         'note' => $this->note,
    //         'created_at' => $this->created_at,
    //         'updated_at' => $this->updated_at,
    //         'status' => $this->status,
    //         'items' => SpotSaleItemResource::collection($this->whenLoaded('items')),
    //         'teacher' => new TeacherResource($this->whenLoaded('teacher')),
    //         'library' => new LibraryResource($this->whenLoaded('library')),
    //     ];
    // }

    public function toArray(Request $request): array
    {
        $customerType = $this->student_id
            ? 'student'
            : ($this->library_id ? 'library' : 'teacher');

        $customerId = match ($customerType) {
            'student' => $this->student_id,
            'library' => $this->library_id,
            default => $this->teacher_id,
        };

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,

            'customer_type' => $customerType,
            'customer_id' => $customerId,

            'teacher_id' => $this->teacher_id,
            'library_id' => $this->library_id,
            'student_id' => $this->student_id,

            'total_amount' => $this->total,
            'note' => $this->note,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'items' => SpotSaleItemResource::collection(
                $this->whenLoaded('items')
            ),

            'teacher' => $this->whenLoaded('teacher'),
            'library' => $this->whenLoaded('library'),

            'student' => $this->whenLoaded('student', function () {
                if (!$this->student) {
                    return null;
                }

                return [
                    'id' => $this->student->id,
                    'student_name' => $this->student->student_name,
                    'course_class_id' => $this->student->course_class_id,
                    'class_name' => $this->student->courseClass?->name,
                    'institution_id' => $this->student->institution_id,
                    'institution_name' => $this->student->institution?->institution_name,
                ];
            }),
        ];
    }
}
