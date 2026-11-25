<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\backend\CurrentStock;
use App\Models\backend\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->whereNull('parent_id')
            ->where('is_active', 1)
            ->with(['category', 'subcategory', 'brand']);

        // Filter by fields added in create blade
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
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

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $products = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Products fetched successfully.',
            'data' => ProductResource::collection(
                $products->items()
            ),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'next_page_url' => $products->nextPageUrl(),
                'prev_page_url' => $products->previousPageUrl(),
            ],
        ]);
    }

    /**
     * Search products by name, code, edition, year.
     * Supports optional keyword (q), category_id, and subcategory_id filters.
     * Example: /api/products/search?q=book&category_id=1&subcategory_id=2
     *
     * @queryParam q string Optional search keyword (name, code, edition, year). Example: HSC
     * @queryParam category_id integer Optional category ID filter. Example: 2
     * @queryParam subcategory_id integer Optional subcategory ID filter. Example: 1
     * @queryParam per_page integer Optional items per page (default: 20). Example: 20
     */
    public function search(Request $request)
    {
        $keyword = trim((string) ($request->input('q') ?? $request->input('search') ?? ''));

        $query = Product::query()
            ->whereNull('parent_id')
            ->where('is_active', 1)
            ->with(['category', 'subcategory', 'brand']);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('slug', 'LIKE', "%{$keyword}%")
                    ->orWhere('product_code', 'LIKE', "%{$keyword}%")
                    ->orWhere('edition', 'LIKE', "%{$keyword}%")
                    ->orWhere('product_publish_year', 'LIKE', "%{$keyword}%")
                    ->orWhere('product_exam_year', 'LIKE', "%{$keyword}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->input('subcategory_id'));
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $products = $query->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->only(['q', 'search', 'category_id', 'subcategory_id', 'per_page']));

        return response()->json([
            'success' => true,
            'message' => 'Products fetched successfully.',
            'data' => ProductResource::collection(
                $products->items()
            ),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'next_page_url' => $products->nextPageUrl(),
                'prev_page_url' => $products->previousPageUrl(),
            ],
        ]);
    }

    /**
     * Display products by category.
     * Example: /api/products/category/1?subcategory_id=2
     */
    public function byCategory($category, Request $request)
    {
        $query = Product::query()
            ->whereNull('parent_id')
            ->where('category_id', $category)
            ->where('is_active', 1)
            ->with(['category', 'subcategory', 'brand']);

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $products = $query->latest('id')->paginate($perPage);

        return ProductResource::collection($products);
    }

    /**
     * Display products by subcategory.
     * Example: /api/products/subcategory/1
     */
    public function bySubcategory($subcategory, Request $request)
    {
        $query = Product::query()
            ->whereNull('parent_id')
            ->where('subcategory_id', $subcategory)
            ->where('is_active', 1)
            ->with(['category', 'subcategory', 'brand']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $products = $query->latest('id')->paginate($perPage);

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
        $userId = auth('sanctum')->id() ?? auth()->id();

        $query = Product::query()
            ->whereNull('parent_id')
            ->where('is_active', 1)
            ->with(['category:id,name', 'subcategory:id,name', 'brand:id,name']);

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

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        // If in_stock_only or copy_type filter is requested
        $copyType = $request->input('stock_type') ?: $request->input('copy_type');
        if ($userId && ($request->boolean('in_stock_only') || $request->boolean('in_stock') || $copyType)) {
            $stockQuery = CurrentStock::where('user_id', $userId);
            if ($copyType === 'specimen') {
                $stockQuery->where('specimen_stock', '>', 0);
            } elseif ($copyType === 'sale') {
                $stockQuery->where('sale_stock', '>', 0);
            } else {
                $stockQuery->where('current_stock', '>', 0);
            }

            $inStockProductIds = $stockQuery->pluck('product_id')->all();
            $query->whereIn('id', $inStockProductIds);
        }

        $perPage = min(max((int) $request->input('per_page', 50), 1), 100);
        $products = $query->orderBy('name')->paginate($perPage);

        // Preload user's stock for products on the current page
        $productIds = $products->pluck('id')->all();
        $stocks = $userId
            ? CurrentStock::where('user_id', $userId)
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id')
            : collect();

        $products->getCollection()->transform(function ($product) use ($stocks) {
            $stock = $stocks->get($product->id);
            $product->current_stock = (int) ($stock?->current_stock ?? 0);
            $product->specimen_stock = (int) ($stock?->specimen_stock ?? 0);
            $product->sale_stock = (int) ($stock?->sale_stock ?? 0);
            return $product;
        });

        return ProductResource::collection($products);
    }
}
