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
        $columns   = ['id', 'user_id', 'teacher_id', 'library_id', 'note', 'status'];
        $draw      = (int) $request->input('draw');
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderIdx  = (int) $request->input('order.0.column', 0);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));

        $base = DailyVisit::query()->select(['id', 'user_id', 'teacher_id', 'library_id', 'note', 'status']);

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
            // $b->load('teacher');
            // dd($b);

            // $active = $b->is_active
            //     ? '<span class="badge text-sm fw-semibold bg-dark-success-gradient px-20 py-9 radius-4 text-white">Active</span>'
            //     : '<span class="badge text-sm fw-semibold bg-dark-warning-gradient px-20 py-9 radius-4 text-white">Inactive</span>';

            // $actions = '<div class="d-inline-flex justify-content-end gap-1 w-100">
            //     <a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
            //         bg-success-focus text-success-main AjaxModal"
            //         data-ajax-modal="' . route('daily-visit.editModal', $b->id) . '"
            //         data-size="lg"
            //         data-onsuccess="productDistributionIndex.onSaved"
            //         title="Edit">
            //         <iconify-icon icon="lucide:edit"></iconify-icon>
            //     </a>
            //     <a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-branch-delete"
            //         data-id="' . $b->id . '"
            //         data-url="' . route('daily-visit.destroy', $b->id) . '"
            //         title="Delete">
            //         <iconify-icon icon="mdi:delete"></iconify-icon>
            //     </a>
            // </div>';

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
                            data-ajax-modal="' . route('daily-visit.statusModal', $b->id) . '" 
                            data-onsuccess="BranchesIndex.onSaved"
                            data-size="sm">' . $statusLabel . '</a>';

            $data[] = [
                $b->id,
                $nameCol,
                $b->teacher ? 'Teacher' : 'Library',
                $b->teacher?->teacher_name ?? $b->library?->library_name ?? '-',
                $b->note,
                $statusBadge
                // $actions,
            ];
        }

        return response()->json([
            'draw'                 => $draw,
            'iTotalRecords'        => $total,
            'iTotalDisplayRecords' => $filtered,
            'aaData'               => $data,
        ]);
    }

    public function statusModal(DailyVisit $dailyVisit)
    {
        return view('backend.modules.daily_visit.statusModal', compact('dailyVisit'));
    }


    public function updateStatus(DailyVisit $dailyVisit)
    {
        // dd($fundDistribution);
        if ($dailyVisit->status === 'approved') {
            return response()->json(['ok' => false, 'msg' => ['Approved record cannot be updated']], 402);
        }



        $dailyVisit->status = 'approved';
        $dailyVisit->save();

        return response()->json(['success' => true, 'msg' => 'Status Updated Successfully']);
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
