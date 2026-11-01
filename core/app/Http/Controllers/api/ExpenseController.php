<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\backend\DailyVisit;
use App\Models\backend\Expense;
use App\Models\backend\ExpenseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $expenses = Expense::with([
            'items.category',
        ])
            ->where('created_by', auth()->id())
            ->when($request->filled('date'), function ($query) use ($request) {
                $query->whereDate('expense_date', $request->date);
            })
            ->latest('id')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Expenses fetched successfully.',
            'data' => ExpenseResource::collection($expenses),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'posted_at' => ['nullable', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'expense_date' => ['required', 'date'],

            'from_location' => ['nullable', 'string', 'max:255'],
            'to_location' => ['nullable', 'string', 'max:255'],

            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,posted,cancelled'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt', 'max:10240'],
            // 'attachment' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        $totalAmount = collect($request->items)->sum(function ($item) {
            return (float) $item['amount'];
        });

        $attachmentPath = null;

        // if ($request->hasFile('attachment')) {
        //     $attachmentPath = $request->file('attachment')
        //         ->store('expenses', 'public');
        // }

        if ($request->hasFile('attachment')) {
            $attachmentPath = uploadFile(
                $request->file('attachment'),
                'uploads/expense/attachments'
            );
        }

        // $attachmentPath = $request->attachment;

        $expense = DB::transaction(function () use (
            $request,
            $totalAmount,
            $attachmentPath
        ) {
            $expense = Expense::create([
                // 'posted_at' => $request->posted_at,
                'user_id' => auth()->id(),
                'posted_at' => $request->filled('posted_at') ? Carbon::parse($request->posted_at)->format('Y-m-d H:i:s') : null,
                'name' => $request->name,
                // 'expense_date' => $request->expense_date,
                'expense_date' => Carbon::parse($request->expense_date)->format('Y-m-d'),
                'from_location' => $request->from_location,
                'to_location' => $request->to_location,
                'description' => $request->description,
                'total_amount' => $totalAmount,
                'status' => $request->status,
                'attachment' => $attachmentPath,
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
            'data' => new ExpenseResource(
                $expense->load('items.category')
            ),
        ], 201);
    }
}
