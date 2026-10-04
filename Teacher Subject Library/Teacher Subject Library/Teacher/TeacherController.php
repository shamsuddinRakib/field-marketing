<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\CourseClass;
use App\Models\backend\Department;
use App\Models\backend\Institution;
use App\Models\backend\Subject;
use App\Models\backend\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TeacherController extends Controller
{
    public function index()
    {
        return view('backend.modules.teachers.index');
    }

    public function createModal()
    {
        return view('backend.modules.teachers.create_modal');
    }

    public function editModal(Teacher $teacher)
    {
        $teacher->load('institution');
        return view('backend.modules.teachers.edit_modal', compact('teacher'));
    }

    public function listAjax(Request $request)
    {
        if (!Schema::hasTable('teachers')) {
            return response()->json([
                'draw' => (int) $request->input('draw'),
                'iTotalRecords' => 0,
                'iTotalDisplayRecords' => 0,
                'aaData' => [],
            ]);
        }

        $columns = ['id', 'teacher_name', 'institution_name', 'designation', 'department', 'subject', 'class_name', 'status'];
        $draw = (int) $request->input('draw');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim($request->input('search.value', ''));

        $query = Teacher::query()
            ->leftJoin('institutions as i', 'i.id', '=', 'teachers.institution_id')
            ->select('teachers.*', 'i.institution_name as inst_name', 'i.name as inst_name2', \Illuminate\Support\Facades\DB::raw("COALESCE(i.institution_name, i.name) as institution_name"));

        $total = (clone $query)->count('teachers.id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('teachers.teacher_name', 'like', "%{$search}%")
                    ->orWhere('teachers.designation', 'like', "%{$search}%")
                    ->orWhere('teachers.department', 'like', "%{$search}%")
                    ->orWhere('teachers.subject', 'like', "%{$search}%")
                    ->orWhere('teachers.class_name', 'like', "%{$search}%")
                    ->orWhere('i.institution_name', 'like', "%{$search}%")
                    ->orWhere('i.name', 'like', "%{$search}%")
                    ->orWhere('i.code', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count('teachers.id');
        $orderColumn = $columns[$orderIndex] ?? 'id';
        $orderMap = [
            'institution_name' => 'institution_name',
        ];
        $orderBy = $orderMap[$orderColumn] ?? 'teachers.' . $orderColumn;
        if ($orderColumn === 'institution_name') {
            $orderBy = 'institution_name';
        }
        $query->orderBy($orderBy, $orderDirection);

        $data = $query->skip($start)->take($length)->get()->map(function ($teacher) {
            $status = $teacher->status
                ? '<span class="bg-success-focus text-success-600 border border-success-main px-12 py-4 radius-4 fw-medium text-sm">Active</span>'
                : '<span class="bg-danger-focus text-danger-600 border border-danger-main px-12 py-4 radius-4 fw-medium text-sm">Inactive</span>';

            $editUrl = \Illuminate\Support\Facades\Route::has('teacher.teachers.editModal')
                ? route('teacher.teachers.editModal', $teacher->id)
                : '#';
            $deleteUrl = \Illuminate\Support\Facades\Route::has('teacher.teachers.destroy')
                ? route('teacher.teachers.destroy', $teacher->id)
                : '#';

            $actions = '<div class="d-inline-flex align-items-center justify-content-end gap-1 w-100">'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" data-ajax-modal="' . $editUrl . '" data-size="lg" data-onsuccess="TeachersIndex.onSaved" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-teacher-delete" data-url="' . $deleteUrl . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>'
                . '</div>';

            $teacherName = $teacher->teacher_name;
            $institutionName = $teacher->institution_name ?: $teacher->inst_name ?: $teacher->inst_name2;

            return [
                (int) $teacher->id,
                e($teacherName ?: '-'),
                e($institutionName ?: '-'),
                e($teacher->designation ?: '-'),
                e($teacher->department ?: '-'),
                e($teacher->subject ?: '-'),
                e($teacher->class_name ?: '-'),
                $status,
                $actions,
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $filtered,
            'aaData' => $data,
        ]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('teachers')) {
            return response()->json(['ok' => false, 'msg' => 'Teachers table not migrated yet.'], 422);
        }
        $teacher = Teacher::create($this->validated($request));

        return response()->json(['ok' => true, 'id' => $teacher->id, 'msg' => 'Teacher created.']);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $teacher->update($this->validated($request, $teacher));

        return response()->json(['ok' => true, 'msg' => 'Teacher updated.']);
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();

        return response()->json(['ok' => true, 'msg' => 'Teacher deleted.']);
    }

    public function institutionsSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $institutions = Institution::query()
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($b) use ($term) {
                    $b->where('institution_name', 'like', "%{$term}%")
                      ->orWhere('name', 'like', "%{$term}%")
                      ->orWhere('code', 'like', "%{$term}%");
                });
            })
            ->orderBy('institution_name')
            ->orderBy('name')
            ->limit(30)
            ->get();

        return response()->json(['results' => $institutions->map(function ($inst) {
            $name = $inst->institution_name ?: $inst->name;
            return [
                'id' => $inst->id,
                'text' => $name . ($inst->code ? " ({$inst->code})" : ''),
            ];
        })->values()]);
    }

    private function validated(Request $request, ?Teacher $teacher = null): array
    {
        $data = $request->validate([
            'teacher_name' => ['required', 'string', 'max:191'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:100'],
            'class_name' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'boolean'],
        ]);

        // Resolve department: if select2 sent department ID (numeric), convert to department_name
        // Keeps `teachers.department` as string (e.g. "Science") but source is dynamic from `department` table
        if (!empty($data['department']) && ctype_digit((string) $data['department'])) {
            $dept = Department::find((int) $data['department']);
            if ($dept) {
                $data['department'] = $dept->department_name;
            }
        }

        // Resolve subject: if select2 sent subject ID (numeric), convert to subject_name
        if (!empty($data['subject']) && ctype_digit((string) $data['subject'])) {
            $subj = Subject::find((int) $data['subject']);
            if ($subj) {
                $data['subject'] = $subj->subject_name;
            }
        }

        // Resolve class: if select2 sent CourseClass ID (numeric), convert to name
        if (!empty($data['class_name']) && ctype_digit((string) $data['class_name'])) {
            $cls = CourseClass::find((int) $data['class_name']);
            if ($cls) {
                $data['class_name'] = $cls->name;
            }
        }

        return $data;
    }
}
