<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        return view('backend.modules.departments.index');
    }

    public function listAjax(Request $request)
    {
        $columns   = ['id', 'department_name', 'created_at'];
        $draw      = (int) $request->input('draw', 0);
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderIdx  = (int) $request->input('order.0.column', 0);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim((string) $request->input('search.value', ''));

        $base = Department::query()->select(['id', 'department_name', 'created_at']);
        $total = (clone $base)->count();

        if ($searchVal !== '') {
            $base->where(function ($q) use ($searchVal) {
                $q->where('department_name', 'like', "%{$searchVal}%");
            });
        }

        $filtered = (clone $base)->count();

        // ❗Serial হবে ID ascending অনুযায়ী
        $orderCol = $columns[$orderIdx] ?? 'id';

        // Main query
        $rows = $base->orderBy('id', 'asc') // <-- serial always ID ASC
            ->get();

        // Calculate Serial based on sorted IDs
        $fullSorted = $rows->values();

        // Now apply pagination after sorting
        $paginated = $fullSorted->slice($start, $length);

        $data = [];
        $serial = $start + 1;

        foreach ($paginated as $department) {

            $nameCol = '<strong>' . e($department->department_name) . '</strong>';



            $actions = '<div class="d-inline-flex justify-content-end gap-1 w-100">
            <a href="#"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
               bg-success-focus text-success-main AjaxModal"
               data-ajax-modal="' . route('department.editModal', $department->id) . '"
               data-size="lg"
               data-onsuccess="departmentIndex.onSaved"
               title="Edit">
               <iconify-icon icon="lucide:edit"></iconify-icon>
            </a>

            <a href="#"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
               bg-danger-focus text-danger-main btn-department-delete"
               data-id="' . $department->id . '"
               data-url="' . route('department.destroy', $department->id) . '"
               title="Delete">
               <iconify-icon icon="mdi:delete"></iconify-icon>
            </a>
        </div>';

            $data[] = [
                $serial++,
                $nameCol,
                $actions,
            ];
        }

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }

    public function createModal()
    {

        return view('backend.modules.departments.create_modal');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('department', 'department_name'),
            ],
        ], [
            'department_name.unique' => 'This department name already exists. Please use a different name.',
        ]);

        // Normalize: trim + title-case (case-insensitive duplicate check) — same as Subject
        $normalizedName = ucwords(strtolower(trim($validated['department_name'])));

        // Extra case-insensitive / trimmed check to catch "  sales " vs "Sales" — same as Subject
        $exists = Department::whereRaw('LOWER(TRIM(department_name)) = ?', [strtolower($normalizedName)])->exists();
        if ($exists) {
            return response()->json([
                'msg'    => 'Validation failed.',
                'errors' => ['department_name' => ['This department name already exists. Please use a different name.']],
            ], 422);
        }

        $data = [
            'department_name' => $normalizedName,
        ];

        // Create department
        $department = Department::create($data);

        return response()->json([
            'status' => 'success',
            'msg'    => 'department created successfully.',
            'data'   => $department,
        ], 201);
    }

    public function editModal(Request $request, Department $department)
    {

        return view('backend.modules.departments.edit_modal', compact('department'));
    }


    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'department_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('department', 'department_name')->ignore($department->id),
            ],
        ], [
            'department_name.unique' => 'This department name already exists. Please use a different name.',
        ]);

        $normalizedName = ucwords(strtolower(trim($validated['department_name'])));

        $exists = Department::whereRaw('LOWER(TRIM(department_name)) = ?', [strtolower($normalizedName)])
            ->where('id', '!=', $department->id)
            ->exists();
        if ($exists) {
            return response()->json([
                'msg'    => 'Validation failed.',
                'errors' => ['department_name' => ['This department name already exists. Please use a different name.']],
            ], 422);
        }

        $data = [
            'department_name' => $normalizedName,
        ];

        $department->update($data);

        return response()->json([
            'status' => 'success',
            'msg'    => 'Department updated successfully.',
            'data'   => $department,
        ], 200);
    }

    public function destroy(Request $request, Department $department)
    {


        $department->delete();

        if ($request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'msg'     => 'Department deleted successfully.',
                'data_id' => $department->id,
            ]);
        }

        return redirect()->route('department.index')->with('success', 'department deleted.');
    }
    public function select2(Request $r)
    {

        $q = trim($r->input('q', ''));
        $type = $r->type;
        $base = Department::query();


        if ($q !== '') {
            $base->where(function ($x) use ($q) {
                $x->where('department_name', 'like', "%{$q}%");
            });
        }

        $items = $base->orderBy('id')->orderBy('department_name')
            ->limit(20)->get(['id', 'department_name']);


        return response()->json([
            'results' => $items->map(fn($t) => [
                'id'   => $t->id,
                'text' => $t->department_name
            ])
        ]);
    }
}
