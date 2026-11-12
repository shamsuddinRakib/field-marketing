<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\backend\CurrentFund;
use App\Models\backend\DailyVisit;
use App\Models\backend\Expense;
use App\Models\backend\ExpenseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /**
     * Get list of expenses for the authenticated MR
     */
    public function index(Request $request)
    {
        $request->validate([
            'date'   => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,posted,cancelled'],
        ]);

        $query = Expense::with(['items.category', 'branch'])
            ->where(function ($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('created_by', auth()->id());
            })
            ->when($request->filled('date'), function ($q) use ($request) {
                $q->whereDate('expense_date', $request->date);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('from_location', 'like', "%{$search}%")
                        ->orWhere('to_location', 'like', "%{$search}%");
                });
            });

        $expenses = $query->latest('id')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Expenses fetched successfully.',
            'data'    => ExpenseResource::collection($expenses),
            'meta'    => [
                'current_page' => $expenses->currentPage(),
                'last_page'    => $expenses->lastPage(),
                'per_page'     => $expenses->perPage(),
                'total'        => $expenses->total(),
            ],
        ]);
    }

    /**
     * Store a new expense entry or auto-merge into existing draft
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                        => ['required', 'string', 'max:255'],
            'expense_date'                => ['required', 'date'],
            'from_location'               => ['nullable', 'string', 'max:255'],
            'to_location'                 => ['nullable', 'string', 'max:255'],
            'reference'                   => ['nullable', 'string', 'max:255'],
            'description'                 => ['nullable', 'string'],
            'status'                      => ['required', 'in:draft,posted'],
            'attachment'                  => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt', 'max:10240'],
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'items.*.from_location'       => ['nullable', 'string', 'max:255'],
            'items.*.to_location'         => ['nullable', 'string', 'max:255'],
            'items.*.description'         => ['nullable', 'string'],
            'items.*.amount'              => ['required', 'numeric', 'min:0.01'],
        ]);

        $userId = auth()->id();
        $expenseDate = Carbon::parse($data['expense_date'])->format('Y-m-d');
        $totalAmount = collect($data['items'])->sum(fn($it) => (float) $it['amount']);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = uploadFile($request->file('attachment'), 'uploads/expense/attachments');
        }

        $expense = DB::transaction(function () use ($data, $userId, $expenseDate, $totalAmount, $attachmentPath) {
            // Smart Auto-Merge: Check if there is already a draft daily expense for this user on this date
            $existingExpense = Expense::where('user_id', $userId)
                ->whereDate('expense_date', $expenseDate)
                ->where('status', 'draft')
                ->first();

            if ($existingExpense) {
                // Append items to existing draft
                foreach ($data['items'] as $item) {
                    ExpenseItem::create([
                        'expense_id'          => $existingExpense->id,
                        'expense_category_id' => $item['expense_category_id'],
                        'from_location'       => $item['from_location'] ?? ($data['from_location'] ?? null),
                        'to_location'         => $item['to_location'] ?? ($data['to_location'] ?? null),
                        'description'         => $item['description'] ?? null,
                        'amount'              => $item['amount'],
                    ]);
                }

                $newTotal = $existingExpense->items()->sum('amount');
                $updateFields = [
                    'total_amount' => $newTotal,
                ];

                if ($attachmentPath) {
                    $updateFields['attachment'] = $attachmentPath;
                }

                if (!empty($data['description'])) {
                    $updateFields['description'] = $existingExpense->description
                        ? ($existingExpense->description . '; ' . $data['description'])
                        : $data['description'];
                }

                $lastTo = end($data['items'])['to_location'] ?? ($data['to_location'] ?? null);
                if ($lastTo) {
                    $updateFields['to_location'] = $lastTo;
                }

                if ($data['status'] === 'posted') {
                    $updateFields['status'] = 'posted';
                    $updateFields['posted_at'] = now();

                    CurrentFund::updateOrCreate(
                        ['user_id' => $userId],
                        [
                            'balance'    => DB::raw('balance - ' . $newTotal),
                            'updated_at' => now(),
                        ]
                    );
                }

                $existingExpense->update($updateFields);
                return $existingExpense;
            }

            // Create new expense
            $firstFrom = $data['items'][0]['from_location'] ?? ($data['from_location'] ?? null);
            $firstTo = end($data['items'])['to_location'] ?? ($data['to_location'] ?? null);

            $expense = Expense::create([
                'user_id'       => $userId,
                'posted_at'     => $data['status'] === 'posted' ? now() : null,
                'name'          => $data['name'],
                'expense_date'  => $expenseDate,
                'from_location' => $firstFrom,
                'to_location'   => $firstTo,
                'description'   => $data['description'] ?? null,
                'reference'     => $data['reference'] ?? null,
                'total_amount'  => $totalAmount,
                'attachment'    => $attachmentPath,
                'status'        => $data['status'],
                'created_by'    => $userId,
            ]);

            foreach ($data['items'] as $item) {
                ExpenseItem::create([
                    'expense_id'          => $expense->id,
                    'expense_category_id' => $item['expense_category_id'],
                    'from_location'       => $item['from_location'] ?? ($data['from_location'] ?? null),
                    'to_location'         => $item['to_location'] ?? ($data['to_location'] ?? null),
                    'description'         => $item['description'] ?? null,
                    'amount'              => $item['amount'],
                ]);
            }

            if ($data['status'] === 'posted') {
                CurrentFund::updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'balance'    => DB::raw('balance - ' . $totalAmount),
                        'updated_at' => now(),
                    ]
                );
            }

            return $expense;
        });

        return response()->json([
            'success' => true,
            'message' => 'Expense saved successfully.',
            'data'    => new ExpenseResource($expense->load('items.category')),
        ], 201);
    }

    /**
     * Show single expense details
     */
    public function show($id)
    {
        $expense = Expense::with(['items.category', 'branch'])
            ->where(function ($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('created_by', auth()->id());
            })
            ->where('id', $id)
            ->first();

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Expense fetched successfully.',
            'data'    => new ExpenseResource($expense),
        ]);
    }

    /**
     * Update an existing draft expense
     */
    public function update(Request $request, $id)
    {
        $expense = Expense::where(function ($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('created_by', auth()->id());
            })
            ->where('id', $id)
            ->first();

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found.',
            ], 404);
        }

        if (in_array($expense->status, ['posted', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => ucfirst($expense->status) . ' expense cannot be modified.',
            ], 422);
        }

        $data = $request->validate([
            'name'                        => ['required', 'string', 'max:255'],
            'expense_date'                => ['required', 'date'],
            'from_location'               => ['nullable', 'string', 'max:255'],
            'to_location'                 => ['nullable', 'string', 'max:255'],
            'reference'                   => ['nullable', 'string', 'max:255'],
            'description'                 => ['nullable', 'string'],
            'status'                      => ['required', 'in:draft,posted,cancelled'],
            'attachment'                  => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt', 'max:10240'],
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'items.*.from_location'       => ['nullable', 'string', 'max:255'],
            'items.*.to_location'         => ['nullable', 'string', 'max:255'],
            'items.*.description'         => ['nullable', 'string'],
            'items.*.amount'              => ['required', 'numeric', 'min:0.01'],
        ]);

        $userId = auth()->id();
        $expenseDate = Carbon::parse($data['expense_date'])->format('Y-m-d');
        $totalAmount = collect($data['items'])->sum(fn($it) => (float) $it['amount']);

        $attachmentPath = $request->hasFile('attachment')
            ? uploadFile($request->file('attachment'), 'uploads/expense/attachments')
            : $expense->attachment;

        $expense = DB::transaction(function () use ($expense, $data, $userId, $expenseDate, $totalAmount, $attachmentPath) {
            $firstFrom = $data['items'][0]['from_location'] ?? ($data['from_location'] ?? null);
            $firstTo = end($data['items'])['to_location'] ?? ($data['to_location'] ?? null);

            $expense->update([
                'name'          => $data['name'],
                'expense_date'  => $expenseDate,
                'from_location' => $firstFrom,
                'to_location'   => $firstTo,
                'reference'     => $data['reference'] ?? null,
                'description'   => $data['description'] ?? null,
                'attachment'    => $attachmentPath,
                'total_amount'  => $totalAmount,
                'status'        => $data['status'],
                'posted_at'     => $data['status'] === 'posted' ? now() : null,
            ]);

            // Reset and recreate items
            $expense->items()->delete();

            foreach ($data['items'] as $item) {
                $expense->items()->create([
                    'expense_category_id' => $item['expense_category_id'],
                    'from_location'       => $item['from_location'] ?? null,
                    'to_location'         => $item['to_location'] ?? null,
                    'description'         => $item['description'] ?? null,
                    'amount'              => $item['amount'],
                ]);
            }

            if ($data['status'] === 'posted') {
                CurrentFund::updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'balance'    => DB::raw('balance - ' . $totalAmount),
                        'updated_at' => now(),
                    ]
                );
            }

            return $expense;
        });

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully.',
            'data'    => new ExpenseResource($expense->load('items.category')),
        ]);
    }

    /**
     * Delete a draft expense
     */
    public function destroy($id)
    {
        $expense = Expense::where(function ($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('created_by', auth()->id());
            })
            ->where('id', $id)
            ->first();

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found.',
            ], 404);
        }

        if ($expense->status === 'posted') {
            return response()->json([
                'success' => false,
                'message' => 'Posted expense cannot be deleted.',
            ], 422);
        }

        DB::transaction(function () use ($expense) {
            if ($expense->attachment && file_exists(public_path($expense->attachment))) {
                @unlink(public_path($expense->attachment));
            }
            $expense->items()->delete();
            $expense->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
        ]);
    }

    /**
     * Post a draft expense and deduct from CurrentFund
     */
    public function postExpense($id)
    {
        $expense = Expense::where(function ($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('created_by', auth()->id());
            })
            ->where('id', $id)
            ->first();

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found.',
            ], 404);
        }

        if (in_array($expense->status, ['posted', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => ucfirst($expense->status) . ' expense cannot be posted.',
            ], 422);
        }

        DB::transaction(function () use ($expense) {
            $expense->update([
                'status'    => 'posted',
                'posted_at' => now(),
            ]);

            CurrentFund::updateOrCreate(
                ['user_id' => $expense->user_id],
                [
                    'balance'    => DB::raw('balance - ' . $expense->total_amount),
                    'updated_at' => now(),
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Expense posted successfully.',
            'data'    => new ExpenseResource($expense->load('items.category')),
        ]);
    }

    /* =========================================================================
     *  OLD EXPENSE CODE (COMMENTED FOR REVIEW)
     * =========================================================================
    public function old_index(Request $request)
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

    public function old_store(Request $request)
    {
        $request->validate([
            'posted_at' => ['nullable', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'expense_date' => ['required', 'date'],
            'from_location' => ['nullable', 'string', 'max:255'],
            'to_location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,posted,cancelled'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt', 'max:10240'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        $totalAmount = collect($request->items)->sum(function ($item) {
            return (float) $item['amount'];
        });

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = uploadFile(
                $request->file('attachment'),
                'uploads/expense/attachments'
            );
        }

        $expense = DB::transaction(function () use (
            $request,
            $totalAmount,
            $attachmentPath
        ) {
            $expense = Expense::create([
                'user_id' => auth()->id(),
                'posted_at' => $request->filled('posted_at') ? Carbon::parse($request->posted_at)->format('Y-m-d H:i:s') : null,
                'name' => $request->name,
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
    ========================================================================= */
}
