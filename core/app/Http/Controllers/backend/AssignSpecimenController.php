<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\AssignSpecimen;
use App\Models\backend\AssignSpecimenItem;
use App\Models\backend\CurrentStock;
use App\Models\backend\StockLedger;
use App\Models\backend\Teacher;
use App\Models\backend\Institution;
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
            ->select(['id', 'user_id', 'teacher_id', 'library_id', 'assign_inst_type', 'upazila_id', 'district_id', 'division_id', 'address', 'note', 'status', 'created_at'])
            ->with([
                'user:id,name,phone',
                'upazila',
                'district',
                'division',
                'teacher.institution.upazila',
                'teacher.institution.district',
                'teacher.institution.division',
                'teacher.upazila',
                'teacher.district',
                'teacher.division',
                'library.upazila',
                'library.district',
                'library.division',
                'items.product:id,name',
            ])
            ->withSum('items as items_qty', 'quantity');

        $total = (clone $base)->count();

        if ($searchVal !== '') {
            $base->where(function ($q) use ($searchVal) {
                $q->where('note', 'like', "%{$searchVal}%")
                    ->orWhere('id', $searchVal)
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$searchVal}%"))
                    ->orWhereHas('teacher', fn($t) => $t->where('teacher_name', 'like', "%{$searchVal}%")->orWhere('phone', 'like', "%{$searchVal}%"))
                    ->orWhereHas('library', fn($l) => $l->where('library_name', 'like', "%{$searchVal}%")->orWhere('phone', 'like', "%{$searchVal}%"));
            });
        }

        $filtered = (clone $base)->count();

        $rows = $base->orderBy('id', $orderDir)
            ->skip($start)->take($length)->get();

        $data = [];
        foreach ($rows as $b) {
            //$nameCol = '<strong>' . e(optional($b->user)->name ?? ('#' . $b->user_id)) . '</strong>';
            $nameCol = '<div>
                <div><strong>Name:</strong> ' .
                e(optional($b->user)->name ?? ('#' . $b->user_id)) .
                '</div>
                <div class="text-secondary" style="font-size:12px;">
                    <strong>Phone:</strong> ' .
                e(optional($b->user)->phone ?? '—') .
                '</div>
            </div>';

            $instName = '';
            $locStr = '';

            if ($b->teacher_id) {
                $recipientName = $b->teacher->teacher_name ?? '—';
                $recipientPhone = $b->teacher->phone ?? '—';
                $instName = $b->teacher->institution?->institution_name ?? '';
                $upazilaName = $b->upazila?->upazila_name ?? ($b->teacher->upazila?->upazila_name ?? ($b->teacher->institution?->upazila?->upazila_name ?? ''));
                $districtName = $b->district?->district_name ?? ($b->teacher->district?->district_name ?? ($b->teacher->institution?->district?->district_name ?? ''));
                $locParts = array_filter([$upazilaName, $districtName]);
                $locStr = !empty($locParts) ? implode(', ', $locParts) : '';
            } elseif ($b->library_id) {
                $recipientName = $b->library->library_name ?? '—';
                $recipientPhone = $b->library->phone ?? '—';
                $upazilaName = $b->upazila?->upazila_name ?? ($b->library->upazila?->upazila_name ?? '');
                $districtName = $b->district?->district_name ?? ($b->library->district?->district_name ?? '');
                $locParts = array_filter([$upazilaName, $districtName]);
                $locStr = !empty($locParts) ? implode(', ', $locParts) : '';
            } else {
                $recipientName = '—';
                $recipientPhone = '—';
            }

            $visited = '<div>
                <div><strong>Name:</strong> ' . e($recipientName) . '</div>
                <div class="text-secondary" style="font-size:12px;">
                    <strong>Phone:</strong> ' . e($recipientPhone) . '
                </div>';
            if ($instName) {
                $visited .= '<div class="text-secondary" style="font-size:12px;"><strong>Inst:</strong> ' . e($instName) . '</div>';
            }
            if ($locStr) {
                $visited .= '<div class="text-muted" style="font-size:11px;"><iconify-icon icon="lucide:map-pin" class="align-middle"></iconify-icon> ' . e($locStr) . '</div>';
            }
            $visited .= '</div>';


            $whenCol = $b->created_at
                ? $b->created_at->format('d M Y') : '—';

            $qty = (float) ($b->items_qty ?? 0);

            $type = $b->teacher_id ? 'teacher' : ($b->library_id ? 'library' : '—');
            $typeBadge = $type === 'teacher'
                ? '<span class="badge text-sm fw-semibold bg-primary px-12 py-6 radius-4">Teacher</span>'
                : ($type === 'library'
                    ? '<span class="badge text-sm fw-semibold bg-info px-12 py-6 radius-4">Library</span>'
                    : '—');

            //$nameCol = '<strong>' . e($b->user->name ?? '') . '</strong>';

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
            'assign_inst_type'    => ['nullable', 'string', 'in:school,college,coaching,batch', 'required_if:customer_type,teacher'],
            'upazila_id'          => ['nullable', 'integer', 'exists:upazilas,id'],
            'district_id'         => ['nullable', 'integer', 'exists:districts,district_id'],
            'division_id'         => ['nullable', 'integer', 'exists:divisions,id'],
            'address'             => ['nullable', 'string', 'max:255'],
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
                'user_id'          => $data['user_id'],
                'teacher_id'       => $data['customer_type'] === 'teacher' ? $data['teacher_id'] : null,
                'library_id'       => $data['customer_type'] === 'library' ? $data['library_id'] : null,
                'assign_inst_type' => $data['customer_type'] === 'teacher' ? ($data['assign_inst_type'] ?? null) : null,
                'upazila_id'       => $data['upazila_id'] ?? null,
                'district_id'      => $data['district_id'] ?? null,
                'division_id'      => $data['division_id'] ?? null,
                'address'          => $data['address'] ?? null,
                'note'             => $data['note'] ?? null,
                'status'           => 'pending',
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
        $assignSpecimen->load([
            'items.product:id,name',
            'user:id,name,phone',
            'teacher.institution.upazila',
            'teacher.institution.district',
            'teacher.institution.division',
            'teacher.upazila',
            'teacher.district',
            'teacher.division',
            'library.upazila',
            'library.district',
            'library.division',
        ]);

        return view('backend.modules.assign_specimen.show', compact('assignSpecimen'));
    }

    public function editModal(AssignSpecimen $assignSpecimen)
    {
        if ($assignSpecimen->status === 'approved') {
            abort(403, 'Approved record cannot be edited');
        }

        $assignSpecimen->load([
            'items.product:id,name',
            'user:id,name',
            'teacher.institution.upazila',
            'teacher.institution.district',
            'teacher.institution.division',
            'teacher.upazila',
            'teacher.district',
            'teacher.division',
            'library.upazila',
            'library.district',
            'library.division',
        ]);

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
            'assign_inst_type'    => ['nullable', 'string', 'in:school,college,coaching,batch', 'required_if:customer_type,teacher'],
            'upazila_id'          => ['nullable', 'integer', 'exists:upazilas,id'],
            'district_id'         => ['nullable', 'integer', 'exists:districts,district_id'],
            'division_id'         => ['nullable', 'integer', 'exists:divisions,id'],
            'address'             => ['nullable', 'string', 'max:255'],
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
                'user_id'          => $data['user_id'],
                'teacher_id'       => $data['customer_type'] === 'teacher' ? $data['teacher_id'] : null,
                'library_id'       => $data['customer_type'] === 'library' ? $data['library_id'] : null,
                'assign_inst_type' => $data['customer_type'] === 'teacher' ? ($data['assign_inst_type'] ?? null) : null,
                'upazila_id'       => $data['upazila_id'] ?? null,
                'district_id'      => $data['district_id'] ?? null,
                'division_id'      => $data['division_id'] ?? null,
                'address'          => $data['address'] ?? null,
                'note'             => $data['note'] ?? null,
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

    public function teachersByPhoneSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $upazilaId = $request->input('upazila_id');
        $institutionId = $request->input('institution_id');

        $query = Teacher::query()
            ->with(['institution.upazila', 'institution.district', 'institution.division', 'upazila', 'district', 'division'])
            ->when($institutionId, function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId);
            })
            ->when(!$institutionId && $upazilaId, function ($q) use ($upazilaId) {
                $q->where(function ($sub) use ($upazilaId) {
                    $sub->where('upazila_id', $upazilaId)
                        ->orWhereHas('institution', fn($inst) => $inst->where('upazila_id', $upazilaId));
                });
            })
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($b) use ($term) {
                    $b->where('phone', 'like', "%{$term}%")
                        ->orWhere('teacher_name', 'like', "%{$term}%");
                });
            })
            ->orderByRaw("CASE WHEN phone IS NOT NULL AND phone != '' THEN 0 ELSE 1 END")
            ->orderBy('phone')
            ->limit(30);

        $teachers = $query->get();

        return response()->json([
            'results' => $teachers->map(function ($teacher) {
                $inst = $teacher->institution;
                $instName = $inst?->institution_name ?? '';
                $upazila = $teacher->upazila ?? ($inst?->upazila);
                $district = $teacher->district ?? ($inst?->district);
                $division = $teacher->division ?? ($inst?->division);

                $text = $teacher->phone ?: 'No Phone';
                if ($teacher->teacher_name) {
                    $text .= ' (' . $teacher->teacher_name . ')';
                }
                if ($instName) {
                    $text .= ' - ' . $instName;
                }

                return [
                    'id'               => $teacher->id,
                    'text'             => $text,
                    'teacher_name'     => $teacher->teacher_name ?? '',
                    'phone'            => $teacher->phone ?? '',
                    'institution_id'   => $teacher->institution_id,
                    'institution_name' => $instName,
                    'upazila_id'       => $upazila?->id,
                    'upazila_name'     => $upazila?->upazila_name ?? '',
                    'district_id'      => $district?->district_id,
                    'district_name'    => $district?->district_name ?? '',
                    'division_id'      => $division?->id,
                    'division_name'    => $division?->name ?? ($division?->bn_name ?? ''),
                    'address'          => $teacher->address ?: ($inst?->address ?? ''),
                ];
            })->values(),
        ]);
    }

    public function institutionsSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $upazilaId = $request->input('upazila_id');

        // Strictly enforce that if upazila_id is not given, return empty results!
        if (!$upazilaId) {
            return response()->json(['results' => []]);
        }

        $query = Institution::query()
            ->with(['upazila', 'district', 'division'])
            ->where(function ($q) use ($upazilaId) {
                $q->where('upazila_id', $upazilaId)
                  ->orWhereHas('teachers', fn($t) => $t->where('upazila_id', $upazilaId));
            })
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($b) use ($term) {
                    $b->where('institution_name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            })
            ->orderBy('institution_name')
            ->limit(50);

        $institutions = $query->get();

        return response()->json([
            'results' => $institutions->map(function ($inst) {
                $name = $inst->institution_name ?: $inst->name;
                return [
                    'id'            => $inst->id,
                    'text'          => $name . ($inst->code ? " ({$inst->code})" : ''),
                    'upazila_id'    => $inst->upazila_id,
                    'upazila_name'  => $inst->upazila?->upazila_name ?? '',
                    'district_id'   => $inst->district_id,
                    'district_name' => $inst->district?->district_name ?? '',
                    'division_id'   => $inst->division_id,
                    'division_name' => $inst->division?->name ?? ($inst->division?->bn_name ?? ''),
                    'address'       => $inst->address ?? '',
                ];
            })->values(),
        ]);
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
