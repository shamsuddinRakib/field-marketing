<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\backend\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::query()
            ->whereNull('parent_id')
            ->with('category')
            ->latest('id')
            ->paginate(20);

        return ProductResource::collection($products);
    }

    /**
     * Search products by name.
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
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('slug', 'LIKE', "%{$keyword}%");
            })
            ->with('category')
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
            ->with('category')
            ->latest('id')
            ->paginate(20);

        return ProductResource::collection($products);
    }

    /**
     * Display a single product. id=65
     */
    public function show(Product $product)
    {
        $product->load('category');

        return new ProductResource($product);
    }
}
