<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\CourseClass;
use App\Models\backend\DailyVisit;
use App\Models\backend\Department;
use App\Models\backend\Library;
use App\Models\backend\Subject;
use App\Models\backend\Teacher;
use App\Models\backend\Upazila;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;


class DailyVisitController extends Controller
{
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

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
    //         'library_id' => ['nullable', 'integer', 'exists:libraries,id'],
    //         'visit_date' => ['required', 'date_format:Y-m-d'],
    //         'latitude' => ['nullable', 'numeric'],
    //         'longitude' => ['nullable', 'numeric'],
    //         'status' => ['required', 'string'],
    //         'note' => ['nullable', 'string'],
    //     ]);

    //     // Check duplicate visit for same MR, teacher and date
    //     $existingVisit = DailyVisit::where('user_id', auth()->id())
    //         ->where('teacher_id', $request->teacher_id)
    //         ->whereDate('visit_date', $request->visit_date)
    //         ->first();

    //     if ($existingVisit) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'You have already recorded a visit for this teacher on this date.',
    //         ], 422);
    //     }

    //     $visit = DailyVisit::create([
    //         'user_id' => auth()->id(),
    //         'teacher_id' => $request->teacher_id,
    //         'library_id' => $request->library_id,
    //         'visit_date' => $request->visit_date,
    //         'latitude' => $request->latitude,
    //         'longitude' => $request->longitude,
    //         'status' => $request->status,
    //         'note' => $request->note,
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Daily visit created successfully.',
    //         'data' => $visit->load([
    //             'teacher',
    //             'library',
    //         ]),
    //     ], 201);
    // }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Existing customer IDs
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'library_id' => ['nullable', 'integer', 'exists:libraries,id'],

            // New Teacher
            'new_teacher' => ['nullable', 'array'],
            'new_teacher.teacher_name' => ['required_with:new_teacher', 'string', 'max:191'],
            'new_teacher.phone' => ['nullable', 'string', 'max:20'],
            'new_teacher.institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'new_teacher.designation' => ['nullable', 'string', 'max:100'],
            'new_teacher.department' => ['nullable', 'string', 'max:100'],
            'new_teacher.subject' => ['nullable', 'string', 'max:100'],
            'new_teacher.class_name' => ['nullable', 'string', 'max:100'],

            // New Library
            'new_library' => ['nullable', 'array'],
            'new_library.library_name' => ['required_with:new_library', 'string', 'max:191'],
            'new_library.code' => ['nullable', 'string', 'max:100', Rule::unique('libraries', 'code')],
            'new_library.email' => ['nullable', 'email', 'max:191'],
            'new_library.phone' => ['required_with:new_library', 'string', 'max:50'],
            'new_library.upazila_id' => ['nullable', 'integer', 'exists:upazilas,id'],
            'new_library.address' => ['nullable', 'string', 'max:1000'],

            // Daily Visit
            'visit_date' => ['required', 'date_format:Y-m-d'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'status' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        // One request cannot create/select both customer types.
        $hasTeacher = !empty($validated['teacher_id']) || !empty($validated['new_teacher']);
        $hasLibrary = !empty($validated['library_id']) || !empty($validated['new_library']);

        if ($hasTeacher && $hasLibrary) {
            throw ValidationException::withMessages([
                'customer' => [
                    'Please select either a teacher or a library, not both.',
                ],
            ]);
        }

        if (!$hasTeacher && !$hasLibrary) {
            throw ValidationException::withMessages([
                'customer' => [
                    'Please select an existing teacher/library or provide new customer details.',
                ],
            ]);
        }

        $visit = DB::transaction(function () use ($validated) {

            $teacherId = $validated['teacher_id'] ?? null;
            $libraryId = $validated['library_id'] ?? null;

            // Create New Teacher

            if (!empty($validated['new_teacher'])) {

                $teacherData = $validated['new_teacher'];

                // Convert Department ID to department name
                if (
                    isset($teacherData['department'])
                    && is_numeric($teacherData['department'])
                ) {
                    $department = Department::find(
                        $teacherData['department']
                    );

                    if (!$department) {
                        throw ValidationException::withMessages([
                            'new_teacher.department' => [
                                'Invalid department selected.',
                            ],
                        ]);
                    }

                    $teacherData['department'] = $department->department_name;
                }

                // Convert Subject ID to subject name
                if (
                    isset($teacherData['subject'])
                    && is_numeric($teacherData['subject'])
                ) {
                    $subject = Subject::find(
                        $teacherData['subject']
                    );

                    if (!$subject) {
                        throw ValidationException::withMessages([
                            'new_teacher.subject' => [
                                'Invalid subject selected.',
                            ],
                        ]);
                    }

                    $teacherData['subject'] = $subject->subject_name;
                }

                // Convert Class ID to class name
                if (
                    isset($teacherData['class_name'])
                    && is_numeric($teacherData['class_name'])
                ) {
                    $courseClass = CourseClass::find(
                        $teacherData['class_name']
                    );

                    if (!$courseClass) {
                        throw ValidationException::withMessages([
                            'new_teacher.class_name' => [
                                'Invalid class selected.',
                            ],
                        ]);
                    }

                    $teacherData['class_name'] = $courseClass->name;
                }

                $teacherData['status'] = 1;
                $teacher = Teacher::create($teacherData);
                $teacherId = $teacher->id;
            }

            // Create New Library

            if (!empty($validated['new_library'])) {

                $libraryData = $validated['new_library'];

                // Resolve District and Division from Upazila
                if (!empty($libraryData['upazila_id'])) {

                    $upazila = Upazila::with([
                        'upazila_district.district_division',
                    ])->findOrFail($libraryData['upazila_id']);

                    $district = $upazila->upazila_district;

                    if (!$district) {
                        throw ValidationException::withMessages([
                            'new_library.upazila_id' => [
                                'District information not found for selected upazila.',
                            ],
                        ]);
                    }

                    $division = $district->district_division;

                    if (!$division) {
                        throw ValidationException::withMessages([
                            'new_library.upazila_id' => [
                                'Division information not found for selected upazila.',
                            ],
                        ]);
                    }

                    $libraryData['district_id'] = $district->district_id;
                    $libraryData['division_id'] = $division->id;
                }

                $libraryData['status'] = 1;
                $library = Library::create($libraryData);
                $libraryId = $library->id;
            }

            // Duplicate Visit Check

            $duplicateQuery = DailyVisit::where('user_id', auth()->id())->whereDate('visit_date', $validated['visit_date']);

            if ($teacherId) {
                $duplicateQuery->where('teacher_id', $teacherId);
            } elseif ($libraryId) {
                $duplicateQuery->where('library_id', $libraryId);
            }

            if ($duplicateQuery->exists()) {
                throw ValidationException::withMessages([
                    'customer' => [
                        'You have already recorded a visit for this customer on this date.',
                    ],
                ]);
            }

            // Create Daily Visit 

            $visit = DailyVisit::create([
                'user_id' => auth()->id(),
                'teacher_id' => $teacherId,
                'library_id' => $libraryId,
                'visit_date' => $validated['visit_date'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
            ]);

            return $visit;
        });

        return response()->json([
            'success' => true,
            'message' => 'Daily visit created successfully.',
            'data' => $visit->load([
                'teacher.institution',
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
