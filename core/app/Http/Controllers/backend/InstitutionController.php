<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
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

        return view('backend.modules.institutions.edit_modal', compact('institution'));
    }

    public function importModal()
    {
        return view('backend.modules.institutions.import_modal');
    }

    public function importChunk(Request $request)
    {
        if (!Schema::hasTable('institutions')) {
            return response()->json(['ok' => false, 'msg' => 'Institutions table not found.'], 422);
        }

        $rows = $request->input('rows', []);
        if (!is_array($rows) || empty($rows)) {
            return response()->json(['ok' => true, 'inserted' => 0, 'skipped' => 0, 'errors' => []]);
        }

        $inserted = 0;
        $skipped = 0;
        $insertedItems = [];
        $skippedItems = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNum = $row['row_num'] ?? ($index + 1);
            $name = trim($row['institution_name'] ?? $row['name'] ?? '');
            if ($name === '') {
                continue; // ignore empty rows / headers
            }

            // EIIN / Code
            $code = trim($row['code'] ?? $row['eiin'] ?? '');
            if (mb_strlen($code) > 50) {
                $code = mb_substr($code, 0, 50);
            }

            $email = trim($row['email'] ?? '');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = '';
            }
            $districtName = trim($row['district'] ?? '');
            $upazilaName = trim($row['upazila'] ?? $row['upazilla'] ?? '');
            $divisionName = trim($row['division'] ?? '');
            $address = trim($row['address'] ?? '');
            $statusRaw = trim($row['status'] ?? 'Active');
            $status = in_array(strtolower($statusRaw), ['1', 'active', 'true', 'সক্রিয়']) ? 1 : 0;

            // Resolve District
            $district = function_exists('resolve_district_by_name') ? resolve_district_by_name($districtName) : null;
            $districtId = $district ? $district->district_id : null;
            $divisionId = $district ? $district->district_division_id : null;

            // Resolve Upazila
            $upazila = function_exists('resolve_upazila_by_name') ? resolve_upazila_by_name($upazilaName, $districtId) : null;
            $upazilaId = $upazila ? $upazila->id : null;

            // If district wasn't found directly, but upazila was found: inherit district and division from upazila
            if (!$districtId && $upazila && $upazila->upazila_district) {
                $districtId = $upazila->upazila_district->district_id;
                $divisionId = $upazila->upazila_district->district_division_id;
            }

            // If division was passed and divisionId not resolved yet:
            if (!$divisionId && $divisionName !== '') {
                $div = \App\Models\backend\Division::where('name', 'like', "%{$divisionName}%")
                    ->orWhere('bn_name', 'like', "%{$divisionName}%")
                    ->first();
                if ($div) {
                    $divisionId = $div->id;
                }
            }

            if ($districtId && $districtId != 1) {
                $bogusRecord = Institution::where('institution_name', $name)
                    ->where('district_id', 1)
                    ->where('upazila_id', 1)
                    ->first();
                if ($bogusRecord) {
                    $bogusRecord->update([
                        'district_id' => $districtId,
                        'upazila_id'  => $upazilaId,
                        'division_id' => $divisionId,
                        'address'     => $address !== '' ? $address : $bogusRecord->address,
                    ]);
                    $inserted++;
                    $locText = $district ? ($district->district_bn_name ?: $district->district_name) : $districtName;
                    $insertedItems[] = [
                        'row'      => $rowNum,
                        'name'     => $name,
                        'district' => $locText . ' (Updated from Barguna to ' . $locText . ')',
                    ];
                    continue;
                }
            }

            // Duplicate Check: Skip if already exists
            $existsQuery = Institution::query();
            $locText = $district ? ($district->district_bn_name ?: $district->district_name) : $districtName;
            $matchReason = '';
            if ($code !== '') {
                $existsQuery->where('code', $code);
                $matchReason = "EIIN / Code '{$code}' already exists in database";
            } else {
                $existsQuery->where('institution_name', $name);
                if ($districtId) {
                    $existsQuery->where('district_id', $districtId);
                }
                if ($upazilaId) {
                    $existsQuery->where('upazila_id', $upazilaId);
                }
                $matchReason = "Already exists in database" . ($locText ? " ({$locText})" : '');
            }

            if ($existsQuery->exists()) {
                $skipped++;
                $skippedItems[] = [
                    'row'      => $rowNum,
                    'name'     => $name,
                    'district' => $locText ?: '-',
                    'reason'   => $matchReason,
                ];
                continue; 
            }

            try {
                Institution::create([
                    'institution_name' => $name,
                    'code'             => $code !== '' ? $code : null,
                    'email'            => $email !== '' ? $email : null,
                    'district_id'      => $districtId,
                    'upazila_id'       => $upazilaId,
                    'division_id'      => $divisionId,
                    'address'          => $address !== '' ? $address : null,
                    'status'           => $status,
                ]);
                $inserted++;
                $insertedItems[] = [
                    'row'      => $rowNum,
                    'name'     => $name,
                    'district' => $locText ?: '-',
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'row'     => $rowNum,
                    'name'    => $name,
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
        // If table does not exist yet, return empty DataTable response to avoid 500
        if (!Schema::hasTable('institutions')) {
            return response()->json([
                'draw' => (int) $request->input('draw'),
                'iTotalRecords' => 0,
                'iTotalDisplayRecords' => 0,
                'aaData' => [],
            ]);
        }

        $columns = ['id', 'institution_name', 'code', 'email', 'upazila_name', 'district_name', 'division_name', 'address', 'status'];
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
                $builder->where('institutions.institution_name', 'like', "%{$search}%")
                    ->orWhere('institutions.code', 'like', "%{$search}%")
                    ->orWhere('institutions.email', 'like', "%{$search}%")
                    // ->orWhere('institutions.phone', 'like', "%{$search}%") // Removed: belongs to Teacher module
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

            $institutionName = $institution->institution_name;

            return [
                (int) $institution->id,
                e($institutionName ?: '-'),
                e($institution->code ?: '-'),
                e($institution->email ?: '-'),
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

        return response()->json([
            'ok' => true,
            'id' => $institution->id,
            'institution_name' => $institution->institution_name,
            'code' => $institution->code,
            'msg' => 'Institution created.'
        ]);
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
                      ->orWhere('code', 'like', "%{$term}%");
                });
            })
            ->orderBy('institution_name')
            ->limit(30)
            ->get();

        return response()->json(['results' => $institutions->map(function ($inst) {
            $name = $inst->institution_name;
            return [
                'id' => $inst->id,
                'text' => $name . ($inst->code ? " ({$inst->code})" : ''),
            ];
        })->values()]);
    }

    private function validated(Request $request, ?Institution $institution = null): array
    {
        $data = $request->validate([
            'institution_name' => ['required', 'string', 'max:191'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('institutions', 'code')->ignore($institution?->id)],
            'email' => ['nullable', 'email', 'max:191'],
            // 'phone' => ['nullable', 'string', 'max:50'], // Removed: Phone belongs to Teacher module
            'upazila_id' => ['nullable', 'integer', 'exists:upazilas,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,district_id'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'boolean'],
        ]);

        return $data;
    }
}
