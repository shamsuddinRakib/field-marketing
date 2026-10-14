<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\BookRequest;
use App\Models\backend\CurrentStock;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\ProductDistribution;
use App\Models\backend\StockLedger;
use App\Models\backend\StockLedgers;
use App\Models\backend\StockLedgers as BackendStockLedgers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductDistributionController extends Controller
{
    public function index()
    {
        return view('backend.modules.product_distribution.index');
    }

    public function listAjax(Request $request)
    {
        $columns   = ['id', 'user_id', 'product_id', 'quantity', 'note', 'status'];
        $draw      = (int) $request->input('draw');
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderIdx  = (int) $request->input('order.0.column', 0);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));

        $base = ProductDistribution::query()->select(['id', 'user_id', 'product_id', 'quantity', 'note', 'status']);

        $total = (clone $base)->count();

        if ($searchVal !== '') {
            $base->where(function ($q) use ($searchVal) {
                $q->where('name', 'like', "%{$searchVal}%")
                    ->orWhere('code', 'like', "%{$searchVal}%")
                    ->orWhere('sort', 'like', "%{$searchVal}%");
            });
        }

        $filtered = (clone $base)->count();

        $orderCol = $columns[$orderIdx] ?? 'id';

        $rows = $base->orderBy($orderCol, $orderDir)
            ->skip($start)->take($length)->get();

        $data = [];
        foreach ($rows as $b) {
            $nameCol = '<strong>' . e($b->user->name) . '</strong>';

            // $active = $b->is_active
            //     ? '<span class="badge text-sm fw-semibold bg-dark-success-gradient px-20 py-9 radius-4 text-white">Active</span>'
            //     : '<span class="badge text-sm fw-semibold bg-dark-warning-gradient px-20 py-9 radius-4 text-white">Inactive</span>';

            $actions = '<div class="d-inline-flex justify-content-end gap-1 w-100">
                <a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
                    bg-success-focus text-success-main AjaxModal"
                    data-ajax-modal="' . route('product-distribution.edit', $b->id) . '"
                    data-size="lg"
                    data-onsuccess="BranchesIndex.onSaved"
                    title="Edit">
                    <iconify-icon icon="lucide:edit"></iconify-icon>
                </a>
                <a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-branch-delete"
                    data-id="' . $b->id . '"
                    data-url="' . route('product-distribution.destroy', $b->id) . '"
                    title="Delete">
                    <iconify-icon icon="mdi:delete"></iconify-icon>
                </a>
            </div>';

            $statusLabel = match ($b->status) {
                'pending'   => '<span class="badge text-sm fw-semibold bg-info-600 px-20 py-9 radius-4 text-white">Pending</span>',
                'confirmed' => '<span class="badge text-sm fw-semibold bg-lilac-600 px-20 py-9 radius-4 text-white">Confirmed</span>',
                'delivered' => '<span class="badge text-sm fw-semibold bg-success-600 px-20 py-9 radius-4 text-white">Delivered</span>',
                'cancelled' => '<span class="badge text-sm fw-semibold bg-danger-600 px-20 py-9 radius-4 text-white">Cancelled</span>',
                'returned'  => '<span class="badge text-sm fw-semibold bg-warning-600 px-20 py-9 radius-4 text-white">Returned</span>',
                'hold'      => '<span class="badge text-sm fw-semibold bg-warning-600 px-20 py-9 radius-4 text-white">Hold</span>',
                'void'      => '<span class="badge text-sm fw-semibold bg-danger-600 px-20 py-9 radius-4 text-white">Void</span>',
                default     => '<span class="badge text-sm fw-semibold bg-lilac-600 px-20 py-9 radius-4 text-white">' . e(ucfirst($b->status)) . '</span>',
            };

            $statusBadge = '<a href="javascript:void(0)" class="AjaxModal" 
                            data-ajax-modal="' . route('product-distribution.statusModal', $b->id) . '" 
                            data-onsuccess="BranchesIndex.onSaved"
                            data-size="sm">' . $statusLabel . '</a>';


            $data[] = [
                $b->id,
                $nameCol,

                $b->product->name,
                $b->quantity,
                $b->note,
                $statusBadge,
                $b->status === 'delivered' ? 'N/A' : $actions,
            ];
        }

        return response()->json([
            'draw'                 => $draw,
            'iTotalRecords'        => $total,
            'iTotalDisplayRecords' => $filtered,
            'aaData'               => $data,
        ]);
    }

    public function createModal()
    {
        // @perm গার্ড চাইলে দিন
        return view('backend.modules.product_distribution.create'); // partial only
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'user_id'      => ['required', 'string', 'max:150'],
            'product_id'      => ['required', 'string', 'max:50'],
            'quantity'     => ['required', 'integer'],
            'note'     => ['nullable', 'string', 'max:150'],

        ]);

        $distribution = ProductDistribution::create([
            'user_id'    => $data['user_id'],
            'product_id' => $data['product_id'],
            'quantity'   => $data['quantity'],
            'note'       => $data['note'],
        ]);


        return response()->json(['ok' => true, 'msg' => 'Product distributed successfully']);
    }

    public function editModal(Request $req, ProductDistribution $productDistribution)
    {
        return view('backend.modules.product_distribution.edit', compact('productDistribution'));
    }

    public function update(ProductDistribution $productDistribution, Request $req)
    {
        $data = $req->validate([
            'user_id'      => ['required', 'string', 'max:150'],
            'product_id'      => ['required', 'string', 'max:50'],
            'quantity'     => ['required', 'integer'],
            'note'     => ['nullable', 'string', 'max:150'],
          

        ]);

        if ($productDistribution->status === 'delivered') {
            return response()->json(['ok' => false, 'msg' => ['Delivered record cannot be updated']], 402);
        }

        $productDistribution->update($data);

        return response()->json(['ok' => true, 'msg' => 'Updated Successfully']);
    }


    public function destroy(ProductDistribution $productDistribution)
    {
        // return $productDistribution;
        if ($productDistribution->status === 'pending') {
            $productDistribution->delete();
            return response()->json(['ok' => true, 'msg' => 'Deleted Successfully']);
        }

        return response()->json(['ok' => false, 'msg' => 'Approved record cannt be deleted.']);
    }

    public function statusModal(ProductDistribution $productDistribution)
    {
        return view('backend.modules.product_distribution.statusModal', compact('productDistribution'));
    }


    public function updateStatus(ProductDistribution $productDistribution, Request $req)
    {
        $data = $req->validate([
            'status'     => ['required', 'string', 'in:pending,delivered'],
        ]);

        if ($productDistribution->status === 'pending' && $data['status'] === 'pending') {
            return response()->json(['ok' => true, 'msg' => ['Updated Successfully']]);
        }

        if ($productDistribution->status === 'delivered') {
            return response()->json(['ok' => false, 'msg' => ['Delivered record cannot be updated']], 402);
        }

        DB::beginTransaction();
        try {
            $stock = CurrentStock::where('user_id', $productDistribution->user_id)
                ->where('product_id', $productDistribution->product_id)
                ->first();

            if ($stock) {
                $stock->increment('current_stock', $productDistribution->quantity);
            } else {
                CurrentStock::create([
                    'user_id'       => $productDistribution->user_id,
                    'product_id'    => $productDistribution->product_id,
                    'current_stock' => $productDistribution->quantity,
                ]);
            }

            StockLedger::create([
                'ref_type'   => 'Distribution',
                'ref_id'     => $productDistribution->id,
                'product_id' => $productDistribution->product_id,
                'quantity'   => $productDistribution->quantity,
                'direction'  => 'IN',
                'note'       => $productDistribution->note ?? '',
            ]);

            // Auto-save distribution table data into book_requests so it
            // becomes visible/usable in Book Request + Book Return modules.
            $this->syncDistributionToBookRequest($productDistribution);

            $productDistribution->status = 'delivered';
            $productDistribution->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json(['ok' => false, 'msg' => ['Something went wrong. Please try again.']], 500);
        }

        return response()->json(['success' => true, 'msg' => 'Status Updated Successfully. Distribution saved to book requests.']);
    }

    /**
     * Select2: Products data from Distribution List (product_distributions).
     * Used by Book Requests + Book Returns so their product dropdowns
     * come from the distribution list.
     *
     * GET params: q, marketing_representative_id (optional), status (pending/delivered, default all)
     * Returns: [{id: product_id, text: "Name (Dist: total)", distributed_quantity, ...}]
     */
    public function productsSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $representativeId = $request->input('marketing_representative_id');
        $status = $request->input('status');

        $query = ProductDistribution::query()
            ->join('products as p', 'p.id', '=', 'product_distributions.product_id')
            ->selectRaw('product_distributions.product_id as product_id, p.name as product_name, p.sku as sku, SUM(product_distributions.quantity) as distributed_quantity, COUNT(product_distributions.id) as distribution_count')
            ->groupBy('product_distributions.product_id', 'p.name', 'p.sku');

        if (in_array($status, ['pending', 'delivered'], true)) {
            $query->where('product_distributions.status', $status);
        }

        if ($representativeId) {
            $representative = MarketingRepresentative::find($representativeId);
            if (! $representative) {
                return response()->json(['results' => []]);
            }
            $query->where('product_distributions.user_id', (string) $representative->user_id);
        }

        if ($term !== '') {
            $query->where(function ($builder) use ($term) {
                $builder->where('p.name', 'like', "%{$term}%")
                    ->orWhere('p.sku', 'like', "%{$term}%");
            });
        }

        $items = $query->orderByDesc('distributed_quantity')->limit(30)->get();

        return response()->json([
            'results' => $items->map(fn($item) => [
                'id'   => $item->product_id,
                'text' => $item->product_name . ' (Dist: ' . (int) $item->distributed_quantity . ')',
                'distributed_quantity' => (int) $item->distributed_quantity,
                'sku'  => $item->sku,
            ])->values(),
        ]);
    }

    /**
     * Select2: Distribution rows themselves (for Book Returns Products).
     * Same source as Book Requests: product_distribution list.
     * GET params: q, marketing_representative_id (required), status (pending/delivered, default all),
     *   return_group_id (optional, to exclude current group when editing)
     * Returns: [{id: distribution_id, product_id, text, issued_quantity, remaining_quantity, ...}]
     */
    public function distributionsSelect2(Request $request)
    {
        $representativeId = $request->input('marketing_representative_id');
        if (! $representativeId) {
            return response()->json(['results' => []]);
        }

        $representative = MarketingRepresentative::find($representativeId);
        if (! $representative) {
            return response()->json(['results' => []]);
        }

        $term = trim($request->input('q', ''));
        $status = $request->input('status');
        $returnGroupId = $request->input('return_group_id');

        $query = ProductDistribution::query()
            ->with('product:id,name,sku')
            ->where('user_id', (string) $representative->user_id);

        if (in_array($status, ['pending', 'delivered'], true)) {
            $query->where('status', $status);
        }

        if ($term !== '') {
            $query->whereHas('product', fn($productQuery) => $productQuery
                ->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%"));
        }

        $items = $query->orderByDesc('id')->limit(50)->get();

        $results = [];
        foreach ($items as $item) {
            $returnedTotal = \App\Models\backend\BookReturn::where('product_distribution_id', $item->id)
                ->where('status', '!=', 'rejected')
                ->when($returnGroupId, fn($q) => $q->where(function ($b) use ($returnGroupId) {
                    $b->whereNull('return_group_id')->orWhere('return_group_id', '!=', $returnGroupId);
                }))
                ->sum('returned_quantity');
            $remaining = max(0, (int) $item->quantity - (int) $returnedTotal);

            // Hide fully-returned distributions (same as book_requests flow hides fully-returned requests)
            if ($remaining <= 0) {
                continue;
            }

            // Match Book Requests text style: "Name (Requested: X, Remaining: Y)" + Dist tag
            $results[] = [
                'id'                      => $item->id,
                'product_id'              => $item->product_id,
                'text'                    => ($item->product?->name ?? '#' . $item->product_id)
                    . ' (Requested: ' . (int) $item->quantity . ', Remaining: ' . $remaining . ') [Dist#' . $item->id . ']',
                'quantity'                => (int) $item->quantity,
                'issued_quantity'         => (int) $item->quantity,
                'remaining_quantity'      => $remaining,
                'distributed_quantity'    => (int) $item->quantity,
                'status'                  => $item->status,
            ];

            if (count($results) >= 30) {
                break;
            }
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Save product_distribution row (MR, product, qty, note) as an
     * approved BookRequest. Idempotent via product_distribution_id.
     * Skips silently when the user is not a marketing representative.
     */
    protected function syncDistributionToBookRequest(ProductDistribution $productDistribution): ?BookRequest
    {
        // Already synced? do nothing (idempotent).
        $existing = BookRequest::where('product_distribution_id', $productDistribution->id)->first();
        if ($existing) {
            return $existing;
        }

        $representative = MarketingRepresentative::where('user_id', (string) $productDistribution->user_id)->first();
        if (! $representative) {
            return null;
        }

        $requestDate = $productDistribution->created_at
            ? \Carbon\Carbon::parse($productDistribution->created_at)->toDateString()
            : now()->toDateString();

        return BookRequest::create([
            'marketing_representative_id' => $representative->id,
            'request_group_id'            => (string) Str::uuid(),
            'product_distribution_id'     => $productDistribution->id,
            'product_id'                  => $productDistribution->product_id,
            'quantity'                    => max(1, (int) $productDistribution->quantity),
            'note'                        => $productDistribution->note,
            'request_date'                => $requestDate,
            'status'                      => 'approved',
        ]);
    }


    // public function editModal(ProductType $productType)
    // {
    //     return view('backend.modules.product_types.edit_modal', compact('productType'));
    // }
}
