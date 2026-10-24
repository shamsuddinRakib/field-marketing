<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\DailyVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DailyVisitController extends Controller
{
    public function index()
    {
        return view('backend.modules.daily_visit.index');
    }

    public function listAjax(Request $request)
    {
        $columns   = ['id', 'user_id', 'teacher_id', 'library_id', 'note', 'status', 'id'];
        $draw      = (int) $request->input('draw');
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderIdx  = (int) $request->input('order.0.column', 0);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));

        $base = DailyVisit::query()->with(['user', 'teacher', 'library'])
            ->select(['id', 'user_id', 'teacher_id', 'library_id', 'note', 'status']);

        $total = (clone $base)->count();

        if ($searchVal !== '') {
            $base->where(function ($q) use ($searchVal) {
                $q->where('note', 'like', "%{$searchVal}%")
                    ->orWhere('status', 'like', "%{$searchVal}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$searchVal}%"))
                    ->orWhereHas('teacher', fn ($t) => $t->where('teacher_name', 'like', "%{$searchVal}%"))
                    ->orWhereHas('library', fn ($l) => $l->where('library_name', 'like', "%{$searchVal}%"));
            });
        }

        $filtered = (clone $base)->count();

        $orderCol = $columns[$orderIdx] ?? 'id';

        $rows = $base->orderBy($orderCol, $orderDir)
            ->skip($start)->take($length)->get();

        $data = [];
        foreach ($rows as $b) {
            $nameCol = '<strong>' . e($b->user->name ?? '-') . '</strong>';

            $actions = '<div class="d-inline-flex justify-content-end gap-1 w-100">
                <a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
                    bg-success-focus text-success-main AjaxModal"
                    data-ajax-modal="' . route('daily-visit.edit', $b->id) . '"
                    data-size="lg"
                    data-onsuccess="BranchesIndex.onSaved"
                    title="Edit">
                    <iconify-icon icon="lucide:edit"></iconify-icon>
                </a>
                <a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-branch-delete"
                    data-id="' . $b->id . '"
                    data-url="' . route('daily-visit.destroy', $b->id) . '"
                    title="Delete">
                    <iconify-icon icon="mdi:delete"></iconify-icon>
                </a>
            </div>';

            $statusLabel = match ($b->status) {
                'pending'   => '<span class="badge text-sm fw-semibold bg-info-600 px-20 py-9 radius-4 text-white">Pending</span>',
                'approved'  => '<span class="badge text-sm fw-semibold bg-success-600 px-20 py-9 radius-4 text-white">Approved</span>',
                default     => '<span class="badge text-sm fw-semibold bg-lilac-600 px-20 py-9 radius-4 text-white">' . e(ucfirst((string) $b->status)) . '</span>',
            };

            $statusBadge = '<a href="javascript:void(0)" class="AjaxModal" 
                            data-ajax-modal="' . route('daily-visit.statusModal', $b->id) . '" 
                            data-onsuccess="BranchesIndex.onSaved"
                            data-size="sm">' . $statusLabel . '</a>';

            $data[] = [
                $b->id,
                $nameCol,
                $b->teacher ? 'Teacher' : 'Library',
                $b->teacher?->teacher_name ?? $b->library?->library_name ?? '-',
                $b->note,
                $statusBadge,
                $b->status === 'pending' ? $actions : 'N/A',
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
        return view('backend.modules.daily_visit.create');
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'user_id'      => ['required', 'integer', 'exists:users,id'],
            'type'         => ['required', 'string', 'in:teacher,library'],
            'visitable_id' => ['required', 'integer'],
            'note'         => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['type'] === 'teacher') {
            $req->validate(['visitable_id' => ['exists:teachers,id']]);
            $dailyVisit = DailyVisit::create([
                'user_id'    => $data['user_id'],
                'teacher_id' => $data['visitable_id'],
                'library_id' => null,
                'note'       => $data['note'] ?? null,
                'status'     => 'pending',
            ]);
        } else {
            $req->validate(['visitable_id' => ['exists:libraries,id']]);
            $dailyVisit = DailyVisit::create([
                'user_id'    => $data['user_id'],
                'teacher_id' => null,
                'library_id' => $data['visitable_id'],
                'note'       => $data['note'] ?? null,
                'status'     => 'pending',
            ]);
        }

        return response()->json(['ok' => true, 'msg' => 'Daily visit created successfully']);
    }

    public function editModal(DailyVisit $dailyVisit)
    {
        $dailyVisit->load(['user', 'teacher', 'library']);

        return view('backend.modules.daily_visit.edit', compact('dailyVisit'));
    }

    public function update(DailyVisit $dailyVisit, Request $req)
    {
        if ($dailyVisit->status !== 'pending') {
            return response()->json(['ok' => false, 'msg' => 'Approved record cannot be updated.'], 422);
        }

        $data = $req->validate([
            'user_id'      => ['required', 'integer', 'exists:users,id'],
            'type'         => ['required', 'string', 'in:teacher,library'],
            'visitable_id' => ['required', 'integer'],
            'note'         => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['type'] === 'teacher') {
            $req->validate(['visitable_id' => ['exists:teachers,id']]);
            $dailyVisit->user_id = $data['user_id'];
            $dailyVisit->teacher_id = $data['visitable_id'];
            $dailyVisit->library_id = null;
        } else {
            $req->validate(['visitable_id' => ['exists:libraries,id']]);
            $dailyVisit->user_id = $data['user_id'];
            $dailyVisit->teacher_id = null;
            $dailyVisit->library_id = $data['visitable_id'];
        }

        $dailyVisit->note = $data['note'] ?? null;
        $dailyVisit->save();

        return response()->json(['ok' => true, 'msg' => 'Updated Successfully']);
    }

    public function destroy(DailyVisit $dailyVisit)
    {
        if ($dailyVisit->status !== 'pending') {
            return response()->json(['ok' => false, 'msg' => 'Approved record cannot be deleted.'], 422);
        }

        $dailyVisit->delete();

        return response()->json(['ok' => true, 'msg' => 'Deleted Successfully']);
    }

    public function statusModal(DailyVisit $dailyVisit)
    {
        return view('backend.modules.daily_visit.statusModal', compact('dailyVisit'));
    }


    public function updateStatus(DailyVisit $dailyVisit, Request $req)
    {
         $data = $req->validate([
            'status'     => ['required', 'string', 'in:pending,approved'],
        ]);

        if ($dailyVisit->status === 'approved') {
            return response()->json(['ok' => false, 'msg' => 'Approved record cannot be updated'], 422);
        }

        if ($dailyVisit->status === 'pending' && $data['status'] === 'pending') {
            return response()->json(['ok' => true, 'msg' => 'Updated Successfully']);
        }

        $dailyVisit->status = 'approved';
        $dailyVisit->save();

        return response()->json(['ok' => true, 'msg' => 'Status Updated Successfully']);
    }

    public function select2(Request $request)
    {
        $search = $request->input('q', '');
        $userId = $request->user_id;
        $query = DailyVisit::with('user')->where('user_id', $userId);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }



        $results = $query->limit(10)->get();

        return response()->json([
            'results' => $results->map(function ($item) {
                $text = $item->teacher
                    ? $item->teacher->teacher_name . ' - ' . ($item->teacher->institution?->institution_name ?? '-')
                    : ($item->library?->library_name ?? '-');
                return [
                    'id' => $item->id,
                    'text' => $text . ' - ' . Carbon::parse($item->created_at)->format('d M Y h:i A'),
                ];
            }),
        ]);
    }
}
