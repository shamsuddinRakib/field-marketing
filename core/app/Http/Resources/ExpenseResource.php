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
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            // 'daily_visit_id' => $this->daily_visit_id,
            // 'branch_id' => $this->branch_id,
            // 'invoice_no' => $this->invoice_no,
            // 'expense_no' => $this->expense_no,
            'name' => $this->name,
            'expense_date' => $this->expense_date,
            'posted_at' => $this->posted_at,
            'description' => $this->description,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'attachment' => $this->attachment,

            'branch' => $this->whenLoaded('branch', function () {
                if (!$this->branch) {
                    return null;
                }

                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                ];
            }),

            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'expense_category_id' => $item->expense_category_id,
                        'description' => $item->description,
                        'amount' => $item->amount,

                        'category' => $item->category ? [
                            'id' => $item->category->id,
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
