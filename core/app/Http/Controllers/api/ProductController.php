<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\backend\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->whereNull('parent_id')
            ->where('is_active', 1)
            ->with(['category', 'brand']);

        // Filter by fields added in create blade
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('edition')) {
            $query->where('edition', $request->edition);
        }

        if ($request->filled('product_publish_year')) {
            $query->where('product_publish_year', $request->product_publish_year);
        }

        if ($request->filled('product_exam_year')) {
            $query->where('product_exam_year', $request->product_exam_year);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%")
                    ->orWhere('edition', 'like', "%{$search}%")
                    ->orWhere('product_publish_year', 'like', "%{$search}%")
                    ->orWhere('product_exam_year', 'like', "%{$search}%");
            });
        }

        $products = $query->latest('id')->paginate(20);

        return ProductResource::collection($products);
    }

    /**
     * Search products by name, code, edition, year.
     * Example: /api/products/search?q=book
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $keyword = trim($validated['q']);

        $products = Product::query()
            ->whereNull('parent_id')
            ->where('is_active', 1)
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('slug', 'LIKE', "%{$keyword}%")
                    ->orWhere('product_code', 'LIKE', "%{$keyword}%")
                    ->orWhere('edition', 'LIKE', "%{$keyword}%")
                    ->orWhere('product_publish_year', 'LIKE', "%{$keyword}%")
                    ->orWhere('product_exam_year', 'LIKE', "%{$keyword}%");
            })
            ->with(['category', 'brand'])
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->appends(['q' => $keyword]);

        return ProductResource::collection($products);
    }

    /**
     * Display products by category.
     * Example: /api/products/category/1
     */
    public function byCategory($category)
    {
        $products = Product::query()
            ->whereNull('parent_id')
            ->where('category_id', $category)
            ->where('is_active', 1)
            ->with(['category', 'brand'])
            ->latest('id')
            ->paginate(20);

        return ProductResource::collection($products);
    }

    /**
     * Display a single product. id=65
     */
    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'subcategory']);

        return new ProductResource($product);
    }

    /**
     * Dropdown list for MR specimen delivery.
     * Route: /api/mr/delivery-products
     */
    public function dropdown(Request $request)
    {
        $query = Product::query()
            ->whereNull('parent_id')
            ->where('is_active', 1)
            ->with(['category:id,name', 'brand:id,name']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%")
                    ->orWhere('edition', 'like', "%{$search}%")
                    ->orWhere('product_publish_year', 'like', "%{$search}%")
                    ->orWhere('product_exam_year', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('name')->paginate(50);

        return ProductResource::collection($products);
    }
}
