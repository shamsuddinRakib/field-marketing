<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\MrDesignation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MrDesignationController extends Controller
{
    public function index()
    {
        return view('backend.modules.mr_designation.index');
    }

    public function listAjax(Request $request)
    {
        $columns   = ['id', 'designation_name', 'created_at'];
        $draw      = (int) $request->input('draw', 0);
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderIdx  = (int) $request->input('order.0.column', 0);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim((string) $request->input('search.value', ''));

        $base = MrDesignation::query()->select(['id', 'designation_name', 'created_at']);
        $total = (clone $base)->count();

        if ($searchVal !== '') {
            $base->where(function ($q) use ($searchVal) {
                $q->where('designation_name', 'like', "%{$searchVal}%");
            });
        }

        $filtered = (clone $base)->count();

        // Serial always ID ASC like Department
        $rows = $base->orderBy('id', 'asc')->get();
        $fullSorted = $rows->values();
        $paginated = $fullSorted->slice($start, $length);

        $data = [];
        $serial = $start + 1;

        foreach ($paginated as $designation) {
            $nameCol = '<strong>' . e($designation->designation_name) . '</strong>';

            $actions = '<div class="d-inline-flex justify-content-end gap-1 w-100">
            <a href="#"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
               bg-success-focus text-success-main AjaxModal"
               data-ajax-modal="' . route('mr-designation.editModal', $designation->id) . '"
               data-size="lg"
               data-onsuccess="mrDesignationIndex.onSaved"
               title="Edit">
               <iconify-icon icon="lucide:edit"></iconify-icon>
            </a>

            <a href="#"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
               bg-danger-focus text-danger-main btn-mr-designation-delete"
               data-id="' . $designation->id . '"
               data-url="' . route('mr-designation.destroy', $designation->id) . '"
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
        return view('backend.modules.mr_designation.create_modal');
    }

    public function store(Request $request)
    {
        $dummy = new MrDesignation();
        $tableName = $dummy->getTable();

        $validated = $request->validate([
            'designation_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($tableName, 'designation_name'),
            ],
        ], [
            'designation_name.unique' => 'This designation name already exists. Please use a different name.',
        ]);

        $normalizedName = ucwords(strtolower(trim($validated['designation_name'])));

        $exists = MrDesignation::whereRaw('LOWER(TRIM(designation_name)) = ?', [strtolower($normalizedName)])->exists();
        if ($exists) {
            return response()->json([
                'msg'    => 'Validation failed.',
                'errors' => ['designation_name' => ['This designation name already exists. Please use a different name.']],
            ], 422);
        }

        $data = [
            'designation_name' => $normalizedName,
        ];

        $mrDesignation = MrDesignation::create($data);

        return response()->json([
            'status' => 'success',
            'msg'    => 'Designation created successfully.',
            'data'   => $mrDesignation,
        ], 201);
    }

    public function editModal(Request $request, MrDesignation $mrDesignation)
    {
        return view('backend.modules.mr_designation.edit_modal', compact('mrDesignation'));
    }

    public function update(Request $request, MrDesignation $mrDesignation)
    {
        $tableName = $mrDesignation->getTable();

        $validated = $request->validate([
            'designation_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($tableName, 'designation_name')->ignore($mrDesignation->id),
            ],
        ], [
            'designation_name.unique' => 'This designation name already exists. Please use a different name.',
        ]);

        $normalizedName = ucwords(strtolower(trim($validated['designation_name'])));

        $exists = MrDesignation::whereRaw('LOWER(TRIM(designation_name)) = ?', [strtolower($normalizedName)])
            ->where('id', '!=', $mrDesignation->id)
            ->exists();
        if ($exists) {
            return response()->json([
                'msg'    => 'Validation failed.',
                'errors' => ['designation_name' => ['This designation name already exists. Please use a different name.']],
            ], 422);
        }

        $data = [
            'designation_name' => $normalizedName,
        ];

        $mrDesignation->update($data);

        return response()->json([
            'status' => 'success',
            'msg'    => 'Designation updated successfully.',
            'data'   => $mrDesignation,
        ], 200);
    }

    public function destroy(Request $request, MrDesignation $mrDesignation)
    {
        $mrDesignation->delete();

        if ($request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'msg'     => 'Designation deleted successfully.',
                'data_id' => $mrDesignation->id,
            ]);
        }

        return redirect()->route('mr-designation.index')->with('success', 'Designation deleted.');
    }

    public function select2(Request $r)
    {
        $q = trim($r->input('q', ''));
        $base = MrDesignation::query();

        if ($q !== '') {
            $base->where(function ($x) use ($q) {
                $x->where('designation_name', 'like', "%{$q}%");
            });
        }

        $items = $base->orderBy('id')->orderBy('designation_name')
            ->limit(20)->get(['id', 'designation_name']);

        return response()->json([
            'results' => $items->map(fn($t) => [
                'id'   => $t->id,
                'text' => $t->designation_name,
            ]),
        ]);
    }
}

