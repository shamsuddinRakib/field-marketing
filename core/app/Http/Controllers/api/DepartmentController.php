<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Get department list
     */
    public function index(Request $request)
    {
        $query = Department::query()
            ->select('id', 'department_name');

        // Search by department name
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where('department_name', 'like', "%{$search}%");
        }

        // Pagination
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $departments = $query
            ->orderBy('department_name', 'asc')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Departments retrieved successfully.',
            'data' => $departments->items(),
            'meta' => [
                'current_page' => $departments->currentPage(),
                'per_page' => $departments->perPage(),
                'total' => $departments->total(),
                'last_page' => $departments->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get single department
     */
    public function show($id)
    {
        $department = Department::select('id', 'department_name')
            ->find($id);

        if (!$department) {
            return response()->json([
                'status' => 'error',
                'message' => 'Department not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Department retrieved successfully.',
            'data' => $department,
        ], 200);
    }
}
