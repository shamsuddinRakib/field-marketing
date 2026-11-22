<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\DailyVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

    public function reportIndex()
    {
        $now            = Carbon::now();
        $currentYear    = $now->year;
        $currentQuarter = $now->quarter;
        $years          = range(max(2024, $currentYear - 3), $currentYear + 1);
        rsort($years);

        return view('backend.modules.daily_visit.report', compact('currentYear', 'currentQuarter', 'years'));
    }

    public function reportListAjax(Request $request)
    {
        $filters = $this->resolveReportFilters($request);

        $uniqueTeacherExpr = $filters['type'] === 'library'
            ? '0 AS unique_teacher_q'
            : 'COUNT(DISTINCT dv.teacher_id) AS unique_teacher_q';
        $uniqueLibraryExpr = $filters['type'] === 'teacher'
            ? '0 AS unique_library_q'
            : 'COUNT(DISTINCT dv.library_id) AS unique_library_q';
        $rawTeacherExpr = $filters['type'] === 'library'
            ? '0 AS raw_teacher_q'
            : 'SUM(CASE WHEN dv.teacher_id IS NOT NULL THEN 1 ELSE 0 END) AS raw_teacher_q';
        $rawLibraryExpr = $filters['type'] === 'teacher'
            ? '0 AS raw_library_q'
            : 'SUM(CASE WHEN dv.library_id IS NOT NULL THEN 1 ELSE 0 END) AS raw_library_q';

        // Inner grouping per (MR, calendar year, calendar quarter) prevents same target visited
        // multiple times within the same 3-month quarter from counting more than once, while
        // automatically resetting and summing across separate quarters.
        $quarterAgg = $this->buildReportBaseQuery($filters)
            ->selectRaw("
                dv.user_id,
                YEAR(dv.created_at) AS visit_year,
                QUARTER(dv.created_at) AS visit_quarter,
                {$uniqueTeacherExpr},
                {$uniqueLibraryExpr},
                {$rawTeacherExpr},
                {$rawLibraryExpr},
                COUNT(*) AS raw_total_q
            ")
            ->groupBy('dv.user_id', DB::raw('YEAR(dv.created_at)'), DB::raw('QUARTER(dv.created_at)'));

        $mrSub = DB::table('marketing_representatives as mr1')
            ->join(
                DB::raw('(SELECT user_id, MAX(id) AS latest_id FROM marketing_representatives WHERE deleted_at IS NULL GROUP BY user_id) as mr2'),
                'mr1.id',
                '=',
                'mr2.latest_id'
            )
            ->select('mr1.user_id', 'mr1.employee_id');

        $records = DB::query()
            ->fromSub($quarterAgg, 'q')
            ->leftJoin('users as u', 'u.id', '=', 'q.user_id')
            ->leftJoinSub($mrSub, 'mr', 'mr.user_id', '=', 'q.user_id')
            ->selectRaw("
                q.user_id,
                COALESCE(u.name, CONCAT('User #', q.user_id)) AS mr_name,
                MAX(u.phone) AS mr_phone,
                MAX(mr.employee_id) AS employee_id,
                SUM(q.unique_teacher_q) AS unique_teacher_visits,
                SUM(q.unique_library_q) AS unique_library_visits,
                SUM(q.unique_teacher_q + q.unique_library_q) AS total_unique_visits,
                SUM(q.raw_teacher_q) AS raw_teacher_visits,
                SUM(q.raw_library_q) AS raw_library_visits,
                SUM(q.raw_total_q) AS total_raw_visits,
                COUNT(*) AS active_quarters
            ")
            ->groupBy('q.user_id', 'u.name')
            ->orderByDesc('total_unique_visits')
            ->orderByDesc('total_raw_visits')
            ->orderBy('mr_name')
            ->get();

        $filterQuery = [
            'period'     => $filters['period'],
            'year'       => $filters['year'],
            'start_date' => $filters['start_date'],
            'end_date'   => $filters['end_date'],
            'type'       => $filters['type'],
            'status'     => $filters['status'],
        ];

        $rows    = [];
        $summary = [
            'total_mrs'             => $records->count(),
            'unique_teacher_visits' => 0,
            'unique_library_visits' => 0,
            'total_unique_visits'   => 0,
            'total_raw_visits'      => 0,
        ];

        foreach ($records as $r) {
            $uniqueTeacher = (int) $r->unique_teacher_visits;
            $uniqueLibrary = (int) $r->unique_library_visits;
            $totalUnique   = (int) $r->total_unique_visits;
            $totalRaw      = (int) $r->total_raw_visits;

            $summary['unique_teacher_visits'] += $uniqueTeacher;
            $summary['unique_library_visits'] += $uniqueLibrary;
            $summary['total_unique_visits']   += $totalUnique;
            $summary['total_raw_visits']      += $totalRaw;

            $rows[] = [
                'user_id'               => (int) $r->user_id,
                'mr_name'               => $r->mr_name,
                'mr_phone'              => $r->mr_phone,
                'employee_id'           => $r->employee_id,
                'unique_teacher_visits' => $uniqueTeacher,
                'unique_library_visits' => $uniqueLibrary,
                'total_unique_visits'   => $totalUnique,
                'raw_teacher_visits'    => (int) $r->raw_teacher_visits,
                'raw_library_visits'    => (int) $r->raw_library_visits,
                'total_raw_visits'      => $totalRaw,
                'active_quarters'       => (int) $r->active_quarters,
                'details_url'           => route('daily-visit.report.details.show', ['user' => (int) $r->user_id]) . '?' . http_build_query($filterQuery),
            ];
        }

        return response()->json([
            'ok'      => true,
            'filters' => $filters,
            'summary' => $summary,
            'rows'    => $rows,
        ]);
    }

    public function reportDetailsModal(Request $request, int $user)
    {
        $filters = $this->resolveReportFilters($request);
        $userId  = $user;

        $userRecord = DB::table('users')
            ->select('id', 'name', 'phone')
            ->where('id', $userId)
            ->first() ?? (object) [
                'id'    => $userId,
                'name'  => 'User #' . $userId,
                'phone' => null,
            ];

        $mr = DB::table('marketing_representatives')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        $teacherRows = $filters['type'] !== 'library'
            ? $this->fetchVisitTargetBreakdown($filters, $userId, 'teacher')
            : collect();

        $libraryRows = $filters['type'] !== 'teacher'
            ? $this->fetchVisitTargetBreakdown($filters, $userId, 'library')
            : collect();

        $items = $teacherRows->concat($libraryRows)->sort(function ($a, $b) {
            return [(int) $b->unique_visits, (int) $b->raw_visits, (string) $b->last_visit_at]
                <=> [(int) $a->unique_visits, (int) $a->raw_visits, (string) $a->last_visit_at];
        })->values();

        $totals = [
            'unique_teachers'  => (int) $teacherRows->sum('unique_visits'),
            'unique_libraries' => (int) $libraryRows->sum('unique_visits'),
            'total_unique'     => (int) $items->sum('unique_visits'),
            'total_raw'        => (int) $items->sum('raw_visits'),
        ];

        return view('backend.modules.daily_visit.report_details_modal', [
            'user'    => $userRecord,
            'mr'      => $mr,
            'filters' => $filters,
            'items'   => $items,
            'totals'  => $totals,
        ]);
    }

    private function resolveReportFilters(Request $request): array
    {
        $allowedPeriods = ['current_quarter', 'last_quarter', 'q1', 'q2', 'q3', 'q4', 'all_quarters', 'custom'];
        $period         = in_array($request->input('period'), $allowedPeriods, true)
            ? $request->input('period')
            : 'current_quarter';

        $nowYear = Carbon::now()->year;
        $year    = (int) $request->input('year', $nowYear);
        $year    = max(2020, min($nowYear + 2, $year));

        if ($period === 'current_quarter') {
            $now         = Carbon::now();
            $start       = $now->copy()->firstOfQuarter()->startOfDay();
            $end         = $now->copy()->lastOfQuarter()->endOfDay();
            $periodLabel = "{$now->year} Q{$now->quarter} (Current Quarter)";
        } elseif ($period === 'last_quarter') {
            $prev        = Carbon::now()->firstOfQuarter()->subDay();
            $start       = $prev->copy()->firstOfQuarter()->startOfDay();
            $end         = $prev->copy()->lastOfQuarter()->endOfDay();
            $periodLabel = "{$prev->year} Q{$prev->quarter} (Last Quarter)";
        } elseif (in_array($period, ['q1', 'q2', 'q3', 'q4'], true)) {
            $qNum        = (int) substr($period, 1);
            $startMonth  = ($qNum - 1) * 3 + 1;
            $start       = Carbon::create($year, $startMonth, 1)->startOfDay();
            $end         = $start->copy()->lastOfQuarter()->endOfDay();
            $periodLabel = "{$year} Q{$qNum}";
        } elseif ($period === 'all_quarters') {
            $start       = Carbon::create($year, 1, 1)->startOfDay();
            $end         = Carbon::create($year, 12, 31)->endOfDay();
            $periodLabel = "{$year} (All Quarters)";
        } else {
            try {
                $rawStart = trim((string) $request->input('start_date', ''));
                $rawEnd   = trim((string) $request->input('end_date', ''));
                if ($rawStart === '' || $rawEnd === '') {
                    throw new \InvalidArgumentException('Missing custom date range.');
                }
                $start = Carbon::parse($rawStart)->startOfDay();
                $end   = Carbon::parse($rawEnd)->endOfDay();
                if ($start->gt($end)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }
                $periodLabel = $start->format('d M Y') . ' – ' . $end->format('d M Y');
            } catch (\Throwable $e) {
                $now         = Carbon::now();
                $period      = 'current_quarter';
                $start       = $now->copy()->firstOfQuarter()->startOfDay();
                $end         = $now->copy()->lastOfQuarter()->endOfDay();
                $periodLabel = "{$now->year} Q{$now->quarter} (Current Quarter)";
            }
        }

        $userId = (int) $request->input('user_id', 0);
        $userId = $userId > 0 ? $userId : null;

        $type   = in_array($request->input('type'), ['teacher', 'library'], true) ? $request->input('type') : 'all';
        $status = in_array($request->input('status'), ['pending', 'approved'], true) ? $request->input('status') : 'all';

        return [
            'period'       => $period,
            'year'         => $year,
            'start_date'   => $start->toDateString(),
            'end_date'     => $end->toDateString(),
            'start_at'     => $start->toDateTimeString(),
            'end_at'       => $end->toDateTimeString(),
            'period_label' => $periodLabel,
            'user_id'      => $userId,
            'type'         => $type,
            'status'       => $status,
        ];
    }

    private function buildReportBaseQuery(array $filters, ?int $forceUserId = null)
    {
        $query = DB::table('daily_visits as dv')
            ->whereBetween('dv.created_at', [$filters['start_at'], $filters['end_at']]);

        $targetUserId = $forceUserId ?? $filters['user_id'];
        if ($targetUserId) {
            $query->where('dv.user_id', $targetUserId);
        }

        if ($filters['type'] === 'teacher') {
            $query->whereNotNull('dv.teacher_id');
        } elseif ($filters['type'] === 'library') {
            $query->whereNotNull('dv.library_id');
        } else {
            $query->where(function ($q) {
                $q->whereNotNull('dv.teacher_id')->orWhereNotNull('dv.library_id');
            });
        }

        if (in_array($filters['status'], ['pending', 'approved'], true)) {
            $query->where('dv.status', $filters['status']);
        }

        return $query;
    }

    private function fetchVisitTargetBreakdown(array $filters, int $userId, string $targetType)
    {
        $isTeacher = $targetType === 'teacher';
        $fkCol     = $isTeacher ? 'dv.teacher_id' : 'dv.library_id';

        $aggCols = "
            COUNT(*) AS raw_visits,
            COUNT(DISTINCT CONCAT(YEAR(dv.created_at), '-Q', QUARTER(dv.created_at))) AS unique_visits,
            SUM(CASE WHEN dv.status = 'approved' THEN 1 ELSE 0 END) AS approved_raw_visits,
            SUM(CASE WHEN dv.status = 'pending' THEN 1 ELSE 0 END) AS pending_raw_visits,
            GROUP_CONCAT(DISTINCT CONCAT(YEAR(dv.created_at), ' Q', QUARTER(dv.created_at)) ORDER BY YEAR(dv.created_at), QUARTER(dv.created_at) SEPARATOR ', ') AS quarters_visited,
            MAX(dv.created_at) AS last_visit_at
        ";

        $q = $this->buildReportBaseQuery($filters, $userId)->whereNotNull($fkCol);

        if ($isTeacher) {
            return $q->leftJoin('teachers as t', 't.id', '=', 'dv.teacher_id')
                ->leftJoin('institutions as inst', 'inst.id', '=', 't.institution_id')
                ->selectRaw("
                    'teacher' AS visit_type,
                    dv.teacher_id AS target_id,
                    COALESCE(t.teacher_name, CONCAT('Teacher #', dv.teacher_id)) AS target_name,
                    inst.institution_name AS extra_info,
                    t.phone AS phone,
                    {$aggCols}
                ")
                ->groupBy('dv.teacher_id', 't.teacher_name', 't.phone', 'inst.institution_name')
                ->get();
        }

        return $q->leftJoin('libraries as l', 'l.id', '=', 'dv.library_id')
            ->selectRaw("
                'library' AS visit_type,
                dv.library_id AS target_id,
                COALESCE(l.library_name, CONCAT('Library #', dv.library_id)) AS target_name,
                l.address AS extra_info,
                l.phone AS phone,
                {$aggCols}
            ")
            ->groupBy('dv.library_id', 'l.library_name', 'l.phone', 'l.address')
            ->get();
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
