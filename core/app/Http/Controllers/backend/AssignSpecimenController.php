<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\AssignSpecimen;
use App\Models\backend\AssignSpecimenItem;
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
        $draw      = (int) $request->input('draw');
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));

        $base = AssignSpecimen::query()
            ->select(['id', 'user_id', 'teacher_id', 'library_id', 'note', 'status', 'created_at'])
            ->with([
                'user:id,name',
                'teacher:id,teacher_name',
                'library:id,library_name',
                'items.product:id,name',
            ])
            ->withSum('items as items_qty', 'quantity');

        $total = (clone $base)->count();

        if ($searchVal !== '') {
            $base->where(function ($q) use ($searchVal) {
                $q->where('note', 'like', "%{$searchVal}%")
                    ->orWhere('id', $searchVal)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$searchVal}%"))
                    ->orWhereHas('teacher', fn ($t) => $t->where('teacher_name', 'like', "%{$searchVal}%"))
                    ->orWhereHas('library', fn ($l) => $l->where('library_name', 'like', "%{$searchVal}%"));
            });
        }

        $filtered = (clone $base)->count();

        $rows = $base->orderBy('id', $orderDir)
            ->skip($start)->take($length)->get();

        $data = [];
        foreach ($rows as $b) {
            $nameCol = '<strong>' . e(optional($b->user)->name ?? ('#' . $b->user_id)) . '</strong>';

            $type = $b->teacher_id ? 'teacher' : ($b->library_id ? 'library' : '—');
            $typeBadge = $type === 'teacher'
                ? '<span class="badge text-sm fw-semibold bg-primary px-12 py-6 radius-4">Teacher</span>'
                : ($type === 'library'
                    ? '<span class="badge text-sm fw-semibold bg-info px-12 py-6 radius-4">Library</span>'
                    : '—');

            $nameCol = '<strong>' . e($b->user->name ?? '') . '</strong>';

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
                'approved'  => '<span class="badge text-sm fw-semibold bg-success-600 px-20 py-9 radius-4 text-white">Approved</span>',
                default     => '<span class="badge text-sm fw-semibold bg-lilac-600 px-20 py-9 radius-4 text-white">' . e(ucfirst($b->status)) . '</span>',
            };

            $statusBadge = $b->status === 'approved'
                ? $statusLabel
                : '<a href="javascript:void(0)" class="AjaxModal" data-ajax-modal="' . route('assign-specimen.statusModal', $b->id) . '" data-onsuccess="BranchesIndex.onSaved" data-size="sm">' . $statusLabel . '</a>';

            $actions = '<div class="d-inline-flex align-items-center gap-1">';
            $actions .= '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-info-focus text-info-main AjaxModal" data-ajax-modal="' . route('assign-specimen.show', $b->id) . '" data-size="lg" title="View Details"><iconify-icon icon="mdi:eye"></iconify-icon></a>';

            if ($b->status !== 'approved') {
                $actions .= '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" data-ajax-modal="' . route('assign-specimen.edit', $b->id) . '" data-size="lg" data-onsuccess="BranchesIndex.onSaved" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>';
                $actions .= '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-branch-delete" data-id="' . $b->id . '" data-url="' . route('assign-specimen.destroy', $b->id) . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>';
            }

            $actions .= '</div>';

            $data[] = [
                $b->id,
                $nameCol,
                $typeBadge,
                $visited,
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
        return view('backend.modules.assign_specimen.create');
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'user_id'             => ['required', 'integer', 'exists:users,id'],
            'customer_type'       => ['required', 'in:teacher,library'],
            'teacher_id'          => ['nullable', 'integer', 'exists:teachers,id', 'required_if:customer_type,teacher'],
            'library_id'          => ['nullable', 'integer', 'exists:libraries,id', 'required_if:customer_type,library'],
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
            $specimen = AssignSpecimen::create([
                'user_id'    => $data['user_id'],
                'teacher_id' => $data['customer_type'] === 'teacher' ? $data['teacher_id'] : null,
                'library_id' => $data['customer_type'] === 'library' ? $data['library_id'] : null,
                'note'       => $data['note'] ?? null,
                'status'     => 'pending',
            ]);

            foreach ($merged as $productId => $qty) {
                AssignSpecimenItem::create([
                    'assign_specimen_id' => $specimen->id,
                    'product_id'         => $productId,
                    'quantity'           => $qty,
                ]);
            }

            DB::commit();

            return response()->json(['ok' => true, 'msg' => 'Specimen assigned successfully (pending)', 'data' => ['id' => $specimen->id]]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['ok' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function show(AssignSpecimen $assignSpecimen)
    {
        $assignSpecimen->load(['items.product:id,name', 'user:id,name', 'teacher:id,teacher_name', 'library:id,library_name']);

        return view('backend.modules.assign_specimen.show', compact('assignSpecimen'));
    }

    public function editModal(AssignSpecimen $assignSpecimen)
    {
        if ($assignSpecimen->status === 'approved') {
            abort(403, 'Approved record cannot be edited');
        }

        $assignSpecimen->load(['items.product:id,name', 'user:id,name', 'teacher:id,teacher_name', 'library:id,library_name']);

        return view('backend.modules.assign_specimen.edit', compact('assignSpecimen'));
    }

    public function update(AssignSpecimen $assignSpecimen, Request $req)
    {
        if ($assignSpecimen->status === 'approved') {
            return response()->json(['ok' => false, 'msg' => 'Approved record cannot be updated'], 422);
        }

        $data = $req->validate([
            'user_id'             => ['required', 'integer', 'exists:users,id'],
            'customer_type'       => ['required', 'in:teacher,library'],
            'teacher_id'          => ['nullable', 'integer', 'exists:teachers,id', 'required_if:customer_type,teacher'],
            'library_id'          => ['nullable', 'integer', 'exists:libraries,id', 'required_if:customer_type,library'],
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
            $assignSpecimen->update([
                'user_id'    => $data['user_id'],
                'teacher_id' => $data['customer_type'] === 'teacher' ? $data['teacher_id'] : null,
                'library_id' => $data['customer_type'] === 'library' ? $data['library_id'] : null,
                'note'       => $data['note'] ?? null,
            ]);

            $assignSpecimen->items()->delete();

            foreach ($merged as $productId => $qty) {
                $assignSpecimen->items()->create([
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

    public function destroy(AssignSpecimen $assignSpecimen)
    {
        if ($assignSpecimen->status !== 'pending') {
            return response()->json(['ok' => false, 'msg' => 'Only pending records can be deleted.'], 422);
        }

        $assignSpecimen->items()->delete();
        $assignSpecimen->delete();

        return response()->json(['ok' => true, 'msg' => 'Deleted Successfully']);
    }

    public function statusModal(AssignSpecimen $assignSpecimen)
    {
        $assignSpecimen->load(['items.product:id,name', 'user:id,name', 'teacher:id,teacher_name', 'library:id,library_name']);

        return view('backend.modules.assign_specimen.statusModal', compact('assignSpecimen'));
    }

    public function updateStatus(AssignSpecimen $assignSpecimen, Request $req)
    {
        $data = $req->validate([
            'status' => ['required', 'string', 'in:pending,approved'],
        ]);

        if ($assignSpecimen->status === 'approved') {
            return response()->json(['ok' => false, 'msg' => 'Approved record cannot be updated'], 422);
        }

        if ($data['status'] === 'pending') {
            return response()->json(['ok' => true, 'msg' => 'Status is already pending']);
        }

        $assignSpecimen->load('items');

        if ($assignSpecimen->items->isEmpty()) {
            return response()->json(['ok' => false, 'msg' => 'No items to approve'], 422);
        }

        try {
            DB::transaction(function () use ($assignSpecimen) {
                foreach ($assignSpecimen->items as $item) {
                    $stock = CurrentStock::where('user_id', $assignSpecimen->user_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$stock || $stock->current_stock < $item->quantity) {
                        throw new \Exception('Insufficient stock for product #' . $item->product_id);
                    }

                    $stock->decrement('current_stock', $item->quantity);

                    StockLedger::create([
                        'ref_type'   => 'Assign',
                        'ref_id'     => $assignSpecimen->id,
                        'product_id' => $item->product_id,
                        'quantity'   => $item->quantity,
                        'direction'  => 'OUT',
                        'note'       => $assignSpecimen->note ?? '',
                    ]);
                }

                $assignSpecimen->status = 'approved';
                $assignSpecimen->save();
            });

            return response()->json(['ok' => true, 'msg' => 'Specimen approved successfully.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()], 409);
        }
    }
}
