<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\Library;
use App\Models\backend\Upazila;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class LibraryController extends Controller
{
    public function index()
    {
        return view('backend.modules.libraries.index');
    }

    public function createModal()
    {
        return view('backend.modules.libraries.create_modal');
    }

    public function editModal(Library $library)
    {
        $library->loadMissing(['upazila', 'district', 'division']);

        return view('backend.modules.libraries.edit_modal', compact('library'));
    }

    public function listAjax(Request $request)
    {
        if (!Schema::hasTable('libraries')) {
            return response()->json([
                'draw' => (int) $request->input('draw'),
                'iTotalRecords' => 0,
                'iTotalDisplayRecords' => 0,
                'aaData' => [],
            ]);
        }

        $columns = ['id', 'library_name', 'code', 'email', 'phone', 'division_name', 'district_name', 'upazila_name', 'address', 'status'];
        $draw = (int) $request->input('draw');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim($request->input('search.value', ''));

        $query = Library::query()
            ->leftJoin('upazilas as u', 'u.id', '=', 'libraries.upazila_id')
            ->leftJoin('districts as d', 'd.district_id', '=', 'libraries.district_id')
            ->leftJoin('divisions as div', 'div.id', '=', 'libraries.division_id')
            ->select('libraries.*', 'u.upazila_name', 'd.district_name', 'div.name as division_name');

        $total = (clone $query)->count('libraries.id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('libraries.library_name', 'like', "%{$search}%")
                    ->orWhere('libraries.code', 'like', "%{$search}%")
                    ->orWhere('libraries.email', 'like', "%{$search}%")
                    ->orWhere('libraries.phone', 'like', "%{$search}%")
                    ->orWhere('libraries.address', 'like', "%{$search}%")
                    ->orWhere('u.upazila_name', 'like', "%{$search}%")
                    ->orWhere('d.district_name', 'like', "%{$search}%")
                    ->orWhere('div.name', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count('libraries.id');
        $orderColumn = $columns[$orderIndex] ?? 'id';
        $orderMap = [
            'upazila_name' => 'u.upazila_name',
            'district_name' => 'd.district_name',
            'division_name' => 'div.name',
            'library_name' => 'libraries.library_name',
        ];
        $orderBy = $orderMap[$orderColumn] ?? 'libraries.' . $orderColumn;
        if ($orderColumn === 'library_name') $orderBy = 'libraries.library_name';
        $query->orderBy($orderBy, $orderDirection);

        $data = $query->skip($start)->take($length)->get()->map(function ($library) {
            $status = $library->status
                ? '<span class="bg-success-focus text-success-600 border border-success-main px-12 py-4 radius-4 fw-medium text-sm">Active</span>'
                : '<span class="bg-danger-focus text-danger-600 border border-danger-main px-12 py-4 radius-4 fw-medium text-sm">Inactive</span>';

            $editUrl = \Illuminate\Support\Facades\Route::has('library.libraries.editModal')
                ? route('library.libraries.editModal', $library->id)
                : '#';
            $deleteUrl = \Illuminate\Support\Facades\Route::has('library.libraries.destroy')
                ? route('library.libraries.destroy', $library->id)
                : '#';

            $actions = '<div class="d-inline-flex align-items-center justify-content-end gap-1 w-100">'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" data-ajax-modal="' . $editUrl . '" data-size="lg" data-onload="LibrariesIndex.onLoad" data-onsuccess="LibrariesIndex.onSaved" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-library-delete" data-url="' . $deleteUrl . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>'
                . '</div>';

            $libraryName = $library->library_name;

            return [
                (int) $library->id,
                e($libraryName ?: '-'),
                e($library->code ?: '-'),
                e($library->email ?: '-'),
                e($library->phone ?: '-'),
                e($library->division_name ?: '-'),
                e($library->district_name ?: '-'),
                e($library->upazila_name ?: '-'),
                e($library->address ? \Illuminate\Support\Str::limit($library->address, 50) : '-'),
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
        if (!Schema::hasTable('libraries')) {
            return response()->json(['ok' => false, 'msg' => 'Libraries table not migrated yet.'], 422);
        }
        $library = Library::create($this->validated($request));

        return response()->json(['ok' => true, 'id' => $library->id, 'msg' => 'Library created.']);
    }

    public function update(Request $request, Library $library)
    {
        $library->update($this->validated($request, $library));

        return response()->json(['ok' => true, 'msg' => 'Library updated.']);
    }

    public function destroy(Library $library)
    {
        $library->delete();

        return response()->json(['ok' => true, 'msg' => 'Library deleted.']);
    }

    public function upazilasSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $districtId = $request->input('district_id');
        $divisionId = $request->input('division_id');
        $upazilas = Upazila::with('upazila_district.district_division')
            ->when($term !== '', fn ($query) => $query->where('upazila_name', 'like', "%{$term}%"))
            ->when($districtId, fn ($query) => $query->where('upazila_district_id', $districtId))
            ->when($divisionId && !$districtId, function ($query) use ($divisionId) {
                $query->whereHas('upazila_district', fn ($q) => $q->where('district_division_id', $divisionId));
            })
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

    public function librariesSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $libraries = Library::query()
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($b) use ($term) {
                    $b->where('library_name', 'like', "%{$term}%")
                      ->orWhere('code', 'like', "%{$term}%");
                });
            })
            ->orderBy('library_name')
            ->limit(30)
            ->get();

        return response()->json(['results' => $libraries->map(function ($lib) {
            $name = $lib->library_name;
            return [
                'id' => $lib->id,
                'text' => $name . ($lib->code ? " ({$lib->code})" : ''),
            ];
        })->values()]);
    }

    private function validated(Request $request, ?Library $library = null): array
    {
        $data = $request->validate([
            'library_name' => ['required', 'string', 'max:191'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('libraries', 'code')->ignore($library?->id)],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['required', 'string', 'max:50'],
            'upazila_id' => ['nullable', 'integer', 'exists:upazilas,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,district_id'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'boolean'],
        ]);

        return $data;
    }
}
