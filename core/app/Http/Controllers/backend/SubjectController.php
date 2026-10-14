<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index()
    {
        return view('backend.modules.subjects.index');
    }

    public function listAjax(Request $request)
    {
        $columns   = ['id', 'subject_name', 'created_at'];
        $draw      = (int) $request->input('draw', 0);
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderIdx  = (int) $request->input('order.0.column', 0);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim((string) $request->input('search.value', ''));

        $base = Subject::query()->select(['id', 'subject_name', 'created_at']);
        $total = (clone $base)->count();

        if ($searchVal !== '') {
            $base->where(function ($q) use ($searchVal) {
                $q->where('subject_name', 'like', "%{$searchVal}%");
            });
        }

        $filtered = (clone $base)->count();

        $orderCol = $columns[$orderIdx] ?? 'id';

        // Fetch sorted rows
        $rows = $base->orderBy('id', 'asc')->get();

        // Pagination after sorting (keep SI consistent with Department pattern)
        $fullSorted = $rows->values();
        $paginated = $fullSorted->slice($start, $length);

        $data = [];
        $serial = $start + 1;

        foreach ($paginated as $subject) {
            $nameCol = '<strong>' . e($subject->subject_name) . '</strong>';

            $actions = '<div class="d-inline-flex justify-content-end gap-1 w-100">
            <a href="#"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
               bg-success-focus text-success-main AjaxModal"
               data-ajax-modal="' . route('subject.editModal', $subject->id) . '"
               data-size="lg"
               data-onsuccess="subjectIndex.onSaved"
               title="Edit">
               <iconify-icon icon="lucide:edit"></iconify-icon>
            </a>

            <a href="#"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
               bg-danger-focus text-danger-main btn-subject-delete"
               data-id="' . $subject->id . '"
               data-url="' . route('subject.destroy', $subject->id) . '"
               title="Delete">
               <iconify-icon icon="mdi:delete"></iconify-icon>
            </a>
        </div>';

            $data[] = [
                $serial++,
                $nameCol,
                $actions,
            ];
        }

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }

    public function createModal()
    {
        return view('backend.modules.subjects.create_modal');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'subject_name')->whereNull('deleted_at'),
            ],
        ], [
            'subject_name.unique' => 'This subject name already exists. Please use a different name.',
        ]);

        // Normalize: trim + title-case (case-insensitive duplicate check)
        $normalizedName = ucwords(strtolower(trim($validated['subject_name'])));

        // Extra case-insensitive / trimmed check to catch "  math" vs "Math"
        $exists = Subject::whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower($normalizedName)])->exists();
        if ($exists) {
            return response()->json([
                'msg'    => 'Validation failed.',
                'errors' => ['subject_name' => ['This subject name already exists. Please use a different name.']],
            ], 422);
        }

        $data = [
            'subject_name' => $normalizedName,
        ];

        $subject = Subject::create($data);

        return response()->json([
            'status' => 'success',
            'msg'    => 'Subject created successfully.',
            'data'   => $subject,
        ], 201);
    }

    public function editModal(Request $request, Subject $subject)
    {
        return view('backend.modules.subjects.edit_modal', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'subject_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'subject_name')->whereNull('deleted_at')->ignore($subject->id),
            ],
        ], [
            'subject_name.unique' => 'This subject name already exists. Please use a different name.',
        ]);

        $normalizedName = ucwords(strtolower(trim($validated['subject_name'])));

        $exists = Subject::whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower($normalizedName)])
            ->where('id', '!=', $subject->id)
            ->exists();
        if ($exists) {
            return response()->json([
                'msg'    => 'Validation failed.',
                'errors' => ['subject_name' => ['This subject name already exists. Please use a different name.']],
            ], 422);
        }

        $data = [
            'subject_name' => $normalizedName,
        ];

        $subject->update($data);

        return response()->json([
            'status' => 'success',
            'msg'    => 'Subject updated successfully.',
            'data'   => $subject,
        ], 200);
    }

    public function destroy(Request $request, Subject $subject)
    {
        $subject->delete();

        if ($request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'msg'     => 'Subject deleted successfully.',
                'data_id' => $subject->id,
            ]);
        }

        return redirect()->route('subject.index')->with('success', 'Subject deleted.');
    }

    public function select2(Request $r)
    {
        $q = trim($r->input('q', ''));
        $base = Subject::query();

        if ($q !== '') {
            $base->where(function ($x) use ($q) {
                $x->where('subject_name', 'like', "%{$q}%");
            });
        }

        $items = $base->orderBy('id')->orderBy('subject_name')
            ->limit(20)->get(['id', 'subject_name']);

        return response()->json([
            'results' => $items->map(fn($t) => [
                'id'   => $t->id,
                'text' => $t->subject_name
            ])
        ]);
    }
}
