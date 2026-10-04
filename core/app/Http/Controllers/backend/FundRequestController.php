<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\FundRequest;
use Illuminate\Http\Request;

class FundRequestController extends Controller
{
       public function index()
    {
        return view('backend.modules.fund_request.index');
    }

    public function listAjax(Request $request)
    {
        $columns   = ['id', 'user_id',   'amount', 'note', 'status'];
        $draw      = (int) $request->input('draw');
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderIdx  = (int) $request->input('order.0.column', 0);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));

        $base = FundRequest::query()->select(['id', 'user_id', 'amount', 'note', 'status']);

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
                    data-ajax-modal="' . route('fund-request.edit', $b->id) . '"
                    data-size="lg"
                    data-onsuccess="BranchesIndex.onSaved"
                    title="Edit">
                    <iconify-icon icon="lucide:edit"></iconify-icon>
                </a>
                <a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-branch-delete"
                    data-id="' . $b->id . '"
                    data-url="' . route('fund-request.destroy', $b->id) . '"
                    title="Delete">
                    <iconify-icon icon="mdi:delete"></iconify-icon>
                </a>
            </div>';

            $statusLabel = match ($b->status) {
                'pending'   => '<span class="badge text-sm fw-semibold bg-info-600 px-20 py-9 radius-4 text-white">Pending</span>',
                'confirmed' => '<span class="badge text-sm fw-semibold bg-lilac-600 px-20 py-9 radius-4 text-white">Confirmed</span>',
                'approved' => '<span class="badge text-sm fw-semibold bg-success-600 px-20 py-9 radius-4 text-white">Approved</span>',
                'cancelled' => '<span class="badge text-sm fw-semibold bg-danger-600 px-20 py-9 radius-4 text-white">Cancelled</span>',
                'returned'  => '<span class="badge text-sm fw-semibold bg-warning-600 px-20 py-9 radius-4 text-white">Returned</span>',
                'hold'      => '<span class="badge text-sm fw-semibold bg-warning-600 px-20 py-9 radius-4 text-white">Hold</span>',
                'void'      => '<span class="badge text-sm fw-semibold bg-danger-600 px-20 py-9 radius-4 text-white">Void</span>',
                default     => '<span class="badge text-sm fw-semibold bg-lilac-600 px-20 py-9 radius-4 text-white">' . e(ucfirst($b->status)) . '</span>',
            };

            $statusBadge = '<a href="javascript:void(0)" class="AjaxModal" 
                            data-ajax-modal="' . route('fund-request.statusModal', $b->id) . '" 
                            data-onsuccess="BranchesIndex.onSaved"
                            data-size="sm">' . $statusLabel . '</a>';


            $data[] = [
                $b->id,
                $nameCol,
                $b->amount,
                $b->note,
                $statusBadge,
                $b->status === 'approved' ? 'N/A' : $actions,
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
        return view('backend.modules.fund_request.create'); // partial only
    }

    public function store(Request $req)
    {
        // return $req->all();
        $data = $req->validate([
            'user_id'      => ['required', 'string', 'max:150'],
            'amount'     => ['required', 'integer'],
            'note'     => ['nullable', 'string', 'max:150'],

        ]);

       $fnd = FundRequest::create($data);
        if(!$fnd){
        return response()->json(['ok' => false, 'msg' => 'Something went wrong']);

        }


        return response()->json(['ok' => true, 'msg' => 'Fund Requested successfully']);
    }

    public function  editModal(Request $req, FundRequest $fundRequest)
    {
        return view('backend.modules.fund_request.edit', compact('fundRequest'));
    }

    public function update(FundRequest $fundRequest, Request $req)
    {
        // dd($req->all());
        $data = $req->validate([
            'user_id'      => ['required', 'string', 'max:150'],
            'amount'     => ['required', 'integer'],
            'note'     => ['nullable', 'string', 'max:150'],

        ]);

       

        $fundRequest->update($data);

        return response()->json(['ok' => true, 'msg' => 'Updated Successfully']);
    }


    public function destroy(FundRequest $fundRequest)
    {
        // return $productDistribution;
        if ($fundRequest->status === 'pending') {
            $fundRequest->delete();
            return response()->json(['ok' => true, 'msg' => 'Deleted Successfully']);
        }

        return response()->json(['ok' => false, 'msg' => 'Approved record cannt be deleted.']);
    }

    public function statusModal(FundRequest $fundRequest)
    {
        return view('backend.modules.fund_request.statusModal', compact('fundRequest'));
    }


    public function updateStatus(FundRequest $fundRequest, Request $req)
    {
        if ($fundRequest->status === 'approved' || $req->status == 'pending') {
            return response()->json(['ok' => false, 'msg' => ['Approved record cannot be updated']], 402);
        }

       $fundRequest->status = 'approved';
       $fundRequest->save();

        return response()->json(['success' => true, 'msg' => 'Status Updated Successfully']);
    }
}
