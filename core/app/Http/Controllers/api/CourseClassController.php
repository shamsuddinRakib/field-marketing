<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseClassResource;
use App\Models\backend\CourseClass;
use Illuminate\Http\Request;

class CourseClassController extends Controller
{
    /**
     * Get active course classes.
     */
    public function index(Request $request)
    {
        $query = CourseClass::query()
            ->where('is_active', 1)
            ->orderBy('name');

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $courseClasses = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Course classes retrieved successfully.',
            'data' => CourseClassResource::collection($courseClasses),
        ]);
    }

    /**
     * show single active course class.
     */
    public function show($id)
    {
        $courseClass = CourseClass::query()
            ->where('is_active', 1)
            ->find($id);

        if (!$courseClass) {
            return response()->json([
                'success' => false,
                'message' => 'Course class not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Course class retrieved successfully.',
            'data' => new CourseClassResource($courseClass),
        ]);
    }
}
