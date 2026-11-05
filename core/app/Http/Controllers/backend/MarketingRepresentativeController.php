<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\MarketingRepresentativeTerritory;
use App\Models\backend\District;
use App\Models\backend\Role;
use App\Models\backend\Upazila;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\backend\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class MarketingRepresentativeController extends Controller
{
    public function index()
    {
        return view('backend.modules.marketing_representatives.index');
    }

    public function createModal()
    {
        return view('backend.modules.marketing_representatives.create_modal');
    }

    public function editModal(MarketingRepresentative $representative)
    {
        $representative->load(['user', 'branch', 'upazila', 'district', 'division', 'mrDesignation', 'territories.upazila', 'territories.district', 'territories.division']);

        return view('backend.modules.marketing_representatives.edit_modal', compact('representative'));
    }

    public function listAjax(Request $request)
    {
        // index must match the DataTable column order in index.blade.php
        $columns = ['id', 'name', 'designation', 'current_fund', 'branch_name', 'upazila_name', 'status', 'actions'];
        $draw = (int) $request->input('draw');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim($request->input('search.value', ''));

        $designationTable = (new \App\Models\backend\MrDesignation())->getTable();

        $query = MarketingRepresentative::query()
            ->leftJoin('users as us', 'us.id', '=', 'marketing_representatives.user_id')
            ->leftJoin('branches as b', 'b.id', '=', 'marketing_representatives.branch_id')
            ->leftJoin('upazilas as u', 'u.id', '=', 'marketing_representatives.upazila_id')
            ->leftJoin("{$designationTable} as des", 'des.id', '=', 'marketing_representatives.mr_designation_id')
            ->leftJoinSub(
                \App\Models\backend\CurrentFund::query()
                    ->select('user_id')
                    ->selectRaw('SUM(balance) as current_fund')
                    ->groupBy('user_id'),
                'cf',
                'cf.user_id',
                '=',
                'marketing_representatives.user_id'
            )
            ->leftJoinSub(
                \App\Models\backend\ProductDistribution::query()
                    ->select('user_id')
                    ->selectRaw('SUM(quantity) as total_product')
                    ->where('status', 'delivered')
                    ->groupBy('user_id'),
                'pd',
                'pd.user_id',
                '=',
                'marketing_representatives.user_id'
            )
            ->leftJoinSub(
                \App\Models\backend\SpotSale::query()
                    ->select('user_id')
                    ->selectRaw('COUNT(id) as total_spot_sales_count')
                    ->selectRaw('SUM(total) as total_spot_sales_amount')
                    ->where('status', 'approved')
                    ->groupBy('user_id'),
                'ss',
                'ss.user_id',
                '=',
                'marketing_representatives.user_id'
            )
            ->select(
                'marketing_representatives.*',
                'us.name as user_name',
                'us.phone as user_phone',
                'us.email as user_email',
                'des.designation_name',
                'b.name as branch_name',
                'u.upazila_name',
                \Illuminate\Support\Facades\DB::raw('COALESCE(cf.current_fund, 0) as current_fund'),
                DB::raw('COALESCE(pd.total_product, 0) as total_product'),
                DB::raw('COALESCE(ss.total_spot_sales_count, 0) as total_spot_sales_count'),
                DB::raw('COALESCE(ss.total_spot_sales_amount, 0) as total_spot_sales_amount')
            );

        $total = (clone $query)->count('marketing_representatives.id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('us.name', 'like', "%{$search}%")
                    ->orWhere('marketing_representatives.employee_id', 'like', "%{$search}%")
                    ->orWhere('us.phone', 'like', "%{$search}%")
                    ->orWhere('us.email', 'like', "%{$search}%")
                    ->orWhere('des.designation_name', 'like', "%{$search}%")
                    ->orWhere('u.upazila_name', 'like', "%{$search}%")
                    ->orWhere('b.name', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count('marketing_representatives.id');
        $orderColumn = $columns[$orderIndex] ?? 'id';
        // name / mobile / email live in the `users` table, not in marketing_representatives
        $orderBy = match ($orderColumn) {
            'name' => 'us.name',
            'designation', 'organization' => 'des.designation_name',
            'current_fund' => 'current_fund',
            'mobile' => 'us.phone',
            'email' => 'us.email',
            'branch_name' => 'b.name',
            'upazila_name' => 'u.upazila_name',
            'actions' => 'marketing_representatives.id',
            'total_product' => 'total_product',
            default => 'marketing_representatives.' . $orderColumn,
        };
        $query->orderBy($orderBy, $orderDirection);

        $rows = $query->skip($start)->take($length)->get();
        $repIds = $rows->pluck('id')->all();
        $territoriesByRep = MarketingRepresentativeTerritory::with('upazila')
            ->whereIn('marketing_representative_id', $repIds)
            ->get()
            ->groupBy('marketing_representative_id');

        $data = $rows->map(function ($representative) use ($territoriesByRep) {
            $status = $representative->status
                ? '<span class="bg-success-focus text-success-600 border border-success-main px-12 py-4 radius-4 fw-medium text-sm">Active</span>'
                : '<span class="bg-danger-focus text-danger-600 border border-danger-main px-12 py-4 radius-4 fw-medium text-sm">Inactive</span>';
            // Support both route names for backward compatibility (usermanage and marketing-representative)
            $showUrl = \Illuminate\Support\Facades\Route::has('usermanage.marketing-representatives.show')
                ? route('usermanage.marketing-representatives.show', $representative->id)
                : route('marketing-representative.marketing-representatives.show', $representative->id);
            $editUrl = \Illuminate\Support\Facades\Route::has('usermanage.marketing-representatives.editModal')
                ? route('usermanage.marketing-representatives.editModal', $representative->id)
                : route('marketing-representative.marketing-representatives.editModal', $representative->id);
            $deleteUrl = \Illuminate\Support\Facades\Route::has('usermanage.marketing-representatives.destroy')
                ? route('usermanage.marketing-representatives.destroy', $representative->id)
                : route('marketing-representative.marketing-representatives.destroy', $representative->id);

            $actions = '<div class="d-inline-flex align-items-center justify-content-end gap-1 w-100">'
                . '<a href="' . $showUrl . '" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-info-focus text-info-main" title="View"><iconify-icon icon="lucide:eye"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" data-ajax-modal="' . $editUrl . '" data-size="xl" data-onload="MarketingRepresentativesIndex.onLoad" data-onsuccess="MarketingRepresentativesIndex.onSaved" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-representative-delete" data-url="' . $deleteUrl . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>'
                . '</div>';

            $repTerritories = $territoriesByRep->get($representative->id);
            if ($repTerritories && $repTerritories->isNotEmpty()) {
                $upazilaDisplay = e($repTerritories->map(fn($t) => $t->upazila?->upazila_name)->filter()->implode(', '));
            } else {
                $upazilaDisplay = e($representative->upazila_name ?: '-');
            }

            return [
                (int) $representative->id,
                // e($representative->user_name),   
                // e($representative->user_phone ?: '-'),
                // e($representative->user_email ?: '-'),
                '<div>
                    <div class="fw-semibold">' . '<strong>Name:</strong> '  . e($representative->user_name ?: '-') . '</div>
                    <div class="text-sm text-secondary">' . '<strong>Phone:</strong> ' . e($representative->user_phone ?: '-') . '</div>
                    <div class="text-sm text-secondary">' . '<strong>Email:</strong> ' . e($representative->user_email ?: '-') . '</div>
                </div>',
                e($representative->designation_name ?: '-'),

                '<div>
                    <div class="fw-semibold">
                        Current Fund: ' . number_format((float) $representative->current_fund, 2) . '
                    </div>
                    <div class="text-sm text-secondary">
                        Total Product: ' . number_format((int) $representative->total_product) . '
                    </div>
                     <div class="text-sm text-secondary">
                        Spot Sales: ' . number_format((int) $representative->total_spot_sales_count) . '
                    </div>
                    <div class="text-sm text-secondary">
                        Spot Sale Amount: ' . number_format((float) $representative->total_spot_sales_amount, 2) . '
                    </div>
                </div>',

                e($representative->branch_name ?: '-'),
                $upazilaDisplay,
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
        $data = $this->validated($request);

        try {
            $representative = DB::transaction(function () use ($data) {

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['mobile'] ?? null,
                    'username' => $data['employee_id'],
                    'password' => Hash::make($data['password']),
                    'role_id' => $this->marketingRepresentativeRoleId(),
                    'branch_id' => $data['branch_id'] ?? null,
                    'status' => $data['status'],
                ]);

                unset(
                    $data['name'],
                    $data['email'],
                    $data['mobile'],
                    $data['password']
                );

                $districtIds = (array) ($data['district_ids'] ?? []);
                $upazilaIds = (array) ($data['upazila_ids'] ?? []);
                if (empty($upazilaIds) && !empty($data['upazila_id'])) {
                    $upazilaIds = [$data['upazila_id']];
                }
                unset($data['district_ids'], $data['upazila_ids']);

                $locInfo = function_exists('getParentLocationsMultiple')
                    ? getParentLocationsMultiple($upazilaIds)
                    : ['territories' => [], 'district_ids' => [], 'division_ids' => []];

                $allDistrictIds = array_unique(array_filter(array_merge($districtIds, $locInfo['district_ids'])));
                if (!empty($allDistrictIds)) {
                    $data['district_id'] = reset($allDistrictIds);
                    $firstDist = District::whereIn('district_id', $allDistrictIds)->first();
                    $data['division_id'] = $firstDist?->district_division_id;
                }
                if (!empty($locInfo['division_ids'])) {
                    $data['division_id'] = $locInfo['division_ids'][0];
                }
                if (!empty($upazilaIds)) {
                    $data['upazila_id'] = $upazilaIds[0];
                }

                $data['user_id'] = $user->id;

                $representative = MarketingRepresentative::create($data);

                $coveredDistricts = [];
                if (!empty($locInfo['territories'])) {
                    foreach ($locInfo['territories'] as $t) {
                        MarketingRepresentativeTerritory::create([
                            'marketing_representative_id' => $representative->id,
                            'upazila_id'                  => $t['upazila_id'],
                            'district_id'                 => $t['district_id'],
                            'division_id'                 => $t['division_id'],
                        ]);
                        if (!empty($t['district_id'])) {
                            $coveredDistricts[] = $t['district_id'];
                        }
                    }
                }
                $remainingDistricts = array_diff($allDistrictIds, array_unique($coveredDistricts));
                foreach ($remainingDistricts as $dId) {
                    $distObj = District::where('district_id', $dId)->first();
                    MarketingRepresentativeTerritory::create([
                        'marketing_representative_id' => $representative->id,
                        'upazila_id'                  => null,
                        'district_id'                 => $dId,
                        'division_id'                 => $distObj?->district_division_id,
                    ]);
                }

                return $representative;
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'msg' => 'Could not save the marketing representative. Please check the data and try again.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'id' => $representative->id,
            'msg' => 'Marketing representative created.',
        ]);
    }

    public function update(Request $request, MarketingRepresentative $representative)
    {
        $data = $this->validated($request, $representative);

        try {
            DB::transaction(function () use ($data, $representative) {

                $user = $representative->user;

                $userData = [
                    'name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['mobile'] ?? null,
                    'username' => $data['employee_id'],
                    'branch_id' => $data['branch_id'] ?? null,
                    'status' => $data['status'],
                ];

                if (!empty($data['password'])) {
                    $userData['password'] = Hash::make($data['password']);
                }

                if ($user) {
                    $user->update($userData);
                }

                unset(
                    $data['name'],
                    $data['email'],
                    $data['mobile'],
                    $data['password']
                );

                $districtIds = (array) ($data['district_ids'] ?? []);
                $upazilaIds = (array) ($data['upazila_ids'] ?? []);
                if (empty($upazilaIds) && !empty($data['upazila_id'])) {
                    $upazilaIds = [$data['upazila_id']];
                }
                unset($data['district_ids'], $data['upazila_ids']);

                $locInfo = function_exists('getParentLocationsMultiple')
                    ? getParentLocationsMultiple($upazilaIds)
                    : ['territories' => [], 'district_ids' => [], 'division_ids' => []];

                $allDistrictIds = array_unique(array_filter(array_merge($districtIds, $locInfo['district_ids'])));
                if (!empty($allDistrictIds)) {
                    $data['district_id'] = reset($allDistrictIds);
                    $firstDist = District::whereIn('district_id', $allDistrictIds)->first();
                    $data['division_id'] = $firstDist?->district_division_id;
                }
                if (!empty($locInfo['division_ids'])) {
                    $data['division_id'] = $locInfo['division_ids'][0];
                }
                if (!empty($upazilaIds)) {
                    $data['upazila_id'] = $upazilaIds[0];
                }

                $representative->update($data);

                $representative->territories()->delete();
                $coveredDistricts = [];
                if (!empty($locInfo['territories'])) {
                    foreach ($locInfo['territories'] as $t) {
                        MarketingRepresentativeTerritory::create([
                            'marketing_representative_id' => $representative->id,
                            'upazila_id'                  => $t['upazila_id'],
                            'district_id'                 => $t['district_id'],
                            'division_id'                 => $t['division_id'],
                        ]);
                        if (!empty($t['district_id'])) {
                            $coveredDistricts[] = $t['district_id'];
                        }
                    }
                }
                $remainingDistricts = array_diff($allDistrictIds, array_unique($coveredDistricts));
                foreach ($remainingDistricts as $dId) {
                    $distObj = District::where('district_id', $dId)->first();
                    MarketingRepresentativeTerritory::create([
                        'marketing_representative_id' => $representative->id,
                        'upazila_id'                  => null,
                        'district_id'                 => $dId,
                        'division_id'                 => $distObj?->district_division_id,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'msg' => 'Could not update the marketing representative. Please check the data and try again.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'msg' => 'Marketing representative updated.',
        ]);
    }

    public function show(MarketingRepresentative $representative)
    {
        $representative->load(['user', 'branch', 'upazila', 'district', 'division', 'mrDesignation', 'territories.upazila', 'territories.district', 'territories.division']);

        $userId = $representative->user_id;

        // Current Fund
        $currentFund = \App\Models\backend\CurrentFund::where('user_id', $userId)
            ->sum('balance');

        // Total Delivered Product Quantity
        $totalProduct = \App\Models\backend\ProductDistribution::where('user_id', $userId)
            ->where('status', 'delivered')
            ->sum('quantity');

        // Approved Spot Sales Count
        $totalSpotSalesCount = \App\Models\backend\SpotSale::where('user_id', $userId)
            ->where('status', 'approved')
            ->count();

        // Approved Spot Sales Amount
        $totalSpotSalesAmount = \App\Models\backend\SpotSale::where('user_id', $userId)
            ->where('status', 'approved')
            ->sum('total');


        return view('backend.modules.marketing_representatives.show', compact('representative','currentFund', 'totalProduct','totalSpotSalesCount','totalSpotSalesAmount'));
    }

    public function destroy(MarketingRepresentative $representative)
    {
        DB::transaction(function () use ($representative) {

            if ($representative->user) {
                $representative->user->update([
                    'status' => 0,
                ]);

                $representative->user->delete();
            }

            $representative->delete();
        });

        return response()->json([
            'ok' => true,
            'msg' => 'Marketing representative deleted.'
        ]);
    }

    public function districtsSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $districts = District::with('district_division')
            ->when($term !== '', function ($q) use ($term) {
                $q->where('district_name', 'like', "%{$term}%")
                    ->orWhere('district_bn_name', 'like', "%{$term}%");
            })
            ->orderBy('district_name')
            ->limit(64)
            ->get();

        return response()->json(['results' => $districts->map(function ($district) {
            $division = $district->district_division;
            return [
                'id'            => $district->district_id,
                'text'          => $district->district_name,
                'division_id'   => $division?->id,
                'division_name' => $division?->name,
            ];
        })->values()]);
    }

    public function upazilasSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $districtIds = $request->input('district_ids');
        if (is_string($districtIds)) {
            $districtIds = array_filter(explode(',', $districtIds));
        }

        // Upazila is dependent on District. If no district is selected, do not return any upazila.
        if (empty($districtIds)) {
            return response()->json(['results' => []]);
        }

        $query = Upazila::with('upazila_district.district_division')
            ->whereIn('upazila_district_id', (array) $districtIds);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('upazila_name', 'like', "%{$term}%")
                    ->orWhere('upazila_bn_name', 'like', "%{$term}%");
            });
        }

        $upazilas = $query->orderBy('upazila_name')
            ->limit(100)
            ->get();

        return response()->json(['results' => $upazilas->map(function ($upazila) {
            $district = $upazila->upazila_district;
            $division = $district?->district_division;

            return [
                'id'            => $upazila->id,
                'text'          => $upazila->upazila_name . ($district ? " ({$district->district_name})" : ''),
                'district_id'   => $district?->district_id,
                'district_name' => $district?->district_name,
                'division_id'   => $division?->id,
                'division_name' => $division?->name,
            ];
        })->values()]);
    }

    private function validated(Request $request, ?MarketingRepresentative $representative = null): array
    {
        // users table has unique index on email / phone / username -> must be validated
        // otherwise MySQL throws 1062 and the request ends with a 500 error.
        $userId = $representative?->user_id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'employee_id' => [
                'required',
                'string',
                'max:100',
                Rule::unique('marketing_representatives', 'employee_id')->ignore($representative?->id),
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'password' => [$representative ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'mobile' => ['nullable', 'string', 'max:50', Rule::unique('users', 'phone')->ignore($userId)],
            'email' => ['nullable', 'email', 'max:191', Rule::unique('users', 'email')->ignore($userId)],
            'mr_designation_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'district_ids' => ['nullable', 'array'],
            'district_ids.*' => ['integer'],
            'upazila_ids' => ['nullable', 'array'],
            'upazila_ids.*' => ['integer', 'exists:upazilas,id'],
            'upazila_id' => ['nullable', 'integer', 'exists:upazilas,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,district_id'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'boolean'],
        ]);

        if (empty($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }

    public function select2()
    {
        $term = trim(request('q', ''));
        $representatives = MarketingRepresentative::with('user')
            ->when($term !== '', fn($query) => $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$term}%")))
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get();

        return response()->json(['results' => $representatives->map(function ($representative) {
            return [
                'id' => $representative->user->id ?? '-',
                'text' => $representative->user?->name ?? 'N/A',
                'employee_id' => $representative->employee_id,
                'mobile' => $representative->user?->phone ?? null,
                'email' => $representative->user?->email ?? null,
            ];
        })->values()]);
    }

    private function marketingRepresentativeRoleId(): int
    {
        $roleId = Role::where(
            'name',
            'Marketing Representative'
        )->value('id');

        if (!$roleId) {
            throw new \RuntimeException(
                'Marketing Representative role not found in roles table.'
            );
        }

        return (int) $roleId;
    }
}
