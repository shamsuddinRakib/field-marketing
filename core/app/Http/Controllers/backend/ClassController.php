<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\CourseClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassController extends Controller
{
    public function index()
    {
        return view('backend.modules.class.index');
    }

    public function listAjax(Request $request)
    {
        $columns   = ['id', 'name', 'level', 'slug', 'is_active'];
        $draw      = (int) $request->input('draw');
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderIdx  = (int) $request->input('order.0.column', 0);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));

        $base = CourseClass::query()->select(['id', 'name', 'level', 'slug', 'is_active']);

        $total = (clone $base)->count();

        if ($searchVal !== '') {
            $base->where(function ($q) use ($searchVal) {
                $q->where('name', 'like', "%{$searchVal}%")
                    ->orWhere('level', 'like', "%{$searchVal}%")
                    ->orWhere('slug', 'like', "%{$searchVal}%");
            });
        }

        $filtered = (clone $base)->count();

        $orderCol = $columns[$orderIdx] ?? 'district_id';

        $rows = $base->orderBy($orderCol, $orderDir)
            ->skip($start)->take($length)->get();

        $data = [];
        foreach ($rows as $b) {
            $nameCol = '<strong>' . e($b->name) . '</strong>';
            $levelCol = '<strong>' . e($b->level) . '</strong>';

            $active = $b->is_active
                ? '<span class="badge text-sm fw-semibold bg-dark-success-gradient px-20 py-9 radius-4 text-white">Active</span>'
                : '<span class="badge text-sm fw-semibold bg-dark-warning-gradient px-20 py-9 radius-4 text-white">Inactive</span>';

            $actions = '<div class="d-inline-flex justify-content-end gap-1 w-100">
                <a href="' . route('class.class.edit', $b->id) . '" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
                    bg-success-focus text-success-main "
                    data-ajax-modal="' . route('class.class.edit', $b->id) . '"
                    data-size="lg"
                    data-onsuccess="BranchesIndex.onSaved"
                    title="Edit">
                    <iconify-icon icon="lucide:edit"></iconify-icon>
                </a>
                <a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-branch-delete"
                    data-id="' . $b->id . '"
                    data-url="' . route('class.class.destroy', $b->id) . '"
                    title="Delete">
                    <iconify-icon icon="mdi:delete"></iconify-icon>
                </a>
            </div>';

            // $brand_image = '<div  style="width:70px"><img src="' . image($b->image) . '" alt="img"></div>';

            $data[] = [
                $b->id,
                $nameCol,
                $levelCol,
                $slug = $b->slug,
                // $brand_image,

                $active,
                $actions,
            ];
        }

        return response()->json([
            'draw'                 => $draw,
            'iTotalRecords'        => $total,
            'iTotalDisplayRecords' => $filtered,
            'aaData'               => $data,
        ]);
    }

    public function createModal()
    {
        return view('backend.modules.class.create'); 
    }

    public function store(Request $req)
    {
        // dd($req->all());
        $data = $req->validate([
            'name'      => ['required', 'string', 'max:150', 'unique:course_class,name'],
            'level'      => ['required', 'string', 'max:150'],
            'slug'      => ['required', 'string', 'max:50', 'unique:course_class,slug'],
            // 'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'meta_title' => ['nullable', 'string', 'max:150'],
            'meta_description' => ['nullable', 'string', 'max:150'],
            'meta_keywords' => ['nullable', 'string', 'max:150'],
            'is_active' => ['required', 'integer'],


        ]);

        // $imagePath = uploadImage($req->file('image'), 'class/images');
        $metaImagePath = null;
        if ($req->hasFile('meta_image')) {
            $metaImagePath = uploadImage($req->file('meta_image'), 'class/meta_images');
        }

        $class = CourseClass::create([
            'name'      => ucwords($data['name']),
            'level'      => ucwords($data['level']),
            'slug'      => ($data['slug']),
            // 'image'  => $imagePath,
            'meta_image' => $metaImagePath,
            'meta_title' => $data['meta_title'],
            'meta_description' => $data['meta_description'],
            'meta_keywords' => $data['meta_keywords'],
            'is_active'     => $data['is_active'] ?? null,

        ]);

        return response()->json(['ok' => true, 'msg' => 'Class created', 'id' => $class->id]);
    }

    public function editModal(CourseClass $class)
    {
        return view('backend.modules.class.edit', compact('class'));
    }

    public function show(CourseClass $class)
    {

        return response()->json([
            'id'        => $class->id,
            'name'      => $class->name,
            'level'      => $class->level,
            'slug'      => $class->slug,

        ]);
    }

    public function update(Request $req, CourseClass $class)
    {

        $data = $req->validate([
            'name'      => ['string', 'max:150'],
            'level'      => ['string', 'max:150'],
            'slug'      => ['string', 'max:50'],
            'meta_title' => ['nullable', 'string', 'max:150'],
            'meta_description' => ['nullable', 'string', 'max:150'],
            'meta_keywords' => ['nullable', 'string', 'max:150'],
            // 'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => ['required', 'integer'],
        ]);



        $previousImage = $class->image;
        $previousMetaImage = $class->meta_image;



        if ($req->hasFile('image')) {
            $imagePath = uploadImage($req->file('image'), 'class/images');
            $class->image = $imagePath;

            if ($previousImage && file_exists($previousImage)) {
                unlink($previousImage);
            }
        }

        if ($req->hasFile('meta_image')) {
            $metaImagePath = uploadImage($req->file('meta_image'), 'class/meta_images');
            $class->meta_image = $metaImagePath;

            if ($previousMetaImage && file_exists($previousMetaImage)) {
                unlink($previousMetaImage);
            }
        }

        $class->name = ucwords($data['name']);
        $class->level = ucwords($data['level']);
        $class->slug = $data['slug'];
        $class->meta_title = $data['meta_title'] ?? null;
        $class->meta_description = $data['meta_description'] ?? null;
        $class->meta_keywords = $data['meta_keywords'] ?? null;
        $class->is_active = $data['is_active'];

        $class->save();

        return redirect()->route('class.class.index')
            ->with('success', 'Class updated successfully!');
    }

    public function destroy(CourseClass $class)
    {
        $class->delete();

        return response()->json(['ok' => true, 'msg' => 'Class Deleted']);
    }

    public function select2(Request $r)
    {

        $q = trim($r->input('q', ''));
        $base = CourseClass::query()->where('is_active', 1);


        if ($q !== '') {
            $base->where(function ($x) use ($q) {
                $x->where('name', 'like', "%{$q}%")
                ->orWhere('level', 'like', "%{$q}%");
            });
        }

        $items = $base->orderBy('id')->orderBy('name')
            ->limit(20)->get(['id', 'name', 'level']);


        return response()->json([
            'results' => $items->map(fn($t) => [
                'id'   => $t->id,
                'text' => $t->name
            ])
        ]);
    }
}
