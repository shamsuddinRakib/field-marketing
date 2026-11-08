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
        $teacher->loadMissing([
            'institution.district',
            'institution.upazila',
            'institution.division',
            'district',
            'upazila',
            'division',
        ]);
        return view('backend.modules.teachers.edit_modal', compact('teacher'));
    }

    public function importModal()
    {
        return view('backend.modules.teachers.import_modal');
    }

    public function importChunk(Request $request)
    {
        if (!Schema::hasTable('teachers')) {
            return response()->json(['ok' => false, 'msg' => 'Teachers table not found.'], 422);
        }

        $rows = $request->input('rows', []);
        if (!is_array($rows) || empty($rows)) {
            return response()->json(['ok' => true, 'inserted' => 0, 'skipped' => 0, 'errors' => []]);
        }

        $defaultInstitutionId = $request->input('default_institution_id');
        $defaultInst = !empty($defaultInstitutionId) ? Institution::find($defaultInstitutionId) : null;

        $inserted = 0;
        $skipped = 0;
        $insertedItems = [];
        $skippedItems = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNum = $row['row_num'] ?? ($index + 1);
            $teacherName = trim($row['teacher_name'] ?? $row['name'] ?? '');
            $rawPhone = trim((string)($row['phone'] ?? $row['mobile'] ?? ''));

            // Clean phone number (strip hyphens, spaces, leading 880)
            $phoneDigits = preg_replace('/[^\d]/', '', $rawPhone);
            if (str_starts_with($phoneDigits, '880') && strlen($phoneDigits) === 13) {
                $phone = '0' . substr($phoneDigits, 3);
            } elseif (str_starts_with($phoneDigits, '00880') && strlen($phoneDigits) === 15) {
                $phone = '0' . substr($phoneDigits, 5);
            } elseif (strlen($phoneDigits) === 10 && str_starts_with($phoneDigits, '1')) {
                $phone = '0' . $phoneDigits;
            } else {
                $phone = $phoneDigits;
            }

            // Skip rows that don't have both teacher_name and phone (e.g. area headings like "জামালপুর সদর" without phone)
            if ($teacherName === '' || $phone === '') {
                continue;
            }

            // If teacher_name is blank but phone exists, fallback name
            if ($teacherName === '' && $phone !== '') {
                $teacherName = 'Teacher ' . substr($phone, -4);
            }

            $instName = trim($row['institution_name'] ?? $row['institute'] ?? $row['institution'] ?? '');
            $districtName = trim($row['district'] ?? '');
            $upazilaName = trim($row['upazila'] ?? $row['upazilla'] ?? '');
            $address = trim($row['address'] ?? '');

            // Resolve District & Upazila for teacher location
            $district = function_exists('resolve_district_by_name') ? resolve_district_by_name($districtName) : null;
            $districtId = $district ? $district->district_id : null;
            $divisionId = $district ? $district->district_division_id : null;

            $upazila = function_exists('resolve_upazila_by_name') ? resolve_upazila_by_name($upazilaName, $districtId) : null;
            $upazilaId = $upazila ? $upazila->id : null;

            if (!$districtId && $upazila && $upazila->upazila_district) {
                $districtId = $upazila->upazila_district->district_id;
                $divisionId = $upazila->upazila_district->district_division_id;
            }

            // Institution is excluded during bulk teacher import as per requirement
            $institutionId = null;

            // Resolve subjects: support multiple comma / plus / slash separated subjects
            $subjectRaw = trim($row['subject'] ?? '');
            $subject = null;
            if ($subjectRaw !== '') {
                $subParts = preg_split('/[,+\/]+/', $subjectRaw);
                $subPartsClean = array_filter(array_map('trim', $subParts));
                $subject = !empty($subPartsClean) ? implode(', ', $subPartsClean) : $subjectRaw;
            }

            // Resolve classes: support multiple comma / slash / dash separated classes
            $classRaw = trim($row['class_name'] ?? $row['class'] ?? '');
            $className = null;
            if ($classRaw !== '') {
                $clsParts = preg_split('/[,+\/]+/', $classRaw);
                $clsPartsClean = array_filter(array_map('trim', $clsParts));
                $className = !empty($clsPartsClean) ? implode(', ', $clsPartsClean) : $classRaw;
            }

            $designation = trim($row['designation'] ?? '');
            $department = trim($row['department'] ?? '');
            $statusRaw = trim($row['status'] ?? 'Active');
            $status = in_array(strtolower($statusRaw), ['1', 'active', 'true', 'সক্রিয়']) ? 1 : 0;

            // Duplicate Check Policy:
            // 1. If phone is present: duplicate if teacher with same phone exists.
            // 2. If phone is empty: duplicate if teacher with same teacher_name exists.
            $dupQuery = Teacher::query();
            $skipReason = '';
            if ($phone !== '') {
                $dupQuery->where('phone', $phone);
                $skipReason = "Phone '{$phone}' already exists in database";
            } elseif ($institutionId) {
                $dupQuery->where('teacher_name', $teacherName)->where('institution_id', $institutionId);
                $skipReason = "Teacher '{$teacherName}' already exists for this institution";
            } else {
                $dupQuery->where('teacher_name', $teacherName);
                $skipReason = "Teacher '{$teacherName}' already exists";
            }

            if ($dupQuery->exists()) {
                $skipped++;
                $skippedItems[] = [
                    'row'         => $rowNum,
                    'name'        => $teacherName,
                    'phone'       => $phone ?: '-',
                    'subject'     => $subject ?: '-',
                    'class'       => $className ?: '-',
                    'reason'      => $skipReason,
                ];
                continue;
            }

            try {
                Teacher::create([
                    'teacher_name'   => $teacherName,
                    'phone'          => $phone !== '' ? $phone : null,
                    'institution_id' => null,
                    'designation'    => $designation ?: null,
                    'department'     => $department ?: null,
                    'subject'        => $subject ?: null,
                    'class_name'     => $className ?: null,
                    'upazila_id'     => $upazilaId,
                    'district_id'    => $districtId,
                    'division_id'    => $divisionId,
                    'address'        => $address !== '' ? $address : null,
                    'status'         => $status,
                ]);
                $inserted++;
                $insertedItems[] = [
                    'row'         => $rowNum,
                    'name'        => $teacherName,
                    'phone'       => $phone ?: '-',
                    'subject'     => $subject ?: '-',
                    'class'       => $className ?: '-',
                    'upazila'     => $upazila ? $upazila->upazila_name : ($upazilaName ?: '-'),
                    'district'    => $district ? $district->district_name : ($districtName ?: '-'),
                    'address'     => $address ?: '-',
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'row'     => $rowNum,
                    'name'    => $teacherName,
                    'phone'   => $phone ?: '-',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'ok'             => true,
            'inserted'       => $inserted,
            'skipped'        => $skipped,
            'inserted_items' => $insertedItems,
            'skipped_items'  => $skippedItems,
            'errors'         => $errors,
        ]);
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

        $columns = [
            'id', 'teacher_name', 'phone', 'institution_name', 'designation',
            'department', 'subject', 'class_name', 'upazila', 'district',
            'division', 'address', 'status', 'id'
        ];
        $draw = (int) $request->input('draw');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim($request->input('search.value', ''));

        $query = Teacher::query()
            ->leftJoin('institutions as i', 'i.id', '=', 'teachers.institution_id')
            ->leftJoin('upazilas as tu', 'tu.id', '=', 'teachers.upazila_id')
            ->leftJoin('districts as td', 'td.district_id', '=', 'teachers.district_id')
            ->leftJoin('divisions as tdiv', 'tdiv.id', '=', 'teachers.division_id')
            ->leftJoin('upazilas as iu', 'iu.id', '=', 'i.upazila_id')
            ->leftJoin('districts as idist', 'idist.district_id', '=', 'i.district_id')
            ->leftJoin('divisions as idiv', 'idiv.id', '=', 'i.division_id')
            ->select(
                'teachers.*',
                'i.institution_name as inst_name',
                \Illuminate\Support\Facades\DB::raw("COALESCE(i.institution_name) as institution_name"),
                \Illuminate\Support\Facades\DB::raw("COALESCE(tu.upazila_name, iu.upazila_name) as display_upazila"),
                \Illuminate\Support\Facades\DB::raw("COALESCE(td.district_name, idist.district_name) as display_district"),
                \Illuminate\Support\Facades\DB::raw("COALESCE(tdiv.name, tdiv.bn_name, idiv.name, idiv.bn_name) as display_division"),
                \Illuminate\Support\Facades\DB::raw("COALESCE(teachers.address, i.address) as display_address")
            );

        $total = (clone $query)->count('teachers.id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('teachers.teacher_name', 'like', "%{$search}%")
                    ->orWhere('teachers.phone', 'like', "%{$search}%")
                    ->orWhere('teachers.designation', 'like', "%{$search}%")
                    ->orWhere('teachers.department', 'like', "%{$search}%")
                    ->orWhere('teachers.subject', 'like', "%{$search}%")
                    ->orWhere('teachers.class_name', 'like', "%{$search}%")
                    ->orWhere('teachers.address', 'like', "%{$search}%")
                    ->orWhere('tu.upazila_name', 'like', "%{$search}%")
                    ->orWhere('td.district_name', 'like', "%{$search}%")
                    ->orWhere('tdiv.name', 'like', "%{$search}%")
                    ->orWhere('tdiv.bn_name', 'like', "%{$search}%")
                    ->orWhere('iu.upazila_name', 'like', "%{$search}%")
                    ->orWhere('idist.district_name', 'like', "%{$search}%")
                    ->orWhere('idiv.name', 'like', "%{$search}%")
                    ->orWhere('idiv.bn_name', 'like', "%{$search}%")
                    ->orWhere('i.institution_name', 'like', "%{$search}%")
                    ->orWhere('i.code', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count('teachers.id');
        $orderColumn = $columns[$orderIndex] ?? 'id';
        $orderMap = [
            'institution_name' => 'institution_name',
            'upazila'          => 'display_upazila',
            'district'         => 'display_district',
            'division'         => 'display_division',
            'address'          => 'display_address',
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
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" data-ajax-modal="' . $editUrl . '" data-size="lg" data-onload="TeachersIndex.onLoad" data-onsuccess="TeachersIndex.onSaved" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-teacher-delete" data-url="' . $deleteUrl . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>'
                . '</div>';

            $teacherName = $teacher->teacher_name;
            $institutionName = \Illuminate\Support\Str::limit($teacher->institution_name ?: $teacher->inst_name ?: $teacher->inst_name2, 20, '...');

            return [
                (int) $teacher->id,
                e($teacherName ?: '-'),
                e($teacher->phone ?: '-'),
                e($institutionName ?: '-'),
                e($teacher->designation ?: '-'),
                e($teacher->department ?: '-'),
                e($teacher->subject ?: '-'),
                e($teacher->class_name ?: '-'),
                e($teacher->display_upazila ?: '-'),
                e($teacher->display_district ?: '-'),
                e($teacher->display_division ?: '-'),
                e($teacher->display_address ?: '-'),
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

        //return response()->json(['ok' => true, 'id' => $teacher->id, 'msg' => 'Teacher created.']);
        return response()->json([
            'ok' => true,
            'id' => $teacher->id,
            'teacher_name' => $teacher->teacher_name,
            'msg' => 'Teacher created successfully.'
        ]);
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
            ->with(['upazila', 'district', 'division'])
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($b) use ($term) {
                    $b->where('institution_name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            })
            ->orderBy('institution_name')
            ->limit(30)
            ->get();

        return response()->json(['results' => $institutions->map(function ($inst) {
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
        })->values()]);
    }

    public function select2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $teachers = Teacher::query()
            ->with(['institution.district', 'institution.upazila', 'district', 'upazila'])
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($b) use ($term) {
                    $b->where('phone', 'like', "%{$term}%")
                        ->orWhere('teacher_name', 'like', "%{$term}%");
                });
            })
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get();

        return response()->json([
            'results' => $teachers->map(function ($teacher) {
                $inst = $teacher->institution;
                $districtName = $teacher->district?->district_name ?? ($inst?->district?->district_name ?? '');
                $upazilaName = $teacher->upazila?->upazila_name ?? ($inst?->upazila?->upazila_name ?? '');
                $locParts = array_filter([$upazilaName, $districtName]);
                $locationStr = !empty($locParts) ? implode(', ', $locParts) : '';

                $phone = $teacher->phone ?: '';
                $text = $phone ? "{$phone} — {$teacher->teacher_name}" : $teacher->teacher_name;
                if ($inst?->institution_name) {
                    $text .= " ({$inst->institution_name})";
                }

                return [
                    'id'               => $teacher->id,
                    'text'             => $text,
                    'teacher_name'     => $teacher->teacher_name,
                    'phone'            => $teacher->phone ?? '',
                    'institution_id'   => $teacher->institution_id,
                    'institution_name' => $inst?->institution_name ?? '',
                    'district'         => $districtName,
                    'upazila'          => $upazilaName,
                    'location'         => $locationStr,
                    'address'          => $teacher->address ?: ($inst?->address ?? ''),
                ];
            })->values(),
        ]);
    }

    private function validated(Request $request, ?Teacher $teacher = null): array
    {
        $data = $request->validate([
            'teacher_name' => ['required', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:50'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable'],
            'class_name' => ['nullable'],
            'upazila_id' => ['nullable', 'integer'],
            'district_id' => ['nullable', 'integer'],
            'division_id' => ['nullable', 'integer'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        // Clean phone number (format BD with leading 0)
        if (!empty($data['phone']) && function_exists('clean_phone_number')) {
            $data['phone'] = clean_phone_number($data['phone']);
        }

        // Sync location from institution if institution is provided
        if (!empty($data['institution_id'])) {
            $inst = Institution::find($data['institution_id']);
            if ($inst) {
                $data['upazila_id']  = $data['upazila_id'] ?? $inst->upazila_id;
                $data['district_id'] = $data['district_id'] ?? $inst->district_id;
                $data['division_id'] = $data['division_id'] ?? $inst->division_id;
                if (empty($data['address']) && !empty($inst->address)) {
                    $data['address'] = $inst->address;
                }
            }
        }

        // Resolve department: if select2 sent department ID (numeric), convert to department_name
        if (!empty($data['department']) && ctype_digit((string) $data['department'])) {
            $dept = Department::find((int) $data['department']);
            if ($dept) {
                $data['department'] = $dept->department_name;
            }
        }

        // Resolve multiple subjects: support both array from multiple select2 and string
        $rawSubject = $request->input('subject');
        if (is_array($rawSubject)) {
            $subjectNames = [];
            foreach ($rawSubject as $s) {
                $s = trim((string) $s);
                if ($s === '') continue;
                if (ctype_digit($s)) {
                    $subj = Subject::find((int) $s);
                    $subjectNames[] = $subj ? $subj->subject_name : $s;
                } else {
                    $subjectNames[] = $s;
                }
            }
            $data['subject'] = !empty($subjectNames) ? mb_substr(implode(', ', array_unique($subjectNames)), 0, 255) : null;
        } elseif (is_string($rawSubject)) {
            $trimmed = trim($rawSubject);
            if ($trimmed !== '' && ctype_digit($trimmed)) {
                $subj = Subject::find((int) $trimmed);
                $data['subject'] = $subj ? $subj->subject_name : $trimmed;
            } else {
                $data['subject'] = $trimmed !== '' ? mb_substr($trimmed, 0, 255) : null;
            }
        } else {
            $data['subject'] = null;
        }

        // Resolve multiple classes: support both array from multiple select2 and string
        $rawClass = $request->input('class_name');
        if (is_array($rawClass)) {
            $classNames = [];
            foreach ($rawClass as $c) {
                $c = trim((string) $c);
                if ($c === '') continue;
                if (ctype_digit($c)) {
                    $cls = CourseClass::find((int) $c);
                    $classNames[] = $cls ? $cls->name : $c;
                } else {
                    $classNames[] = $c;
                }
            }
            $data['class_name'] = !empty($classNames) ? mb_substr(implode(', ', array_unique($classNames)), 0, 100) : null;
        } elseif (is_string($rawClass)) {
            $trimmed = trim($rawClass);
            if ($trimmed !== '' && ctype_digit($trimmed)) {
                $cls = CourseClass::find((int) $trimmed);
                $data['class_name'] = $cls ? $cls->name : $trimmed;
            } else {
                $data['class_name'] = $trimmed !== '' ? mb_substr($trimmed, 0, 100) : null;
            }
        } else {
            $data['class_name'] = null;
        }

        return $data;
    }
}
