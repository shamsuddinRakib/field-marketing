<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\CurrentFund;
use App\Models\backend\FundDistribution;
use App\Models\backend\FundTransferLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FundDistributionController extends Controller
{
    public function index()
    {
        return view('backend.modules.fund_distribution.index');
    }

    public function listAjax(Request $request)
    {
        $columns   = ['id', 'user_id', 'amount', 'created_at', 'note', 'status'];
        $draw      = (int) $request->input('draw');
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderIdx  = (int) $request->input('order.0.column', 0);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));

        $base = FundDistribution::query()->select(['id', 'user_id', 'amount', 'created_at', 'note', 'status']);

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
                    data-ajax-modal="' . route('fund-distribution.edit', $b->id) . '"
                    data-size="lg"
                    data-onsuccess="BranchesIndex.onSaved"
                    title="Edit">
                    <iconify-icon icon="lucide:edit"></iconify-icon>
                </a>
                <a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-branch-delete"
                    data-id="' . $b->id . '"
                    data-url="' . route('fund-distribution.destroy', $b->id) . '"
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
                            data-ajax-modal="' . route('fund-distribution.statusModal', $b->id) . '" 
                            data-onsuccess="BranchesIndex.onSaved"
                            data-size="sm">' . $statusLabel . '</a>';


            $data[] = [
                $b->id,
                $nameCol,

                $b->amount,
                Carbon::parse($b->created_at)->format('d F Y'),
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
        return view('backend.modules.fund_distribution.create'); // partial only
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'user_id'      => ['required', 'integer', 'exists:users,id'],
            'amount'     => ['required', 'numeric'],
            'note'     => ['nullable', 'string', 'max:250'],

        ]);

        $distribution = FundDistribution::create([
            'user_id'    => $data['user_id'],
            'amount'   => $data['amount'],
            'note'       => $data['note'],
        ]);


        return response()->json(['ok' => true, 'msg' => 'Fund distributed successfully']);
    }

    public function editModal(Request $req, FundDistribution $fundDistribution)
    {
        return view('backend.modules.fund_distribution.edit', compact('fundDistribution'));
    }

    public function update(FundDistribution $fundDistribution, Request $req)
    {
        $data = $req->validate([
            'user_id'      => ['required', 'integer', 'exists:users,id'],
            'amount'     => ['required', 'numeric'],
            'note'     => ['nullable', 'string', 'max:250'],

        ]);

        $fundDistribution->update($data);

        return response()->json(['ok' => true, 'msg' => 'Updated Successfully']);
    }


    public function destroy(FundDistribution $fundDistribution)
    {
        // return $productDistribution;
        if ($fundDistribution->status === 'pending') {
            $fundDistribution->delete();
            return response()->json(['ok' => true, 'msg' => 'Deleted Successfully']);
        }

        return response()->json(['ok' => false, 'msg' => 'Approved record cannt be deleted.']);
    }

    public function statusModal(FundDistribution $fundDistribution)
    {
        return view('backend.modules.fund_distribution.statusModal', compact('fundDistribution'));
    }


    public function updateStatus(FundDistribution $fundDistribution, Request $req)
    {
        // dd($fundDistribution);
        $data = $req->validate([
            'status'     => ['required', 'string', 'in:pending,delivered'],
        ]);

        if ($fundDistribution->status === 'pending' && $data['status'] === 'pending') {
            return response()->json(['ok' => true, 'msg' => ['Updated Successfully']]);
        }

        if ($fundDistribution->status === 'delivered') {
            return response()->json(['ok' => false, 'msg' => ['Delivered record cannot be updated']], 402);
        }



        DB::beginTransaction();
        try {
            $stock = CurrentFund::where('user_id', $fundDistribution->user_id)
                ->first();

            if ($stock) {
                $stock->increment('balance', $fundDistribution->amount);
            } else {
                CurrentFund::create([
                    'user_id'       => $fundDistribution->user_id,
                    'balance' => $fundDistribution->amount,
                ]);
            }

            FundTransferLedger::create([
                'ref_type'   => 'Distribution',
                'ref_id'     => $fundDistribution->id,
                'amount'   => $fundDistribution->amount,
                'direction'  => 'IN',
                'note'       => $fundDistribution->note ?? '',
            ]);

            // return $distribution;
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'msg' => ['Something went wrong. Please try again.']], 500);
        }



        $fundDistribution->status = 'delivered';
        $fundDistribution->save();

        return response()->json(['success' => true, 'msg' => 'Status Updated Successfully']);
    }
}
