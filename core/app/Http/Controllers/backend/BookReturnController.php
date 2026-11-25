<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\BookReturn;
use App\Models\backend\CurrentStock;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookReturnController extends Controller
{
    public function index()
    {
        return view('backend.modules.book_returns.index');
    }

    public function createModal()
    {
        return view('backend.modules.book_returns.create_modal');
    }

    public function editModal(BookReturn $bookReturn)
    {
        $bookReturn->load(['representative.user', 'product']);

        return view('backend.modules.book_returns.edit_modal', compact('bookReturn'));
    }

    public function show(BookReturn $bookReturn)
    {
        $bookReturn->load(['representative.user', 'product']);

        return view('backend.modules.book_returns.show', compact('bookReturn'));
    }

    public function listAjax(Request $request)
    {
        $columns = [
            'id',
            'representative_name',
            'product_name',
            'return_type',
            'returned_quantity',
            'note',
            'status',
            'received_by',
            'received_date',
        ];
        $sortable = [
            'id' => 'book_returns.id',
            'representative_name' => 'us.name',
            'product_name' => 'p.name',
            'return_type' => 'book_returns.return_type',
            'returned_quantity' => 'book_returns.returned_quantity',
            'note' => 'book_returns.note',
            'status' => 'book_returns.status',
            'received_by' => 'book_returns.received_by',
            'received_date' => 'book_returns.received_date',
        ];

        $draw = (int) $request->input('draw');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim($request->input('search.value', ''));

        $query = BookReturn::query()
            ->leftJoin('marketing_representatives as mr', 'mr.id', '=', 'book_returns.marketing_representative_id')
            ->leftJoin('users as us', 'us.id', '=', 'mr.user_id')
            ->leftJoin('products as p', 'p.id', '=', 'book_returns.product_id')
            ->select(
                'book_returns.*',
                'us.name as representative_name',
                'mr.employee_id as representative_employee_id',
                'p.name as product_name'
            );

        $total = (clone $query)->count('book_returns.id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('us.name', 'like', "%{$search}%")
                    ->orWhere('mr.employee_id', 'like', "%{$search}%")
                    ->orWhere('p.name', 'like', "%{$search}%")
                    ->orWhere('book_returns.note', 'like', "%{$search}%")
                    ->orWhere('book_returns.status', 'like', "%{$search}%")
                    ->orWhere('book_returns.received_by', 'like', "%{$search}%")
                    ->orWhere('book_returns.received_date', 'like', "%{$search}%")
                    ->orWhere('book_returns.returned_quantity', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count('book_returns.id');

        $orderColumn = $columns[$orderIndex] ?? 'id';
        $query->orderBy($sortable[$orderColumn] ?? 'book_returns.id', $orderDirection);

        $data = $query->skip($start)->take($length)->get()->map(function ($bookReturn) {
            $status = match ($bookReturn->status) {
                'received' => '<span class="bg-success-focus text-success-600 border border-success-main px-12 py-4 radius-4 fw-medium text-sm">Received</span>',
                'partial' => '<span class="bg-info-focus text-info-600 border border-info-main px-12 py-4 radius-4 fw-medium text-sm">Partially Returned</span>',
                'rejected' => '<span class="bg-danger-focus text-danger-600 border border-danger-main px-12 py-4 radius-4 fw-medium text-sm">Rejected</span>',
                default => '<span class="bg-warning-focus text-warning-600 border border-warning-main px-12 py-4 radius-4 fw-medium text-sm">Pending</span>',
            };

            $showUrl = route('book-return.book-returns.show', $bookReturn->id);
            $editUrl = route('book-return.book-returns.editModal', $bookReturn->id);
            $deleteUrl = route('book-return.book-returns.destroy', $bookReturn->id);

            $actions = '<div class="d-inline-flex align-items-center justify-content-end gap-1 w-100">'
                . '<a href="' . $showUrl . '" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-info-focus text-info-main" title="View"><iconify-icon icon="lucide:eye"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" data-ajax-modal="' . $editUrl . '" data-size="lg" data-onload="BookReturnsIndex.onLoad" data-onsuccess="BookReturnsIndex.onSaved" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-book-return-delete" data-url="' . $deleteUrl . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>'
                . '</div>';

            $typeBadge = ($bookReturn->return_type === 'sale')
                ? '<span class="badge bg-primary-subtle text-primary-700 border border-primary-subtle text-xs px-8 py-3">Sale Copy</span>'
                : '<span class="badge bg-info-subtle text-info-700 border border-info-subtle text-xs px-8 py-3">Specimen</span>';

            return [
                (int) $bookReturn->id,
                e($bookReturn->representative_name ?: '-'),
                e($bookReturn->product_name ?? '-'),
                $typeBadge,
                (int) $bookReturn->returned_quantity,
                e($bookReturn->note ?: '-'),
                $status,
                e($bookReturn->received_by ?: '-'),
                $bookReturn->received_date?->format('d M, Y') ?? '-',
                $actions,
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $filtered,
            'aaData' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        try {
            return DB::transaction(function () use ($data) {
                $bookReturn = BookReturn::create($data);

                if (in_array($bookReturn->status, ['received', 'partial'], true) && (int) $bookReturn->returned_quantity > 0) {
                    $this->adjustStockForReturn($bookReturn, 0, (int) $bookReturn->returned_quantity);
                }

                return response()->json([
                    'ok' => true,
                    'id' => $bookReturn->id,
                    'msg' => 'Book return created.',
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'msg' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(Request $request, BookReturn $bookReturn)
    {
        $data = $this->validated($request);

        try {
            return DB::transaction(function () use ($bookReturn, $data) {
                $oldStatus = $bookReturn->status;
                $oldReturnedQty = (int) $bookReturn->returned_quantity;
                $oldRepresentativeId = (int) $bookReturn->marketing_representative_id;
                $oldProductId = (int) $bookReturn->product_id;
                $oldReturnType = $bookReturn->return_type ?: 'specimen';
                $newReturnType = $data['return_type'] ?? 'specimen';

                $oldEffectiveQty = in_array($oldStatus, ['received', 'partial'], true) ? $oldReturnedQty : 0;
                $newEffectiveQty = in_array($data['status'], ['received', 'partial'], true) ? (int) $data['returned_quantity'] : 0;

                // If representative, product, or return_type changed while previously deducted, restore old stock first
                if ($oldEffectiveQty > 0 && (
                    $oldRepresentativeId !== (int) $data['marketing_representative_id'] ||
                    $oldProductId !== (int) $data['product_id'] ||
                    $oldReturnType !== $newReturnType
                )) {
                    $this->restoreStock($oldRepresentativeId, $oldProductId, $oldEffectiveQty, $bookReturn->id, 'Book Return #' . $bookReturn->id . ' updated (Target/Type changed) - Stock restored', $oldReturnType);
                    $oldEffectiveQty = 0;
                }

                $bookReturn->update($data);

                $diff = $newEffectiveQty - $oldEffectiveQty;
                if ($diff !== 0) {
                    if ($diff > 0) {
                        $this->adjustStockForReturn($bookReturn, $oldEffectiveQty, $newEffectiveQty);
                    } else {
                        $restoreQty = abs($diff);
                        $this->restoreStock((int) $bookReturn->marketing_representative_id, (int) $bookReturn->product_id, $restoreQty, $bookReturn->id, 'Book Return #' . $bookReturn->id . ' adjusted - Stock restored', $bookReturn->return_type ?: 'specimen');
                    }
                }

                return response()->json([
                    'ok' => true,
                    'msg' => 'Book return updated.',
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'msg' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(BookReturn $bookReturn)
    {
        try {
            return DB::transaction(function () use ($bookReturn) {
                if (in_array($bookReturn->status, ['received', 'partial'], true) && (int) $bookReturn->returned_quantity > 0) {
                    $this->restoreStock(
                        (int) $bookReturn->marketing_representative_id,
                        (int) $bookReturn->product_id,
                        (int) $bookReturn->returned_quantity,
                        $bookReturn->id,
                        'Book Return #' . $bookReturn->id . ' deleted - Stock restored',
                        $bookReturn->return_type ?: 'specimen'
                    );
                }

                $bookReturn->delete();

                return response()->json(['ok' => true, 'msg' => 'Book return deleted.']);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'msg' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deduct returned books from Marketing Representative's CurrentStock and record StockLedger.
     */
    protected function adjustStockForReturn(BookReturn $bookReturn, int $alreadyDeducted, int $targetDeduction): void
    {
        $netToDeduct = $targetDeduction - $alreadyDeducted;
        if ($netToDeduct <= 0) {
            return;
        }

        $rep = MarketingRepresentative::find($bookReturn->marketing_representative_id);
        $userId = $rep?->user_id;

        if (!$userId) {
            return;
        }

        $stock = CurrentStock::where('user_id', $userId)
            ->where('product_id', $bookReturn->product_id)
            ->lockForUpdate()
            ->first();

        $returnType = $bookReturn->return_type ?: 'specimen';
        $stockCol = ($returnType === 'sale') ? 'sale_stock' : 'specimen_stock';
        $stockColLabel = ($returnType === 'sale') ? 'Sale' : 'Specimen';

        if ($stock) {
            $poolStock = (int) $stock->{$stockCol};
            if ($poolStock < $netToDeduct) {
                throw new \Exception("Representative has insufficient {$stockColLabel} stock for this product (Available: {$poolStock}, Attempted return: {$netToDeduct})");
            }
            $stock->{$stockCol} = max(0, $poolStock - $netToDeduct);
            $stock->current_stock = (int) $stock->specimen_stock + (int) $stock->sale_stock;
            $stock->save();
        } else {
            throw new \Exception('Representative has no allocated stock for product #' . $bookReturn->product_id);
        }

        StockLedger::create([
            'ref_type'   => 'Return',
            'ref_id'     => $bookReturn->id,
            'product_id' => $bookReturn->product_id,
            'quantity'   => $netToDeduct,
            'direction'  => 'OUT',
            'stock_type' => $returnType,
            'note'       => 'Book Return #' . $bookReturn->id . ($bookReturn->note ? ': ' . $bookReturn->note : ''),
        ]);
    }

    /**
     * Restore books back to Marketing Representative's CurrentStock and record compensating StockLedger.
     */
    protected function restoreStock(int $representativeId, int $productId, int $quantity, int $refId, string $note, string $returnType = 'specimen'): void
    {
        if ($quantity <= 0) {
            return;
        }

        $rep = MarketingRepresentative::find($representativeId);
        $userId = $rep?->user_id;

        if (!$userId) {
            return;
        }

        $stock = CurrentStock::where('user_id', $userId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        $stockCol = ($returnType === 'sale') ? 'sale_stock' : 'specimen_stock';

        if ($stock) {
            $stock->{$stockCol} = (int) $stock->{$stockCol} + $quantity;
            $stock->current_stock = (int) $stock->specimen_stock + (int) $stock->sale_stock;
            $stock->save();
        } else {
            CurrentStock::create([
                'user_id'       => $userId,
                'product_id'    => $productId,
                $stockCol       => $quantity,
                'current_stock' => $quantity,
            ]);
        }

        StockLedger::create([
            'ref_type'   => 'Return',
            'ref_id'     => $refId,
            'product_id' => $productId,
            'quantity'   => $quantity,
            'direction'  => 'IN',
            'stock_type' => $returnType,
            'note'       => $note,
        ]);
    }

    public function representativesSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));

        $representatives = MarketingRepresentative::with('user')
            ->where('status', 1)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($builder) use ($term) {
                    $builder->where('employee_id', 'like', "%{$term}%")
                        ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$term}%"));
                });
            })
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get();

        return response()->json([
            'results' => $representatives->map(fn($representative) => [
                'id' => $representative->id,
                'text' => trim($representative->user?->name ?: ($representative->employee_id ?: '#' . $representative->id)),
            ])->values(),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'marketing_representative_id' => ['required', 'integer', Rule::exists('marketing_representatives', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'return_type' => ['required', 'string', Rule::in(['specimen', 'sale'])],
            'returned_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys(BookReturn::STATUSES))],
            'received_by' => ['nullable', 'string', 'max:191'],
            'received_date' => ['nullable', 'date'],
        ]);

        $data['issued_quantity'] = (int) $data['returned_quantity'];

        if (empty($data['received_date'])) {
            $data['received_date'] = null;
        }

        return $data;
    }
}
