<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    /**
     * Active teachers list (dropdown / search for the app).
     */
    public function index(Request $request)
    {
        $query = Teacher::query()
            ->with('institution:id,institution_name,code')
            ->where('status', 1);

        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('teacher_name', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $teachers = $query->orderBy('teacher_name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Teachers fetched successfully.',
            'data' => $teachers->map(fn ($teacher) => [
                'id' => $teacher->id,
                'name' => $teacher->teacher_name,
                'designation' => $teacher->designation,
                'department' => $teacher->department,
                'subject' => $teacher->subject,
                'class_name' => $teacher->class_name,
                'institution' => $teacher->institution ? [
                    'id' => $teacher->institution->id,
                    'name' => $teacher->institution->institution_name,
                    'code' => $teacher->institution->code,
                ] : null,
            ]),
        ]);
    }
}
