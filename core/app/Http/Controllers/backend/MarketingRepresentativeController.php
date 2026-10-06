<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\MarketingRepresentative;
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
        $representative->load(['user', 'branch', 'upazila', 'district', 'division']);

        return view('backend.modules.marketing_representatives.edit_modal', compact('representative'));
    }

    public function listAjax(Request $request)
    {
        // index must match the DataTable column order in index.blade.php
        $columns = ['id', 'name', 'mobile', 'email', 'organization', 'branch_name', 'upazila_name', 'status', 'actions'];
        $draw = (int) $request->input('draw');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim($request->input('search.value', ''));

        $query = MarketingRepresentative::query()
            ->leftJoin('users as us', 'us.id', '=', 'marketing_representatives.user_id')
            ->leftJoin('branches as b', 'b.id', '=', 'marketing_representatives.branch_id')
            ->leftJoin('upazilas as u', 'u.id', '=', 'marketing_representatives.upazila_id')
            ->select(
                'marketing_representatives.*',
                'us.name as user_name',
                'us.phone as user_phone',
                'us.email as user_email',
                'b.name as branch_name',
                'u.upazila_name'
            );

        $total = (clone $query)->count('marketing_representatives.id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('us.name', 'like', "%{$search}%")
                    ->orWhere('marketing_representatives.employee_id', 'like', "%{$search}%")
                    ->orWhere('us.phone', 'like', "%{$search}%")
                    ->orWhere('us.email', 'like', "%{$search}%")
                    ->orWhere('marketing_representatives.organization', 'like', "%{$search}%")
                    ->orWhere('u.upazila_name', 'like', "%{$search}%")
                    ->orWhere('b.name', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count('marketing_representatives.id');
        $orderColumn = $columns[$orderIndex] ?? 'id';
        // name / mobile / email live in the `users` table, not in marketing_representatives
        $orderBy = match ($orderColumn) {
            'name' => 'us.name',
            'mobile' => 'us.phone',
            'email' => 'us.email',
            'branch_name' => 'b.name',
            'upazila_name' => 'u.upazila_name',
            'actions' => 'marketing_representatives.id',
            default => 'marketing_representatives.' . $orderColumn,
        };
        $query->orderBy($orderBy, $orderDirection);

        $data = $query->skip($start)->take($length)->get()->map(function ($representative) {
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
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" data-ajax-modal="' . $editUrl . '" data-size="lg" data-onload="MarketingRepresentativesIndex.onLoad" data-onsuccess="MarketingRepresentativesIndex.onSaved" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-representative-delete" data-url="' . $deleteUrl . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>'
                . '</div>';

            return [
                (int) $representative->id,
                e($representative->user_name),
                e($representative->user_phone ?: '-'),
                e($representative->user_email ?: '-'),
                e($representative->organization ?: '-'),
                e($representative->branch_name ?: '-'),
                e($representative->upazila_name ?: '-'),
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

    // public function store(Request $request)
    // {
    //     $representative = MarketingRepresentative::create($this->validated($request));

    //     return response()->json(['ok' => true, 'id' => $representative->id, 'msg' => 'Marketing representative created.']);
    // }

    // public function update(Request $request, MarketingRepresentative $representative)
    // {
    //     $representative->update($this->validated($request, $representative));

    //     return response()->json(['ok' => true, 'msg' => 'Marketing representative updated.']);
    // }

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

                $data['user_id'] = $user->id;

                return MarketingRepresentative::create($data);
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

                $representative->update($data);
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
        $representative->load(['user', 'branch', 'upazila', 'district', 'division']);

        return view('backend.modules.marketing_representatives.show', compact('representative'));
    }

    // public function destroy(MarketingRepresentative $representative)
    // {
    //     $representative->delete();

    //     return response()->json(['ok' => true, 'msg' => 'Marketing representative deleted.']);
    // }
    
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

    public function upazilasSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $upazilas = Upazila::with('upazila_district.district_division')
            ->when($term !== '', fn($query) => $query->where('upazila_name', 'like', "%{$term}%"))
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
            'organization' => ['nullable', 'string', 'max:191'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
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
                'id' => $representative->user->id,
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
