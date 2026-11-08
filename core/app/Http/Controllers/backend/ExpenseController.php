<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\BranchAccount;
use App\Models\backend\CompanySetting;
use App\Models\backend\CurrentFund;
use App\Models\backend\Expense;
use App\Models\backend\ExpenseCategory;
use App\Models\backend\ExpenseItem;
// use App\Models\backend\ExpensePayment;
use App\Models\backend\JournalEntry;
use App\Models\backend\JournalEntryLine;
use App\Models\backend\VoucherType;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpenseController extends Controller
{
    private function ensureLocationColumnsExist(): void
    {
        if (Schema::hasTable('expense_items') && !Schema::hasColumn('expense_items', 'from_location')) {
            Schema::table('expense_items', function ($table) {
                $table->string('from_location', 255)->nullable()->after('expense_category_id');
                $table->string('to_location', 255)->nullable()->after('from_location');
            });
        }
    }

    public function index()
    {
        return view('backend.modules.expenses.index');
    }

    public function listAjax(Request $request)
    {
        $this->ensureLocationColumnsExist();

        $draw = (int) $request->input('draw');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $orderDir = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));
        $branchAll = BranchScope::isAll();
        $branchId = current_branch_id();

        $base = Expense::query()
            ->with(['items.category:id,name', 'user:id,name'])
            ->select(['id', 'user_id', 'name', 'reference', 'expense_date', 'description', 'total_amount', 'status', 'from_location', 'to_location', 'attachment']);

        if (!$branchAll && $branchId) {
            $base->where('branch_id', $branchId);
        } elseif (!$branchAll && !$branchId) {
            $base->whereRaw('1 = 0');
        }

        $total = (clone $base)->count();

        if ($searchVal !== '') {
            $base->where(function ($q) use ($searchVal) {
                $q->where('name', 'like', "%{$searchVal}%")
                    ->orWhere('reference', 'like', "%{$searchVal}%");
            });
        }

        $filtered = (clone $base)->count();

        $rows = $base
            ->orderBy('id', $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($rows as $b) {

            // category names
            $categories = $b->items
                ->pluck('category.name')
                ->filter()
                ->unique()
                ->implode(', ');

            // Unique routes for this daily expense:
            $routes = $b->items->map(function ($it) use ($b) {
                $from = $it->from_location ?: $b->from_location;
                $to = $it->to_location ?: $b->to_location;
                return ($from || $to) ? trim("{$from} ➔ {$to}") : null;
            })->filter()->unique()->values();

            $routesHtml = '';
            if ($routes->isNotEmpty()) {
                $routesHtml = '<div class="text-muted text-xs mt-1 d-flex flex-column gap-1">';
                foreach ($routes->take(3) as $r) {
                    $routesHtml .= '<div class="d-flex align-items-center gap-1"><iconify-icon icon="lucide:map-pin" class="text-primary"></iconify-icon> ' . e($r) . '</div>';
                }
                if ($routes->count() > 3) {
                    $routesHtml .= '<span class="badge bg-light text-primary border text-2xs">+' . ($routes->count() - 3) . ' more routes</span>';
                }
                $routesHtml .= '</div>';
            } elseif ($b->from_location || $b->to_location) {
                $routesHtml = '<div class="text-muted text-xs mt-1 d-flex align-items-center gap-1"><iconify-icon icon="lucide:map-pin" class="text-primary"></iconify-icon> ' . e($b->from_location ?: '—') . ' ➔ ' . e($b->to_location ?: '—') . '</div>';
            }

            $dateFormatted = $b->expense_date ? \Carbon\Carbon::parse($b->expense_date)->format('d M Y') : '';

            $statusBadge = $b->status === 'posted'
                ? '<span class="badge text-sm fw-semibold bg-dark-success-gradient px-20 py-9 radius-4 text-white">Posted</span>'
                : '<button class="badge text-sm fw-semibold bg-dark-warning-gradient px-20 py-9 radius-4 text-white btn-toggle-status"
                     data-url="' . route('expenses.toggleStatus', $b->id) . '"
                     data-id="' . $b->id . '"
                     style="border: none; cursor: pointer;">Draft</button>';

            $attachmentCell = $b->attachment
                ? '<a href="' . asset($b->attachment) . '" target="_blank" class="d-inline-flex align-items-center gap-1 text-primary text-decoration-none">
                    <iconify-icon icon="mdi:file-download-outline"></iconify-icon>
                    <span>View</span>
                  </a>'
                : '—';

            //  ACTION BUTTONS 
            $actions = '<div class="d-inline-flex align-items-center gap-1">';

            // View Details (always available)
            $actions .= '
            <a href="#"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
                      bg-info-focus text-info-main AjaxModal"
               data-ajax-modal="' . route('expenses.show', $b->id) . '"
               data-size="lg"
               title="View Details">
                <iconify-icon icon="mdi:eye"></iconify-icon>
            </a>';

            // ✏️ Edit + 🗑 Delete only for draft
            if ($b->status !== 'posted') {
                $actions .= '
            <a href="#"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
                      bg-success-focus text-success-main AjaxModal"
               data-ajax-modal="' . route('expenses.editModal', $b->id) . '"
               data-size="lg"
               data-onload="ExpenseIndex.onLoad"
               data-onsuccess="ExpenseIndex.onSaved"
               title="Edit Expense">
                <iconify-icon icon="lucide:edit"></iconify-icon>
            </a>

            <a href="#"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
                      bg-danger-focus text-danger-main btn-expense-delete"
               data-url="' . route('expenses.destroy', $b->id) . '"
               data-id="' . $b->id . '"
               title="Delete Expense">
                <iconify-icon icon="mdi:delete"></iconify-icon>
            </a>';
            }

            // Print (always available)
            $actions .= '
        <a href="' . route('expenses.invoice', $b->id) . '"
           target="_blank"
           class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
                  bg-info-focus text-info-main"
           title="Print Invoice">
            <iconify-icon icon="mdi:printer"></iconify-icon>
        </a>';

            $actions .= '</div>';

            // TABLE ROW
            $data[] = [
                $b->id,
                e($b->user->name ?? '—'),
                '<strong>' . e($b->name) . '</strong>' . ($dateFormatted ? '<div class="text-muted text-xs mt-1"><iconify-icon icon="lucide:calendar" class="me-1"></iconify-icon>' . $dateFormatted . '</div>' : ''),
                '<strong>' . e($categories ?: '—') . '</strong>' . $routesHtml,
                e($b->description ?? '—'),
                $attachmentCell,
                number_format($b->total_amount, 2),
                $statusBadge,
                $actions,
            ];
        }

        return response()->json([
            'draw' => $draw,
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $filtered,
            'aaData' => $data,
        ]);
    }

    public function createModal()
    {
        $expense_categories = ExpenseCategory::select('id', 'name')
            ->where('is_active', 1)
            ->get();

        return view('backend.modules.expenses.create_modal', compact('expense_categories')); // partial only
    }

    public function store(Request $req)
    {
        $this->ensureLocationColumnsExist();

        $data = $req->validate([
            'name' => 'required|string|max:255',
            'user_id' => 'required|integer|exists:users,id',
            'expense_date' => 'required|date',
            'from_location' => 'nullable|string|max:255',
            'to_location' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.from_location' => 'nullable|string|max:255',
            'items.*.to_location' => 'nullable|string|max:255',
            'items.*.expense_category_id' => 'required|integer|exists:expense_categories,id',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
            'status' => 'required|in:draft,posted',
            'attachment' => 'nullable|file|max:10240', // max 10MB
        ]);

        $userId = auth()->id();
        $totalAmount = collect($data['items'])->sum('amount');
        $attachmentPath = !empty($data['attachment']) ? uploadFile($data['attachment'], 'uploads/expense/attachments') : null;

        DB::beginTransaction();

        try {
            // Smart Auto-Merge: Check if there is already a draft daily expense for this user on this date
            $existingExpense = Expense::where('user_id', $data['user_id'])
                ->whereDate('expense_date', $data['expense_date'])
                ->where('status', 'draft')
                ->first();

            if ($existingExpense) {
                $expense = $existingExpense;

                foreach ($data['items'] as $item) {
                    ExpenseItem::create([
                        'expense_id' => $expense->id,
                        'expense_category_id' => $item['expense_category_id'],
                        'from_location' => $item['from_location'] ?? ($data['from_location'] ?? null),
                        'to_location' => $item['to_location'] ?? ($data['to_location'] ?? null),
                        'description' => $item['description'] ?? null,
                        'amount' => $item['amount'],
                    ]);
                }

                $newTotal = $expense->items()->sum('amount');
                $updateFields = [
                    'total_amount' => $newTotal,
                ];

                if ($attachmentPath) {
                    $updateFields['attachment'] = $attachmentPath;
                }
                if (!empty($data['description'])) {
                    $updateFields['description'] = $expense->description ? ($expense->description . '; ' . $data['description']) : $data['description'];
                }

                if ($data['status'] === 'posted') {
                    $updateFields['status'] = 'posted';
                    $updateFields['posted_at'] = now();

                    CurrentFund::updateOrCreate(
                        ['user_id' => $data['user_id']],
                        [
                            'balance' => DB::raw('balance - ' . $newTotal),
                            'updated_at' => now(),
                        ]
                    );
                }

                $expense->update($updateFields);
            } else {
                $firstFrom = $data['items'][0]['from_location'] ?? ($data['from_location'] ?? null);
                $firstTo = end($data['items'])['to_location'] ?? ($data['to_location'] ?? null);

                $expense = Expense::create([
                    'user_id' => $data['user_id'],
                    'posted_at' => $data['status'] === 'posted' ? now() : null,
                    'name' => $data['name'],
                    'expense_date' => $data['expense_date'],
                    'from_location' => $firstFrom,
                    'to_location' => $firstTo,
                    'description' => $data['description'] ?? null,
                    'reference' => $data['reference'] ?? null,
                    'total_amount' => $totalAmount,
                    'attachment' => $attachmentPath,
                    'status' => $data['status'],
                    'created_by' => $userId,
                ]);

                foreach ($data['items'] as $item) {
                    ExpenseItem::create([
                        'expense_id' => $expense->id,
                        'expense_category_id' => $item['expense_category_id'],
                        'from_location' => $item['from_location'] ?? ($data['from_location'] ?? null),
                        'to_location' => $item['to_location'] ?? ($data['to_location'] ?? null),
                        'description' => $item['description'] ?? null,
                        'amount' => $item['amount'],
                    ]);
                }

                if ($data['status'] === 'posted') {
                    CurrentFund::updateOrCreate(
                        ['user_id' => $data['user_id']],
                        [
                            'balance' => DB::raw('balance - ' . $totalAmount),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'msg' => 'Expense saved successfully',
                'data' => [
                    'id' => $expense->id,
                    'status' => $expense->status,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'msg' => $e->getMessage(),
            ], 500);
        }
    }

    public function editModal(Expense $expense)
    {
        // 🔒 Posted expense edit block (UI safety)
        if ($expense->status === 'posted') {
            abort(403, 'Posted expense cannot be edited');
        }

        // eager load relations needed for edit modal
        $expense->load([
            'items.category:id,name',
            'dailyVisit.user:id,name',
            'dailyVisit.teacher:id,teacher_name',
            'dailyVisit.library:id,library_name',
        ]);

        return view('backend.modules.expenses.edit_modal', compact('expense'));
    }

    public function update(Request $req, Expense $expense)
    {
        // 🔒 Posted expense cannot be modified
        if ($expense->status === 'posted') {
            return response()->json([
                'ok' => false,
                'msg' => 'Posted expense cannot be modified',
            ], 422);
        }

        $this->ensureLocationColumnsExist();

        $data = $req->validate([
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'expense_date' => 'required|date',
            'from_location' => 'nullable|string|max:255',
            'to_location' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.from_location' => 'nullable|string|max:255',
            'items.*.to_location' => 'nullable|string|max:255',
            'items.*.expense_category_id' => 'required|integer|exists:expense_categories,id',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
            'status' => 'required|in:draft,posted',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $userId = auth()->id();
        $totalAmount = collect($data['items'])->sum('amount');
        $attachmentPath = $req->hasFile('attachment') ? uploadFile($req->file('attachment'), 'uploads/expense/attachments') : $expense->attachment;

        DB::beginTransaction();

        try {
            $firstFrom = $data['items'][0]['from_location'] ?? ($data['from_location'] ?? null);
            $firstTo = end($data['items'])['to_location'] ?? ($data['to_location'] ?? null);

            $expense->update([
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'invoice_no' => $data['invoice_no'] ?? null,
                'posted_at' => $data['posted_at'] ?? null,
                'expense_date' => $data['expense_date'],
                'from_location' => $firstFrom,
                'to_location' => $firstTo,
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'attachment' => $attachmentPath,
                'total_amount' => $totalAmount,
                'status' => $data['status'],
            ]);

            /* RESET ITEMS */
            $expense->items()->delete();

            foreach ($data['items'] as $item) {
                $expense->items()->create([
                    'expense_category_id' => $item['expense_category_id'],
                    'from_location' => $item['from_location'] ?? null,
                    'to_location' => $item['to_location'] ?? null,
                    'description' => $item['description'] ?? null,
                    'amount' => $item['amount'],
                ]);
            }

            /* POSTING (JOURNAL) */
            if ($data['status'] === 'posted') {
                CurrentFund::updateOrCreate(
                    [
                        'user_id' => $data['user_id'],
                    ],
                    [
                        'balance' => DB::raw('balance - ' . $totalAmount),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'msg' => 'Expense updated successfully',
                'id' => $expense->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'msg' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Expense $expense)
    {
        $expense->load([
            'user:id,name,phone',
            'branch:id,name',
            'creator:id,name',
            'items.category:id,name',
        ]);

        return view('backend.modules.expenses.show', compact('expense'));
    }

    public function invoice(Expense $expense)
    {
        $expense->load([
            'items.category',
            // 'payment.paymentType',
            'branch',
            'creator',

        ]);

        $companySetting = CompanySetting::first();

        return view(
            'backend.modules.expenses.invoice',
            compact('expense', 'companySetting')
        );
    }

    public function destroy(Expense $expense)
    {
        if ($expense->status === 'posted') {
            return response()->json(['ok' => false, 'msg' => 'Approved transaction cant be deleted'], 422);
        }
        //delete attachment file if exists
        if ($expense->attachment && file_exists(public_path($expense->attachment))) {
            unlink(public_path($expense->attachment));
        }
        $expense->delete();

        return response()->json(['ok' => true, 'msg' => 'Expense deleted']);
    }

    public function select2(Request $r)
    {

        $q = trim($r->input('q', ''));
        $type = $r->type;
        $base = Expense::query()->where('category_type_id', $type)->where('is_active', 1);

        if ($q !== '') {
            $base->where(function ($x) use ($q) {
                $x->where('name', 'like', "%{$q}%");
            });
        }

        $items = $base->orderBy('id')->orderBy('name')
            ->limit(20)->get(['id', 'name']);

        return response()->json([
            'results' => $items->map(fn($t) => [
                'id' => $t->id,
                'text' => $t->name,
            ]),
        ]);
    }

    /**
     * Toggle expense status from draft to posted
     */
    public function toggleStatus(Expense $expense)
    {
        try {
            // Check if already posted
            if ($expense->status === 'posted') {
                return response()->json([
                    'ok' => false,
                    'msg' => 'Expense is already posted',
                ], 422);
            }


            $userId = auth()->id();

            DB::beginTransaction();

            // Update status
            $expense->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            CurrentFund::updateOrCreate(
                [
                    'user_id' => $expense->user_id,
                ],
                [
                    'balance' => DB::raw('balance - ' . $expense->total_amount),
                    'updated_at' => now(),
                ]
            );

            DB::commit();

            return response()->json([
                'ok' => true,
                'msg' => 'Expense posted successfully',
                'data' => [
                    'id' => $expense->id,
                    'status' => $expense->status,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'msg' => $e->getMessage(),
            ], 500);
        }
    }

    protected function assertExpenseCategoriesAreAccessible(array $categoryIds): void
    {
        $categoryIds = collect($categoryIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($categoryIds->isEmpty() || BranchScope::isAll()) {
            return;
        }

        // $branchId = current_branch_id();

        // if (! $branchId) {
        //     abort(422, 'Please select a branch first');
        // }

        $matchedCount = ExpenseCategory::query()
            ->whereIn('id', $categoryIds)
            ->count();

        abort_if($matchedCount !== $categoryIds->count(), 422, 'Invalid expense category selected for this branch');
    }
}
