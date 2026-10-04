<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\DailyVisit;
use App\Models\backend\Expense;
use App\Models\backend\ExpenseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'daily_visit_id' => [
                'required',
                'integer',
                'exists:daily_visits,id',
            ],

            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'invoice_no' => ['nullable', 'string', 'max:255'],
            'posted_at' => ['nullable', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'expense_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,posted,cancelled'],
            'attachment' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        $visit = DailyVisit::where('id', $request->daily_visit_id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$visit) {
            return response()->json([
                'success' => false,
                'message' => 'The selected visit does not belong to you.',
            ], 422);
        }

        $totalAmount = collect($request->items)
            ->sum(function ($item) {
                return (float) $item['amount'];
            });

        $expense = DB::transaction(function () use (
            $request,
            $totalAmount
        ) {

            $expense = Expense::create([
                'daily_visit_id' => $request->daily_visit_id,
                'branch_id' => $request->branch_id,
                'invoice_no' => $request->invoice_no,
                'posted_at' => $request->posted_at,
                'name' => $request->name,
                'expense_date' => $request->expense_date,
                'description' => $request->description,
                'total_amount' => $totalAmount,
                'status' => $request->status,
                'attachment' => $request->attachment,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {

                ExpenseItem::create([
                    'expense_id' => $expense->id,
                    'expense_category_id' => $item['expense_category_id'],
                    'description' => $item['description'] ?? null,
                    'amount' => $item['amount'],
                ]);
            }

            return $expense;
        });

        return response()->json([
            'success' => true,
            'message' => 'Expense created successfully.',
            'data' => $expense->load([
                'items.category',
                'branch',
            ]),
        ], 201);
    }
}
