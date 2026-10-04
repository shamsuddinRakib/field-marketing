<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\CurrentStock;
use App\Models\backend\ProductDistribution;
use App\Models\backend\StockLedger;
use App\Models\backend\StockLedgers;
use App\Models\backend\StockLedgers as BackendStockLedgers;
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
                $b->status==='delivered'?'N/A': $actions,
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


    public function updateStatus(ProductDistribution $productDistribution)
    {
         if($productDistribution->status==='delivered'){
                return response()->json(['ok' => false, 'msg' => ['Delivered record cannot be updated']],402);
            }

        $distribution = DB::transaction(function () use ($productDistribution) {
           
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

            // return $distribution;
        });

        $productDistribution->status = 'delivered';
        $productDistribution->save();

        return response()->json(['success' => true, 'msg' => 'Status Updated Successfully']);
    }

    // public function editModal(ProductType $productType)
    // {
    //     return view('backend.modules.product_types.edit_modal', compact('productType'));
    // }
}
