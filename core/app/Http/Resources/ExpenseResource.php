<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'expense_date'  => $this->expense_date,
            'posted_at'     => $this->posted_at,
            'from_location' => $this->from_location,
            'to_location'   => $this->to_location,
            'reference'     => $this->reference,
            'description'   => $this->description,
            'total_amount'  => (float) $this->total_amount,
            'status'        => $this->status,
            'attachment'    => $this->attachment ? asset($this->attachment) : null,
            'attachment_path' => $this->attachment,

            'branch' => $this->whenLoaded('branch', function () {
                if (!$this->branch) {
                    return null;
                }

                return [
                    'id'   => $this->branch->id,
                    'name' => $this->branch->name,
                ];
            }),

            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id'                  => $item->id,
                        'expense_category_id' => $item->expense_category_id,
                        'from_location'       => $item->from_location,
                        'to_location'         => $item->to_location,
                        'description'         => $item->description,
                        'amount'              => (float) $item->amount,

                        'category' => $item->category ? [
                            'id'   => $item->category->id,
                            'name' => $item->category->name,
                        ] : null,
                    ];
                });
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
