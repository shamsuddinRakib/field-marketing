<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\District;
use App\Models\backend\Division;
use App\Models\backend\Institution;
use App\Models\backend\Upazila;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class InstitutionController extends Controller
{
    public function index()
    {
        return view('backend.modules.institutions.index');
    }

    public function createModal()
    {
        return view('backend.modules.institutions.create_modal');
    }

    public function editModal(Institution $institution)
    {
        $institution->loadMissing(['upazila', 'district', 'division']);
        $divisions = Division::orderBy('name')->get(['id', 'name']);
        $districts = District::orderBy('district_name')->get(['district_id', 'district_name', 'district_division_id']);

        return view('backend.modules.institutions.edit_modal', compact('institution', 'divisions', 'districts'));
    }

    public function listAjax(Request $request)
    {
        // If table does not exist yet, return empty DataTable response to avoid 500
        if (!Schema::hasTable('institutions')) {
            return response()->json([
                'draw' => (int) $request->input('draw'),
                'iTotalRecords' => 0,
                'iTotalDisplayRecords' => 0,
                'aaData' => [],
            ]);
        }

        $columns = ['id', 'name', 'code', 'email', 'phone', 'upazila_name', 'district_name', 'division_name', 'address', 'status'];
        $draw = (int) $request->input('draw');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim($request->input('search.value', ''));

        $query = Institution::query()
            ->leftJoin('upazilas as u', 'u.id', '=', 'institutions.upazila_id')
            ->leftJoin('districts as d', 'd.district_id', '=', 'institutions.district_id')
            ->leftJoin('divisions as div', 'div.id', '=', 'institutions.division_id')
            ->select('institutions.*', 'u.upazila_name', 'd.district_name', 'div.name as division_name');

        $total = (clone $query)->count('institutions.id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('institutions.name', 'like', "%{$search}%")
                    ->orWhere('institutions.institution_name', 'like', "%{$search}%")
                    ->orWhere('institutions.code', 'like', "%{$search}%")
                    ->orWhere('institutions.email', 'like', "%{$search}%")
                    ->orWhere('institutions.phone', 'like', "%{$search}%")
                    ->orWhere('institutions.address', 'like', "%{$search}%")
                    ->orWhere('u.upazila_name', 'like', "%{$search}%")
                    ->orWhere('d.district_name', 'like', "%{$search}%")
                    ->orWhere('div.name', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count('institutions.id');
        $orderColumn = $columns[$orderIndex] ?? 'id';
        // Map ordering to real column
        $orderMap = [
            'upazila_name' => 'u.upazila_name',
            'district_name' => 'd.district_name',
            'division_name' => 'div.name',
        ];
        $orderBy = $orderMap[$orderColumn] ?? 'institutions.' . $orderColumn;
        // fallback for name alias
        if ($orderColumn === 'name') $orderBy = 'institutions.name';
        $query->orderBy($orderBy, $orderDirection);

        $data = $query->skip($start)->take($length)->get()->map(function ($institution) {
            $status = $institution->status
                ? '<span class="bg-success-focus text-success-600 border border-success-main px-12 py-4 radius-4 fw-medium text-sm">Active</span>'
                : '<span class="bg-danger-focus text-danger-600 border border-danger-main px-12 py-4 radius-4 fw-medium text-sm">Inactive</span>';

            $editUrl = \Illuminate\Support\Facades\Route::has('institution.institutions.editModal')
                ? route('institution.institutions.editModal', $institution->id)
                : '#';
            $deleteUrl = \Illuminate\Support\Facades\Route::has('institution.institutions.destroy')
                ? route('institution.institutions.destroy', $institution->id)
                : '#';

            $actions = '<div class="d-inline-flex align-items-center justify-content-end gap-1 w-100">'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" data-ajax-modal="' . $editUrl . '" data-size="lg" data-onload="InstitutionsIndex.onLoad" data-onsuccess="InstitutionsIndex.onSaved" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-institution-delete" data-url="' . $deleteUrl . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>'
                . '</div>';

            $institutionName = $institution->institution_name ?: $institution->name;

            return [
                (int) $institution->id,
                e($institutionName ?: '-'),
                e($institution->code ?: '-'),
                e($institution->email ?: '-'),
                e($institution->phone ?: '-'),
                e($institution->upazila_name ?: '-'),
                e($institution->district_name ?: '-'),
                e($institution->division_name ?: '-'),
                e($institution->address ? \Illuminate\Support\Str::limit($institution->address, 50) : '-'),
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
        if (!Schema::hasTable('institutions')) {
            return response()->json(['ok' => false, 'msg' => 'Institutions table not migrated yet.'], 422);
        }
        $institution = Institution::create($this->validated($request));

        return response()->json(['ok' => true, 'id' => $institution->id, 'msg' => 'Institution created.']);
    }

    public function update(Request $request, Institution $institution)
    {
        $institution->update($this->validated($request, $institution));

        return response()->json(['ok' => true, 'msg' => 'Institution updated.']);
    }

    public function destroy(Institution $institution)
    {
        $institution->delete();

        return response()->json(['ok' => true, 'msg' => 'Institution deleted.']);
    }

    public function upazilasSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $districtId = $request->input('district_id');
        $divisionId = $request->input('division_id');
        $upazilas = Upazila::with('upazila_district.district_division')
            ->when($term !== '', fn ($query) => $query->where('upazila_name', 'like', "%{$term}%"))
            ->when($districtId, fn ($query) => $query->where('upazila_district_id', $districtId))
            ->when($divisionId && !$districtId, function ($query) use ($divisionId) {
                $query->whereHas('upazila_district', fn ($q) => $q->where('district_division_id', $divisionId));
            })
            ->orderBy('upazila_name')
            ->limit(30)
            ->get();

        return response()->json(['results' => $upazilas->map(function ($upazila) {
            $district = $upazila->upazila_district;
            $division = $district?->district_division;

            return [
                'id' => $upazila->id,
                'text' => $upazila->upazila_name,
                'district_id' => $district?->district_id,
                'district_name' => $district?->district_name,
                'division_id' => $division?->id,
                'division_name' => $division?->name,
            ];
        })->values()]);
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

    private function validated(Request $request, ?Institution $institution = null): array
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:191'],
            'institution_name' => ['required_without:name', 'nullable', 'string', 'max:191'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('institutions', 'code')->ignore($institution?->id)],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:50'],
            'upazila_id' => ['nullable', 'integer', 'exists:upazilas,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,district_id'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'boolean'],
        ]);

        // Normalize name field
        if (empty($data['name']) && !empty($data['institution_name'])) {
            $data['name'] = $data['institution_name'];
        }

        return $data;
    }
}
