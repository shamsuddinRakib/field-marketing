<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\AssignSpecimen;
use App\Models\backend\CurrentStock;
use App\Models\backend\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignSpecimenController extends Controller
{
    public function index()
    {
        return view('backend.modules.assign_specimen.index');
    }

    public function listAjax(Request $request)
    {
        $columns   = ['id', 'user_id', 'teacher_id', 'library_id', 'product_id', 'quantity', 'note', 'status'];
        $draw      = (int) $request->input('draw');
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderIdx  = (int) $request->input('order.0.column', 0);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));

        $base = AssignSpecimen::query()->select(['id', 'user_id', 'teacher_id', 'library_id', 'product_id', 'quantity', 'note', 'status']);

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
                    data-ajax-modal="' . route('assign-specimen.edit', $b->id) . '"
                    data-size="lg"
                    data-onsuccess="BranchesIndex.onSaved"
                    title="Edit">
                    <iconify-icon icon="lucide:edit"></iconify-icon>
                </a>
                <a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-branch-delete"
                    data-id="' . $b->id . '"
                    data-url="' . route('assign-specimen.destroy', $b->id) . '"
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
                            data-ajax-modal="' . route('assign-specimen.statusModal', $b->id) . '" 
                            data-onsuccess="BranchesIndex.onSaved"
                            data-size="sm">' . $statusLabel . '</a>';


            $data[] = [
                $b->id,
                $nameCol,
                $b->teacher ? 'Teacher' : ($b->library ? 'Library' : ''),
                $b->teacher?->teacher_name ?? $b->library?->library_name ?? '-',
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
        return view('backend.modules.assign_specimen.create'); // partial only
    }

    public function store(Request $req)
    {
        // return $req->all();
        $data = $req->validate([
            'user_id'      => ['required', 'string', 'max:150'],
            'type' => ['required', 'string'],
            'visitable_id' => ['required', 'integer'],
            'product_id'      => ['required', 'string', 'max:50'],
            'quantity'     => ['required', 'integer'],
            'note'     => ['nullable', 'string', 'max:150'],

        ]);

        if ($data['type'] == 'teacher') {

            $distribution = AssignSpecimen::create([
                'user_id'    => $data['user_id'],
                'teacher_id' => $data['visitable_id'],
                'product_id' => $data['product_id'],
                'quantity'   => $data['quantity'],
                'note'       => $data['note'],
            ]);
        } elseif ($data['type'] == 'library') {
            $distribution = AssignSpecimen::create([
                'user_id'    => $data['user_id'],
                'library_id' => $data['visitable_id'],
                'product_id' => $data['product_id'],
                'quantity'   => $data['quantity'],
                'note'       => $data['note'],
            ]);
        } else {
            return response()->json(['ok' => false, 'msg' => 'Invalid type']);
        }



        return response()->json(['ok' => true, 'msg' => 'Product distributed successfully']);
    }

    public function  editModal(Request $req, AssignSpecimen $assignSpecimen)
    {
        return view('backend.modules.assign_specimen.edit', compact('assignSpecimen'));
    }

    public function update(AssignSpecimen $assignSpecimen, Request $req)
    {
        // dd($req->all());
        $data = $req->validate([
            'user_id'      => ['required', 'string', 'max:150'],
            'type' => ['required', 'string'],
            'visitable_id'      => ['required', 'string', 'max:150'],
            'product_id'      => ['required', 'string', 'max:50'],
            'quantity'     => ['required', 'integer'],
            'note'     => ['nullable', 'string', 'max:150'],

        ]);

        if ($data['type'] == 'teacher') {
            $assignSpecimen->user_id = $data['user_id'];
            $assignSpecimen->teacher_id = $data['visitable_id'];
            $assignSpecimen->library_id = null;
            $assignSpecimen->product_id = $data['product_id'];
            $assignSpecimen->quantity = $data['quantity'];
            $assignSpecimen->note = $data['note'];
        } elseif ($data['type'] == 'library') {
            $assignSpecimen->user_id = $data['user_id'];
            $assignSpecimen->teacher_id = null;
            $assignSpecimen->library_id = $data['visitable_id'];
            $assignSpecimen->product_id = $data['product_id'];
            $assignSpecimen->quantity = $data['quantity'];
            $assignSpecimen->note = $data['note'];
        } else {
            return response()->json(['ok' => false, 'msg' => 'Invalid type']);
        }

        $assignSpecimen->save();

        return response()->json(['ok' => true, 'msg' => 'Updated Successfully']);
    }


    public function destroy(AssignSpecimen $assignSpecimen)
    {
        // return $productDistribution;
        if ($assignSpecimen->status === 'pending') {
            $assignSpecimen->delete();
            return response()->json(['ok' => true, 'msg' => 'Deleted Successfully']);
        }

        return response()->json(['ok' => false, 'msg' => 'Approved record cannt be deleted.']);
    }

    public function statusModal(AssignSpecimen $assignSpecimen)
    {
        return view('backend.modules.assign_specimen.statusModal', compact('assignSpecimen'));
    }


    public function updateStatus(AssignSpecimen $assignSpecimen, Request $req)
    {
        if ($assignSpecimen->status === 'approved' || $req->status == 'pending') {
            return response()->json(['ok' => false, 'msg' => ['Approved record cannot be updated']], 402);
        }

        try {
            $distribution = DB::transaction(function () use ($assignSpecimen) {

                $stock = CurrentStock::where('user_id', $assignSpecimen->user_id)
                    ->where('product_id', $assignSpecimen->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    throw new \Exception('Insufficient stock!');
                }

                if ($stock->current_stock < $assignSpecimen->quantity) {
                    throw new \Exception('Insufficient stock!');
                }

                $stock->decrement('current_stock', $assignSpecimen->quantity);

                $ledger = StockLedger::create([
                    'ref_type'   => 'Assign',
                    'ref_id'     => $assignSpecimen->id,
                    'product_id' => $assignSpecimen->product_id,
                    'quantity'   => $assignSpecimen->quantity,
                    'direction'  => 'OUT',
                    'note'       => $assignSpecimen->note ?? '',
                ]);

                return $ledger;
            });

            return response()->json([
                'ok'  => true,
                'msg' => 'Specimen assigned successfully.',
                'data' => $distribution,
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'ok'  => false,
                'msg' => $e->getMessage(),
            ], 409);
        }

        return response()->json(['success' => true, 'msg' => 'Status Updated Successfully']);
    }
}
