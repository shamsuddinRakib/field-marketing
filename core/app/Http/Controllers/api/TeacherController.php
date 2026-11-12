<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherResource;
use App\Models\backend\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    /**
     * Active teachers list (dropdown / search for the app).
     */
    public function index(Request $request)
    {
        // $query = Teacher::query()
        //     ->with('institution:id,institution_name,code')
        //     ->where('status', 1);

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $query = Teacher::query()
            ->with([
                'institution.upazila',
                'institution.district',
                'institution.division',
                'upazila',
                'district',
                'division',
            ])
            ->where('status', 1);

        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        // if ($request->filled('search')) {
        //     $search = $request->input('search');
        //     $query->where(function ($q) use ($search) {
        //         $q->where('teacher_name', 'like', "%{$search}%")
        //             ->orWhere('designation', 'like', "%{$search}%")
        //             ->orWhere('subject', 'like', "%{$search}%")
        //             ->orWhere('department', 'like', "%{$search}%");
        //     });
        // }
        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('teacher_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('class_name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhereHas('institution', function ($institutionQuery) use ($search) {
                        $institutionQuery->where('institution_name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('upazila', function ($locationQuery) use ($search) {
                        $locationQuery->where('upazila_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('district', function ($locationQuery) use ($search) {
                        $locationQuery->where('district_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('division', function ($locationQuery) use ($search) {
                        $locationQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // $teachers = $query->orderBy('teacher_name')->get();
        $teachers = $query->orderBy('teacher_name')->paginate($perPage);

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Teachers fetched successfully.',
        //     'data' => $teachers->map(fn($teacher) => [
        //         'id' => $teacher->id,
        //         'name' => $teacher->teacher_name,
        //         'designation' => $teacher->designation,
        //         'department' => $teacher->department,
        //         'subject' => $teacher->subject,
        //         'class_name' => $teacher->class_name,
        //         'institution' => $teacher->institution ? [
        //             'id' => $teacher->institution->id,
        //             'name' => $teacher->institution->institution_name,
        //             'code' => $teacher->institution->code,
        //         ] : null,
        //     ]),
        // ]);


        return response()->json([
            'success' => true,
            'message' => 'Teachers fetched successfully.',
            'data' => TeacherResource::collection($teachers->items()),
            'pagination' => [
                'current_page' => $teachers->currentPage(),
                'last_page' => $teachers->lastPage(),
                'per_page' => $teachers->perPage(),
                'total' => $teachers->total(),
                'next_page_url' => $teachers->nextPageUrl(),
                'prev_page_url' => $teachers->previousPageUrl(),
            ],
        ]);
    }
}
