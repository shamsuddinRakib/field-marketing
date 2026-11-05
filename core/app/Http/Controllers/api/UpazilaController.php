<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\Upazila;
use Illuminate\Http\Request;

class UpazilaController extends Controller
{
    /**
     * Get Upazila list
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
            ->with('upazila_district:district_id,district_name');

        // Filter by district
        if ($request->filled('district_id')) {
            $query->where('upazila_district_id', $request->district_id);
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
            return [
                'id' => $upazila->id,
                'upazila_name' => $upazila->upazila_name,
                'upazila_bn_name' => $upazila->upazila_bn_name,
                'upazila_url' => $upazila->upazila_url,
                'district' => $upazila->upazila_district ? [
                    'id' => $upazila->upazila_district->district_id,
                    'name' => $upazila->upazila_district->district_name,
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
        $upazila = Upazila::with(
            'upazila_district:district_id,district_name'
        )->find($id);

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
                ] : null,
            ],
        ], 200);
    }
}
