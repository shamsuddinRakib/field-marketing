<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\DailyVisit;
use Illuminate\Http\Request;

class DailyVisitController extends Controller
{
    public function index1(Request $request)
    {
        $visits = DailyVisit::with([
            'teacher',
            'library',
        ])
            ->where('user_id', auth()->id())
            ->latest('visit_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $visits,
        ]);
    }

    public function index(Request $request)
    {
        $query = DailyVisit::with([
            'teacher',
            'library',
        ])
            ->where('user_id', auth()->id());

        // Single date filter
        if ($request->filled('date')) {
            $query->whereDate('visit_date', $request->date);
        }

        // Month filter
        if ($request->filled('month')) {
            $query->whereYear('visit_date', substr($request->month, 0, 4))
                ->whereMonth('visit_date', substr($request->month, 5, 2));
        }

        // Date range filter
        if ($request->filled('from_date')) {
            $query->whereDate('visit_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('visit_date', '<=', $request->to_date);
        }

        // Total visit count according to selected filter
        $totalVisits = (clone $query)->count();

        $visits = $query
            ->orderBy('visit_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(15);

        return response()->json([
            'success' => true,

            'summary' => [
                'total_visits' => $totalVisits,
            ],

            'filters' => [
                'date' => $request->date,
                'month' => $request->month,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ],

            'data' => $visits,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'library_id' => ['nullable', 'integer', 'exists:libraries,id'],
            'visit_date' => ['required', 'date_format:Y-m-d'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'status' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        // Check duplicate visit for same MR, teacher and date
        $existingVisit = DailyVisit::where('user_id', auth()->id())
            ->where('teacher_id', $request->teacher_id)
            ->whereDate('visit_date', $request->visit_date)
            ->first();

        if ($existingVisit) {
            return response()->json([
                'success' => false,
                'message' => 'You have already recorded a visit for this teacher on this date.',
            ], 422);
        }

        $visit = DailyVisit::create([
            'user_id' => auth()->id(),
            'teacher_id' => $request->teacher_id,
            'library_id' => $request->library_id,
            'visit_date' => $request->visit_date,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => $request->status,
            'note' => $request->note,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Daily visit created successfully.',
            'data' => $visit->load([
                'teacher',
                'library',
            ]),
        ], 201);
    }

    public function show($id)
    {
        $visit = DailyVisit::with([
            'teacher',
            'library',
        ])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $visit,
        ]);
    }
}
