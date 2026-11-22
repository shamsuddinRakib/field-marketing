<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubcategoryResource;
use App\Models\backend\Subcategory;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of active subcategories.
     * Optional filter by ?category_id=X and ?search=keyword
     */
    public function index(Request $request)
    {
        $query = Subcategory::query()
            ->where('is_active', 1)
            ->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $subcategories = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'data'    => SubcategoryResource::collection($subcategories),
        ]);
    }

    /**
     * Display subcategories for a specific category.
     * Example: /api/subcategories/category/1
     */
    public function byCategory($category)
    {
        $subcategories = Subcategory::query()
            ->where('category_id', $category)
            ->where('is_active', 1)
            ->with('category')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => SubcategoryResource::collection($subcategories),
        ]);
    }

    /**
     * Display a single subcategory by ID.
     * Example: /api/subcategories/1
     */
    public function show($id)
    {
        $subcategory = Subcategory::with('category')->find($id);

        if (!$subcategory) {
            return response()->json([
                'success' => false,
                'message' => 'Subcategory not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new SubcategoryResource($subcategory),
        ]);
    }
}
