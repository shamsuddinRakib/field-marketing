<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherResource;
use App\Models\backend\CourseClass;
use App\Models\backend\Department;
use App\Models\backend\Institution;
use App\Models\backend\Subject;
use App\Models\backend\Teacher;
use App\Models\backend\Upazila;
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

        if ($request->filled('upazila_id')) {
            $upazilaId = $request->upazila_id;
            $query->where(function ($sub) use ($upazilaId) {
                $sub->where('upazila_id', $upazilaId)
                    ->orWhereHas('institution', fn($inst) => $inst->where('upazila_id', $upazilaId));
            });
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

    /**
     * Create a new teacher (from MR mobile app).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_name'   => ['required', 'string', 'max:191'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'designation'    => ['nullable', 'string', 'max:100'],
            'department'     => ['nullable', 'string', 'max:100'],
            'subject'        => ['nullable', 'string', 'max:100'],
            'class_name'     => ['nullable', 'string', 'max:100'],
            'upazila_id'     => ['nullable', 'integer', 'exists:upazilas,id'],
            'district_id'    => ['nullable', 'integer', 'exists:districts,district_id'],
            'division_id'    => ['nullable', 'integer', 'exists:divisions,id'],
            'address'        => ['nullable', 'string', 'max:255'],
        ]);

        $teacherData = $validated;

        // Auto-resolve Department ID to name if numeric ID passed (matches DailyVisitController pattern)
        if (!empty($teacherData['department']) && is_numeric($teacherData['department'])) {
            $department = Department::find($teacherData['department']);
            if ($department) {
                $teacherData['department'] = $department->department_name;
            }
        }

        // Auto-resolve Subject ID to name if numeric ID passed
        if (!empty($teacherData['subject']) && is_numeric($teacherData['subject'])) {
            $subject = Subject::find($teacherData['subject']);
            if ($subject) {
                $teacherData['subject'] = $subject->subject_name;
            }
        }

        // Auto-resolve Class ID to name if numeric ID passed
        if (!empty($teacherData['class_name']) && is_numeric($teacherData['class_name'])) {
            $courseClass = CourseClass::find($teacherData['class_name']);
            if ($courseClass) {
                $teacherData['class_name'] = $courseClass->name;
            }
        }

        // Auto-resolve location from institution if institution provided but location fields are omitted
        if (!empty($teacherData['institution_id']) && (empty($teacherData['upazila_id']) || empty($teacherData['district_id']) || empty($teacherData['division_id']))) {
            $institution = Institution::find($teacherData['institution_id']);
            if ($institution) {
                $teacherData['upazila_id']  = $teacherData['upazila_id'] ?? $institution->upazila_id;
                $teacherData['district_id'] = $teacherData['district_id'] ?? $institution->district_id;
                $teacherData['division_id'] = $teacherData['division_id'] ?? $institution->division_id;
                $teacherData['address']     = $teacherData['address'] ?? $institution->address;
            }
        } elseif (!empty($teacherData['upazila_id']) && (empty($teacherData['district_id']) || empty($teacherData['division_id']))) {
            $upazila = Upazila::with('upazila_district')->find($teacherData['upazila_id']);
            if ($upazila && $upazila->upazila_district) {
                $teacherData['district_id'] = $teacherData['district_id'] ?? $upazila->upazila_district->district_id;
                $teacherData['division_id'] = $teacherData['division_id'] ?? $upazila->upazila_district->district_division_id;
            }
        }

        $teacherData['status'] = 1;
        $teacher = Teacher::create($teacherData);

        $teacher->load([
            'institution.upazila',
            'institution.district',
            'institution.division',
            'upazila',
            'district',
            'division',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Teacher created successfully.',
            'data'    => new TeacherResource($teacher),
        ], 201);
    }
}
