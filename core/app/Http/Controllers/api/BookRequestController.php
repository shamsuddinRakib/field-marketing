<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookRequestResource;
use App\Models\backend\BookRequest;
use App\Models\backend\MarketingRepresentative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookRequestController extends Controller
{
    /**
     * Return all requests that authenticated user has made with pagination and optional status filter
     */
    // public function index(Request $request)
    // {
    //     $mr = MarketingRepresentative::where('user_id', auth()->id())
    //         ->firstOrFail();

    //     $perPage = min(
    //         max((int) $request->input('per_page', 10), 1),
    //         100
    //     );

    //     $query = BookRequest::with([
    //         'product:id,name,slug,sku,brand_id'
    //     ])
    //         ->where('marketing_representative_id', $mr->id);

    //     // Optional status filter
    //     if ($request->filled('status')) {
    //         $query->where('status', $request->status);
    //     }

    //     $bookRequests = $query
    //         ->latest('id')
    //         ->paginate($perPage);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Book requests fetched successfully.',
    //         'data' => BookRequestResource::collection($bookRequests->items()),
    //         'pagination' => [
    //             'current_page' => $bookRequests->currentPage(),
    //             'per_page' => $bookRequests->perPage(),
    //             'total' => $bookRequests->total(),
    //             'last_page' => $bookRequests->lastPage(),
    //             'from' => $bookRequests->firstItem(),
    //             'to' => $bookRequests->lastItem(),
    //         ],
    //     ]);
    // }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'product_id' => ['required', 'integer', 'exists:products,id'],
    //         'quantity' => ['required', 'integer', 'min:1'],
    //         'note' => ['nullable', 'string'],
    //         'request_date' => ['nullable', 'date'],
    //     ]);

    //     $mr = MarketingRepresentative::where('user_id', auth()->id())->first();

    //     if (!$mr) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Marketing representative profile not found.',
    //         ], 404);
    //     }

    //     $bookRequest = BookRequest::create([
    //         'marketing_representative_id' => $mr->id,
    //         'product_id' => $request->product_id,
    //         'quantity' => $request->quantity,
    //         'note' => $request->note,
    //         'request_date' => $request->request_date ?? now()->toDateString(),
    //         'status' => 'pending',
    //     ]);

    //     $bookRequest->load([
    //         'product:id,name,slug,sku,brand_id'
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Book request created successfully.',
    //         'data' => new BookRequestResource($bookRequest),
    //     ], 201);
    // }

    // public function show($id)
    // {
    //     $mr = MarketingRepresentative::where('user_id', auth()->id())->firstOrFail();

    //     $bookRequest = BookRequest::with([
    //         'product:id,name,slug,sku,brand_id'
    //     ])
    //         ->where('id', $id)
    //         ->where('marketing_representative_id', $mr->id)
    //         ->first();

    //     if (!$bookRequest) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Book request not found.',
    //         ], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Book request fetched successfully.',
    //         'data' => new BookRequestResource($bookRequest),
    //     ]);
    // }

    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'product_id' => ['required', 'integer', 'exists:products,id'],
    //         'quantity' => ['required', 'integer', 'min:1'],
    //         'note' => ['nullable', 'string'],
    //         'request_date' => ['nullable', 'date'],
    //     ]);

    //     $mr = MarketingRepresentative::where('user_id', auth()->id())->first();

    //     if (!$mr) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Marketing representative profile not found.',
    //         ], 404);
    //     }

    //     $bookRequest = BookRequest::where('id', $id)
    //         ->where('marketing_representative_id', $mr->id)
    //         ->first();

    //     if (!$bookRequest) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Book request not found.',
    //         ], 404);
    //     }

    //     if ($bookRequest->status !== 'pending') {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Only pending book requests can be edited.',
    //         ], 403);
    //     }

    //     $bookRequest->update([
    //         'product_id' => $request->product_id,
    //         'quantity' => $request->quantity,
    //         'note' => $request->note,
    //         'request_date' => $request->request_date ?? $bookRequest->request_date,
    //     ]);

    //     $bookRequest->load([
    //         'product:id,name,slug,sku,brand_id'
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Book request updated successfully.',
    //         'data' => new BookRequestResource($bookRequest),
    //     ]);
    // }

    // public function destroy($id)
    // {
    //     $mr = MarketingRepresentative::where('user_id', auth()->id())->first();

    //     if (!$mr) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Marketing representative profile not found.',
    //         ], 404);
    //     }

    //     $bookRequest = BookRequest::where('id', $id)
    //         ->where('marketing_representative_id', $mr->id)
    //         ->first();

    //     if (!$bookRequest) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Book request not found.',
    //         ], 404);
    //     }

    //     if ($bookRequest->status !== 'pending') {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Only pending book requests can be deleted.',
    //         ], 403);
    //     }

    //     $bookRequest->delete();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Book request deleted successfully.',
    //     ]);
    // }

    public function index(Request $request)
    {
        $mr = MarketingRepresentative::where('user_id', auth()->id())
            ->firstOrFail();

        $perPage = min(
            max((int) $request->input('per_page', 10), 1),
            100
        );

        $query = BookRequest::with([
            'items.product:id,name,slug,sku,brand_id'
        ])
            ->where('marketing_representative_id', $mr->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bookRequests = $query
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Book requests fetched successfully.',
            'data' => BookRequestResource::collection($bookRequests->items()),
            'pagination' => [
                'current_page' => $bookRequests->currentPage(),
                'per_page' => $bookRequests->perPage(),
                'total' => $bookRequests->total(),
                'last_page' => $bookRequests->lastPage(),
                'from' => $bookRequests->firstItem(),
                'to' => $bookRequests->lastItem(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'array'],
            'product_id.*' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
            'request_date' => ['nullable', 'date'],
        ]);

        $mr = MarketingRepresentative::where('user_id', auth()->id())->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        $bookRequest = null;

        DB::beginTransaction();

        try {
            $bookRequest = BookRequest::create([
                'marketing_representative_id' => $mr->id,
                'note' => $data['note'] ?? null,
                'request_date' => $data['request_date'] ?? now()->toDateString(),
                'status' => 'pending',
            ]);

            foreach ($data['product_id'] as $index => $productId) {
                $bookRequest->items()->create([
                    'product_id' => $productId,
                    'quantity' => $data['quantity'][$index],
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Book request creation failed',
                'error' => $e->getMessage()
            ], 500);
        }

        $bookRequest->load([
            'items.product:id,name,slug,sku,brand_id'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Book request created successfully.',
            'data' => new BookRequestResource($bookRequest),
        ], 201);
    }

    public function show($id)
    {
        $mr = MarketingRepresentative::where('user_id', auth()->id())->firstOrFail();

        $bookRequest = BookRequest::with([
            'items.product:id,name,slug,sku,brand_id'
        ])
            ->where('id', $id)
            ->where('marketing_representative_id', $mr->id)
            ->first();

        if (!$bookRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Book request not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Book request fetched successfully.',
            'data' => new BookRequestResource($bookRequest),
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'product_id' => ['required', 'array'],
            'product_id.*' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
            'request_date' => ['nullable', 'date'],
        ]);

        $mr = MarketingRepresentative::where('user_id', auth()->id())->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        $bookRequest = BookRequest::where('id', $id)
            ->where('marketing_representative_id', $mr->id)
            ->first();

        if (!$bookRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Book request not found.',
            ], 404);
        }

        if ($bookRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending book requests can be edited.',
            ], 403);
        }

        DB::beginTransaction();

        try {
            $bookRequest->update([
                'note' => $data['note'] ?? $bookRequest->note,
                'request_date' => $data['request_date'] ?? $bookRequest->request_date,
            ]);

            // Purono items delete kore notun items create kora holo
            $bookRequest->items()->delete();

            foreach ($data['product_id'] as $index => $productId) {
                $bookRequest->items()->create([
                    'product_id' => $productId,
                    'quantity' => $data['quantity'][$index],
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Book request update failed',
                'error' => $e->getMessage()
            ], 500);
        }

        $bookRequest->load([
            'items.product:id,name,slug,sku,brand_id'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Book request updated successfully.',
            'data' => new BookRequestResource($bookRequest),
        ]);
    }

    public function destroy($id)
    {
        $mr = MarketingRepresentative::where('user_id', auth()->id())->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        $bookRequest = BookRequest::where('id', $id)
            ->where('marketing_representative_id', $mr->id)
            ->first();

        if (!$bookRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Book request not found.',
            ], 404);
        }

        if ($bookRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending book requests can be deleted.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $bookRequest->items()->delete();
            $bookRequest->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Book request deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Book request deleted successfully.',
        ]);
    }
}
