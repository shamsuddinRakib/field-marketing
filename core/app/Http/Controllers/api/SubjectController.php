<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Get subject list
     */
    public function index(Request $request)
    {
        $query = Subject::query()
            ->select('id', 'subject_name');

        // Search by subject name
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where('subject_name', 'like', "%{$search}%");
        }

        // Pagination
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $subjects = $query
            ->orderBy('subject_name', 'asc')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Subjects retrieved successfully.',
            'data' => $subjects->items(),
            'meta' => [
                'current_page' => $subjects->currentPage(),
                'per_page' => $subjects->perPage(),
                'total' => $subjects->total(),
                'last_page' => $subjects->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get single subject
     */
    public function show($id)
    {
        $subject = Subject::select('id', 'subject_name')
            ->find($id);

        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subject not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Subject retrieved successfully.',
            'data' => $subject,
        ], 200);
    }
}
