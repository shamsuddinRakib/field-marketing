<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookReturnResource;
use App\Models\backend\BookReturn;
use App\Models\backend\CurrentStock;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\Product;
use Illuminate\Http\Request;

class BookReturnController extends Controller
{
    public function index(Request $request)
    {
        $mr = MarketingRepresentative::where('user_id', auth()->id())
            ->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        $bookReturns = BookReturn::with([
            'product:id,name,slug,brand_id',
        ])
            ->where('marketing_representative_id', $mr->id)
            ->when($request->filled('return_type'), function ($q) use ($request) {
                $q->where('return_type', $request->return_type);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Book returns fetched successfully.',
            'data' => BookReturnResource::collection($bookReturns),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'return_type' => ['required', 'string', 'in:specimen,sale'],
            'returned_quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $mr = MarketingRepresentative::where('user_id', auth()->id())
            ->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        // Validate available stock in MR's hand for this return type
        $stockRecord = CurrentStock::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->first();

        $stockCol = $request->return_type === 'sale' ? 'sale_stock' : 'specimen_stock';
        $stockLabel = $request->return_type === 'sale' ? 'sale' : 'specimen';
        $availableStock = $stockRecord ? (int) $stockRecord->{$stockCol} : 0;

        if ($availableStock < (int) $request->returned_quantity) {
            $product = Product::find($request->product_id);
            $pName = $product?->name ?? ('Product #' . $request->product_id);
            return response()->json([
                'success' => false,
                'message' => "Insufficient {$stockLabel} stock for '{$pName}'. Available in hand: {$availableStock}, requested: {$request->returned_quantity}.",
            ], 422);
        }

        $bookReturn = BookReturn::create([
            'marketing_representative_id' => $mr->id,
            'institution_id' => null,
            'product_id' => $request->product_id,
            'return_type' => $request->return_type,
            'issued_quantity' => (int) $request->returned_quantity,
            'returned_quantity' => (int) $request->returned_quantity,
            'note' => $request->note,
            'status' => 'pending',
            'received_by' => null,
            'received_date' => null,
        ]);

        $bookReturn->load([
            'product:id,name,slug,brand_id',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Book return created successfully.',
            'data' => new BookReturnResource($bookReturn),
        ], 201);
    }

    public function show($id)
    {
        $mr = MarketingRepresentative::where('user_id', auth()->id())
            ->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        $bookReturn = BookReturn::with([
            'product:id,name,slug,brand_id',
        ])
            ->where('id', $id)
            ->where('marketing_representative_id', $mr->id)
            ->first();

        if (!$bookReturn) {
            return response()->json([
                'success' => false,
                'message' => 'Book return not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Book return fetched successfully.',
            'data' => new BookReturnResource($bookReturn),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'return_type' => ['required', 'string', 'in:specimen,sale'],
            'returned_quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $mr = MarketingRepresentative::where('user_id', auth()->id())
            ->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        $bookReturn = BookReturn::where('id', $id)
            ->where('marketing_representative_id', $mr->id)
            ->first();

        if (!$bookReturn) {
            return response()->json([
                'success' => false,
                'message' => 'Book return not found.',
            ], 404);
        }

        // Only pending returns can be edited.
        if ($bookReturn->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending book returns can be edited.',
            ], 403);
        }

        // Validate available stock in MR's hand for this return type
        $stockRecord = CurrentStock::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->first();

        $stockCol = $request->return_type === 'sale' ? 'sale_stock' : 'specimen_stock';
        $stockLabel = $request->return_type === 'sale' ? 'sale' : 'specimen';
        $availableStock = $stockRecord ? (int) $stockRecord->{$stockCol} : 0;

        if ($availableStock < (int) $request->returned_quantity) {
            $product = Product::find($request->product_id);
            $pName = $product?->name ?? ('Product #' . $request->product_id);
            return response()->json([
                'success' => false,
                'message' => "Insufficient {$stockLabel} stock for '{$pName}'. Available in hand: {$availableStock}, requested: {$request->returned_quantity}.",
            ], 422);
        }

        $bookReturn->update([
            'product_id' => $request->product_id,
            'return_type' => $request->return_type,
            'issued_quantity' => (int) $request->returned_quantity,
            'returned_quantity' => (int) $request->returned_quantity,
            'note' => $request->note,
        ]);

        $bookReturn->fresh()->load([
            'product:id,name,slug,brand_id',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Book return updated successfully.',
            'data' => new BookReturnResource($bookReturn),
        ]);
    }

    public function destroy($id)
    {
        $mr = MarketingRepresentative::where('user_id', auth()->id())
            ->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        $bookReturn = BookReturn::where('id', $id)
            ->where('marketing_representative_id', $mr->id)
            ->first();

        if (!$bookReturn) {
            return response()->json([
                'success' => false,
                'message' => 'Book return not found.',
            ], 404);
        }

        // Only pending returns can be deleted.
        if ($bookReturn->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending book returns can be deleted.',
            ], 403);
        }

        $bookReturn->delete();

        return response()->json([
            'success' => true,
            'message' => 'Book return deleted successfully.',
        ]);
    }
}
