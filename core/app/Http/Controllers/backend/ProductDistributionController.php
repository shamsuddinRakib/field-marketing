<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\CurrentStock;
use App\Models\backend\ProductDistribution;
use App\Models\backend\ProductDistributionItem;
use App\Models\backend\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductDistributionController extends Controller
{
    public function index()
    {
        return view('backend.modules.product_distribution.index');
    }

    public function listAjax(Request $request)
    {
        $draw      = (int) $request->input('draw');
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));

        $base = ProductDistribution::query()
            ->select(['id', 'user_id', 'note', 'status', 'created_at'])
            ->with(['user:id,name', 'items.product:id,name'])
            ->withSum('items as items_qty', 'quantity');

        $total = (clone $base)->count();

        if ($searchVal !== '') {
            $base->where(function ($q) use ($searchVal) {
                $q->where('note', 'like', "%{$searchVal}%")
                    ->orWhere('id', $searchVal)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$searchVal}%"));
            });
        }

        $filtered = (clone $base)->count();

        $rows = $base->orderBy('id', $orderDir)
            ->skip($start)->take($length)->get();

        $data = [];
        foreach ($rows as $b) {
            $nameCol = '<strong>' . e(optional($b->user)->name ?? ('#' . $b->user_id)) . '</strong>';
            $whenCol = $b->created_at ? $b->created_at->format('d M Y, h:i A') : '—';
            $qty     = $b->items_qty ?? $b->items->sum('quantity');

            $statusLabel = match ($b->status) {
                'pending'   => '<span class="badge text-sm fw-semibold bg-info-600 px-20 py-9 radius-4 text-white">Pending</span>',
                'delivered' => '<span class="badge text-sm fw-semibold bg-success-600 px-20 py-9 radius-4 text-white">Delivered</span>',
                default     => '<span class="badge text-sm fw-semibold bg-lilac-600 px-20 py-9 radius-4 text-white">' . e(ucfirst($b->status)) . '</span>',
            };

            $statusBadge = $b->status === 'delivered'
                ? $statusLabel
                : '<a href="javascript:void(0)" class="AjaxModal" data-ajax-modal="' . route('product-distribution.statusModal', $b->id) . '" data-onsuccess="BranchesIndex.onSaved" data-size="sm">' . $statusLabel . '</a>';

            $actions = '<div class="d-inline-flex align-items-center gap-1">';

            $actions .= '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-info-focus text-info-main AjaxModal" data-ajax-modal="' . route('product-distribution.show', $b->id) . '" data-size="lg" title="View Details"><iconify-icon icon="mdi:eye"></iconify-icon></a>';

            if ($b->status !== 'delivered') {
                $actions .= '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" data-ajax-modal="' . route('product-distribution.edit', $b->id) . '" data-size="lg" data-onsuccess="BranchesIndex.onSaved" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>';
                $actions .= '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-branch-delete" data-id="' . $b->id . '" data-url="' . route('product-distribution.destroy', $b->id) . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>';
            }

            $actions .= '</div>';

            $data[] = [
                $b->id,
                $nameCol,
                $whenCol,
                number_format((float) $qty),
                e($b->note ?? '—'),
                $statusBadge,
                $actions,
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
        return view('backend.modules.product_distribution.create');
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'user_id'             => ['required', 'integer', 'exists:users,id'],
            'note'                => ['nullable', 'string', 'max:255'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
        ]);

        // Merge duplicate products into single line
        $merged = [];
        foreach ($data['items'] as $item) {
            $pid = (int) $item['product_id'];
            $merged[$pid] = ($merged[$pid] ?? 0) + (int) $item['quantity'];
        }

        DB::beginTransaction();
        try {
            $distribution = ProductDistribution::create([
                'user_id' => $data['user_id'],
                'note'    => $data['note'] ?? null,
                'status'  => 'pending',
            ]);

            foreach ($merged as $productId => $qty) {
                ProductDistributionItem::create([
                    'product_distribution_id' => $distribution->id,
                    'product_id'              => $productId,
                    'quantity'                => $qty,
                ]);
            }

            DB::commit();

            return response()->json(['ok' => true, 'msg' => 'Products distributed successfully (pending)', 'data' => ['id' => $distribution->id]]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['ok' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function show(ProductDistribution $productDistribution)
    {
        $productDistribution->load(['items.product:id,name', 'user:id,name']);

        return view('backend.modules.product_distribution.show', compact('productDistribution'));
    }

    public function editModal(ProductDistribution $productDistribution)
    {
        if ($productDistribution->status === 'delivered') {
            abort(403, 'Delivered record cannot be edited');
        }

        $productDistribution->load(['items.product:id,name', 'user:id,name']);

        return view('backend.modules.product_distribution.edit', compact('productDistribution'));
    }

    public function update(ProductDistribution $productDistribution, Request $req)
    {
        if ($productDistribution->status === 'delivered') {
            return response()->json(['ok' => false, 'msg' => 'Delivered record cannot be updated'], 422);
        }

        $data = $req->validate([
            'user_id'             => ['required', 'integer', 'exists:users,id'],
            'note'                => ['nullable', 'string', 'max:255'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
        ]);

        $merged = [];
        foreach ($data['items'] as $item) {
            $pid = (int) $item['product_id'];
            $merged[$pid] = ($merged[$pid] ?? 0) + (int) $item['quantity'];
        }

        DB::beginTransaction();
        try {
            $productDistribution->update([
                'user_id' => $data['user_id'],
                'note'    => $data['note'] ?? null,
            ]);

            $productDistribution->items()->delete();

            foreach ($merged as $productId => $qty) {
                $productDistribution->items()->create([
                    'product_id' => $productId,
                    'quantity'   => $qty,
                ]);
            }

            DB::commit();

            return response()->json(['ok' => true, 'msg' => 'Updated Successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['ok' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function destroy(ProductDistribution $productDistribution)
    {
        if ($productDistribution->status !== 'pending') {
            return response()->json(['ok' => false, 'msg' => 'Only pending records can be deleted.'], 422);
        }

        $productDistribution->items()->delete();
        $productDistribution->delete();

        return response()->json(['ok' => true, 'msg' => 'Deleted Successfully']);
    }

    public function statusModal(ProductDistribution $productDistribution)
    {
        $productDistribution->load(['items.product:id,name', 'user:id,name']);

        return view('backend.modules.product_distribution.statusModal', compact('productDistribution'));
    }

    public function updateStatus(ProductDistribution $productDistribution, Request $req)
    {
        $data = $req->validate([
            'status' => ['required', 'string', 'in:pending,delivered'],
        ]);

        if ($productDistribution->status === 'delivered') {
            return response()->json(['ok' => false, 'msg' => 'Delivered record cannot be updated'], 422);
        }

        if ($data['status'] === 'pending') {
            return response()->json(['ok' => true, 'msg' => 'Status is already pending']);
        }

        $productDistribution->load('items');

        if ($productDistribution->items->isEmpty()) {
            return response()->json(['ok' => false, 'msg' => 'No items to deliver'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($productDistribution->items as $item) {
                $stock = CurrentStock::where('user_id', $productDistribution->user_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($stock) {
                    $stock->increment('current_stock', $item->quantity);
                } else {
                    CurrentStock::create([
                        'user_id'       => $productDistribution->user_id,
                        'product_id'    => $item->product_id,
                        'current_stock' => $item->quantity,
                    ]);
                }

                StockLedger::create([
                    'ref_type'   => 'Distribution',
                    'ref_id'     => $productDistribution->id,
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'direction'  => 'IN',
                    'note'       => $productDistribution->note ?? '',
                ]);
            }

            $productDistribution->status = 'delivered';
            $productDistribution->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['ok' => false, 'msg' => 'Something went wrong. Please try again.'], 500);
        }

        return response()->json(['ok' => true, 'msg' => 'Status Updated Successfully']);
    }
}
