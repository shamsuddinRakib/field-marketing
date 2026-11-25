<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\Upazila;
use Illuminate\Http\Request;

class UpazilaController extends Controller
{
    /**
     * Get Upazila+District+division list
     */
    public function index(Request $request)
    {
        $query = Upazila::query()
            ->select([
                'id',
                'upazila_name',
                'upazila_bn_name',
                'upazila_district_id',
                'upazila_url',
            ])
            // ->with('upazila_district:district_id,district_name');
            ->with([
                'upazila_district:district_id,district_division_id,district_name',
                'upazila_district.district_division:id,name,bn_name',
            ]);

        // Filter by district
        if ($request->filled('district_id')) {
            $query->where('upazila_district_id', $request->district_id);
        }

        // If authenticated as MR, filter by assigned territories unless all=1
        $user = auth('sanctum')->user() ?? auth()->user();
        if ($user && !$request->boolean('all')) {
            $mr = MarketingRepresentative::where('user_id', $user->id)->with('territories')->first();
            if ($mr) {
                $territoryIds = $mr->territories->pluck('upazila_id')->filter()->all();
                if ($mr->upazila_id) {
                    $territoryIds[] = $mr->upazila_id;
                }
                $assignedUpazilaIds = array_values(array_unique(array_filter($territoryIds)));
                if (!empty($assignedUpazilaIds)) {
                    $query->whereIn('id', $assignedUpazilaIds);
                }
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('upazila_name', 'like', "%{$search}%")
                    ->orWhere('upazila_bn_name', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $upazilas = $query
            ->orderBy('upazila_name', 'asc')
            ->paginate($perPage);

        $data = $upazilas->getCollection()->map(function ($upazila) {
            $dist = $upazila->upazila_district;
            $div = $dist?->district_division;

            return [
                'id' => $upazila->id,
                'upazila_name' => $upazila->upazila_name,
                'upazila_bn_name' => $upazila->upazila_bn_name,
                'upazila_url' => $upazila->upazila_url,
                'district_id' => $dist?->district_id,
                'district_name' => $dist?->district_name ?? '',
                'division_id' => $div?->id,
                'division_name' => $div?->name ?? ($div?->bn_name ?? ''),
                'district' => $dist ? [
                    'id' => $dist->district_id,
                    'name' => $dist->district_name,
                    'division' => $div ? [
                        'id' => $div->id,
                        'name' => $div->name,
                        'bn_name' => $div->bn_name,
                    ] : null,
                ] : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Upazilas retrieved successfully.',
            'data' => $data,
            'meta' => [
                'current_page' => $upazilas->currentPage(),
                'per_page' => $upazilas->perPage(),
                'total' => $upazilas->total(),
                'last_page' => $upazilas->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get single Upazila
     */
    public function show($id)
    {
        // $upazila = Upazila::with(
        //     'upazila_district:district_id,district_name'
        // )->find($id);

        $upazila = Upazila::with([
            'upazila_district:district_id,district_division_id,district_name',
            'upazila_district.district_division:id,name,bn_name',
        ])->find($id);

        if (!$upazila) {
            return response()->json([
                'status' => 'error',
                'message' => 'Upazila not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Upazila retrieved successfully.',
            'data' => [
                'id' => $upazila->id,
                'upazila_name' => $upazila->upazila_name,
                'upazila_bn_name' => $upazila->upazila_bn_name,
                'upazila_url' => $upazila->upazila_url,

                'district' => $upazila->upazila_district ? [
                    'id' => $upazila->upazila_district->district_id,
                    'name' => $upazila->upazila_district->district_name,

                    'division' => $upazila->upazila_district->district_division ? [
                        'id' => $upazila->upazila_district->district_division->id,
                        'name' => $upazila->upazila_district->district_division->name,
                        'bn_name' => $upazila->upazila_district->district_division->bn_name,
                    ] : null,

                ] : null,
            ],
        ], 200);
    }
}
