<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\Division;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    /**
     * Get Division list
     */
    public function index(Request $request)
    {
        $query = Division::query()
            ->select([
                'id',
                'name',
                'bn_name',
                'url',
            ]);

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('bn_name', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = min(
            max((int) $request->input('per_page', 20), 1),
            100
        );

        $divisions = $query
            ->orderBy('name', 'asc')
            ->paginate($perPage);

        $data = $divisions->getCollection()->map(function ($division) {
            return [
                'id' => $division->id,
                'name' => $division->name,
                'bn_name' => $division->bn_name,
                'url' => $division->url,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Divisions retrieved successfully.',
            'data' => $data,
            'meta' => [
                'current_page' => $divisions->currentPage(),
                'per_page' => $divisions->perPage(),
                'total' => $divisions->total(),
                'last_page' => $divisions->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get single Division with Districts
     */
    public function show($id)
    {
        $division = Division::query()
            ->with([
                'division_district' => function ($query) {
                    $query->select([
                        'district_id',
                        'district_division_id',
                        'district_name',
                        'district_bn_name',
                        'district_url',
                    ])->orderBy('district_name', 'asc');
                }
            ])
            ->find($id);

        if (!$division) {
            return response()->json([
                'status' => 'error',
                'message' => 'Division not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Division retrieved successfully.',
            'data' => [
                'id' => $division->id,
                'name' => $division->name,
                'bn_name' => $division->bn_name,
                'url' => $division->url,

                'districts' => $division->division_district->map(function ($district) {
                    return [
                        'id' => $district->district_id,
                        'name' => $district->district_name,
                        'bn_name' => $district->district_bn_name,
                        'url' => $district->district_url,
                    ];
                })->values(),
            ],
        ], 200);
    }
}
