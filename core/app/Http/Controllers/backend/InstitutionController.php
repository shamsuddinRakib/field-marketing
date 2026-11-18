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

            // 1. Resolve District
            $district = $districtName !== '' && function_exists('resolve_district_by_name')
                ? resolve_district_by_name($districtName)
                : null;
            $districtId = $district ? $district->district_id : null;
            $divisionId = $district ? $district->district_division_id : null;

            // Strict Validation: If user specified a District in Excel, but it was not recognized in DB
            if ($districtName !== '' && !$districtId) {
                $skipped++;
                $skippedItems[] = [
                    'row'      => $rowNum,
                    'code'     => $code ?: '-',
                    'name'     => $name,
                    'district' => $districtName,
                    'upazila'  => $upazilaName ?: '-',
                    'division' => $divisionName ?: '-',
                    'address'  => $address ?: '-',
                    'reason'   => "District '{$districtName}' not recognized in database. Please check spelling.",
                ];
                continue;
            }

            // If district was not in Excel row, check if pre-existing record with code already has district_id
            if (!$districtId && $code !== '') {
                $preExisting = Institution::where('code', $code)->first();
                if ($preExisting && $preExisting->district_id) {
                    $districtId = $preExisting->district_id;
                    $divisionId = $preExisting->division_id;
                }
            }

            // 2. Resolve Upazila (strictly scoped by districtId if district is known)
            $upazila = $upazilaName !== '' && function_exists('resolve_upazila_by_name')
                ? resolve_upazila_by_name($upazilaName, $districtId)
                : null;
            $upazilaId = $upazila ? $upazila->id : null;

            // Strict Validation: If user specified an Upazila in Excel
            if ($upazilaName !== '') {
                $locText = $district ? ($district->district_bn_name ?: $district->district_name) : $districtName;

                if ($districtId && !$upazilaId) {
                    $skipped++;
                    $skippedItems[] = [
                        'row'      => $rowNum,
                        'code'     => $code ?: '-',
                        'name'     => $name,
                        'district' => $locText ?: '-',
                        'upazila'  => $upazilaName,
                        'division' => $divisionName ?: '-',
                        'address'  => $address ?: '-',
                        'reason'   => "Upazila '{$upazilaName}' not found under District '{$locText}'.",
                    ];
                    continue;
                }

                if (!$districtId && !$upazilaId) {
                    $skipped++;
                    $skippedItems[] = [
                        'row'      => $rowNum,
                        'code'     => $code ?: '-',
                        'name'     => $name,
                        'district' => $districtName ?: '-',
                        'upazila'  => $upazilaName,
                        'division' => $divisionName ?: '-',
                        'address'  => $address ?: '-',
                        'reason'   => "Upazila '{$upazilaName}' exists in multiple districts. District name is required in Excel.",
                    ];
                    continue;
                }
            }

            // If district wasn't in Excel, but upazila was unique nationally: inherit district and division
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

            $locText = $district ? ($district->district_bn_name ?: $district->district_name) : $districtName;

            // 3. Identify if institution already exists in DB
            $existing = null;
            if ($code !== '') {
                $existing = Institution::where('code', $code)->first();
            }

            if (!$existing && $name !== '') {
                // 3a. Exact match: same name + same district + same upazila
                if ($districtId && $upazilaId) {
                    $existing = Institution::where('institution_name', $name)
                        ->where('district_id', $districtId)
                        ->where('upazila_id', $upazilaId)
                        ->first();
                }

                // 3b. Match same name + same district where upazila_id is NULL in DB
                if (!$existing && $districtId) {
                    $existing = Institution::where('institution_name', $name)
                        ->where('district_id', $districtId)
                        ->whereNull('upazila_id')
                        ->first();
                }

                // 3c. Match same name where both district_id and upazila_id are NULL in DB
                if (!$existing) {
                    $existing = Institution::where('institution_name', $name)
                        ->whereNull('district_id')
                        ->whereNull('upazila_id')
                        ->first();
                }

                // 3d. Legacy Barguna record patch (district_id=1, upazila_id=1) when incoming district is not Barguna
                if (!$existing && $districtId && $districtId != 1) {
                    $existing = Institution::where('institution_name', $name)
                        ->where('district_id', 1)
                        ->where('upazila_id', 1)
                        ->first();
                }
            }

            // 4. If existing institution is found: check if any NULL / missing fields can be backfilled
            if ($existing) {
                $needsUpdate = false;
                $updateData = [];
                $updateNotes = [];

                // Backfill Upazila if NULL in DB and resolved from Excel
                if (empty($existing->upazila_id) && !empty($upazilaId)) {
                    $targetDistrictId = $existing->district_id ?: $districtId;
                    $upazilaMatchesDistrict = true;
                    if ($targetDistrictId && $upazila && isset($upazila->upazila_district_id)) {
                        $upazilaMatchesDistrict = ($upazila->upazila_district_id == $targetDistrictId);
                    }
                    if ($upazilaMatchesDistrict) {
                        $updateData['upazila_id'] = $upazilaId;
                        $needsUpdate = true;
                        $updateNotes[] = 'Upazila added';
                    }
                }

                // Backfill District if NULL in DB and resolved from Excel
                if (empty($existing->district_id) && !empty($districtId)) {
                    $updateData['district_id'] = $districtId;
                    $needsUpdate = true;
                    $updateNotes[] = 'District added';
                } elseif ($existing->district_id == 1 && $districtId && $districtId != 1) {
                    // Correct legacy Barguna placeholder
                    $updateData['district_id'] = $districtId;
                    $updateData['upazila_id']  = $upazilaId;
                    $needsUpdate = true;
                    $updateNotes[] = 'District corrected from Barguna to ' . $locText;
                }

                // Backfill Division if NULL in DB and resolved from Excel
                if (empty($existing->division_id) && !empty($divisionId)) {
                    $updateData['division_id'] = $divisionId;
                    $needsUpdate = true;
                }

                // Backfill EIIN/Code if NULL/empty in DB and present in Excel
                if (empty($existing->code) && !empty($code)) {
                    $updateData['code'] = $code;
                    $needsUpdate = true;
                    $updateNotes[] = 'EIIN added';
                }

                // Backfill Address if NULL/empty in DB and present in Excel
                if (empty($existing->address) && !empty($address)) {
                    $updateData['address'] = $address;
                    $needsUpdate = true;
                }

                // Backfill Email if NULL/empty in DB and present in Excel
                if (empty($existing->email) && !empty($email)) {
                    $updateData['email'] = $email;
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    try {
                        $existing->update($updateData);
                        $inserted++;
                        $noteStr = !empty($updateNotes) ? ' (' . implode(', ', $updateNotes) . ')' : ' (Updated)';
                        $insertedItems[] = [
                            'row'      => $rowNum,
                            'code'     => $existing->code ?: ($code ?: '-'),
                            'name'     => $name,
                            'district' => ($locText ?: '-') . $noteStr,
                            'upazila'  => $upazila ? $upazila->upazila_name : ($upazilaName ?: '-'),
                            'division' => $divisionName ?: '-',
                            'address'  => $address ?: '-',
                            'action'   => 'updated',
                        ];
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'row'      => $rowNum,
                            'code'     => $code ?: '-',
                            'name'     => $name,
                            'district' => $locText ?: '-',
                            'upazila'  => $upazilaName ?: '-',
                            'division' => $divisionName ?: '-',
                            'address'  => $address ?: '-',
                            'message'  => 'Update failed: ' . $e->getMessage(),
                        ];
                    }
                    continue;
                }

                // All fields are already complete and up-to-date -> skip as duplicate
                $skipped++;
                $matchReason = $code !== ''
                    ? "EIIN / Code '{$code}' already exists in database (Up-to-date)"
                    : "Already exists in database" . ($locText ? " ({$locText})" : '');

                $skippedItems[] = [
                    'row'      => $rowNum,
                    'code'     => $code ?: '-',
                    'name'     => $name,
                    'district' => $locText ?: '-',
                    'upazila'  => $upazila ? $upazila->upazila_name : ($upazilaName ?: '-'),
                    'division' => $divisionName ?: '-',
                    'address'  => $address ?: '-',
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
                    'code'     => $code ?: '-',
                    'name'     => $name,
                    'district' => $locText ?: '-',
                    'upazila'  => $upazila ? $upazila->upazila_name : ($upazilaName ?: '-'),
                    'division' => $divisionName ?: '-',
                    'address'  => $address ?: '-',
                    'action'   => 'inserted',
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'row'      => $rowNum,
                    'code'     => $code ?: '-',
                    'name'     => $name,
                    'district' => $locText ?: '-',
                    'upazila'  => $upazilaName ?: '-',
                    'division' => $divisionName ?: '-',
                    'address'  => $address ?: '-',
                    'message'  => $e->getMessage(),
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
        try {
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
            $start = max(0, (int) $request->input('start', 0));
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

            if ($length > 0) {
                $query->skip($start)->take($length);
            }

            $data = $query->get()->map(function ($institution) {
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
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Institutions listAjax error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) $request->input('draw'),
                'iTotalRecords' => 0,
                'iTotalDisplayRecords' => 0,
                'aaData' => [],
                'error' => $e->getMessage(),
            ]);
        }
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
        $upazilaId = $request->input('upazila_id');
        $institutions = Institution::query()
            ->when($upazilaId, fn($q) => $q->where('upazila_id', $upazilaId))
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
