<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookRequestResource;
use App\Models\backend\BookRequest;
use App\Models\backend\MarketingRepresentative;
use Illuminate\Http\Request;

class BookRequestController extends Controller
{
    // public function index()
    // {
    //     $mr = MarketingRepresentative::where('user_id', auth()->id())->firstOrFail();

    //     $bookRequests = BookRequest::with([
    //         'product:id,name,slug,sku,brand_id'
    //     ])
    //         ->where('marketing_representative_id', $mr->id)
    //         ->latest('id')
    //         ->get();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Book requests fetched successfully.',
    //         'data' => BookRequestResource::collection($bookRequests),
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
            'product:id,name,slug,sku,brand_id'
        ])
            ->where('marketing_representative_id', $mr->id);

        // Optional status filter
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
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
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

        $bookRequest = BookRequest::create([
            'marketing_representative_id' => $mr->id,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'note' => $request->note,
            'request_date' => $request->request_date ?? now()->toDateString(),
            'status' => 'pending',
        ]);

        $bookRequest->load([
            'product:id,name,slug,sku,brand_id'
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
            'product:id,name,slug,sku,brand_id'
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
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
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

        $bookRequest->update([
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'note' => $request->note,
            'request_date' => $request->request_date ?? $bookRequest->request_date,
        ]);

        $bookRequest->load([
            'product:id,name,slug,sku,brand_id'
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

        $bookRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Book request deleted successfully.',
        ]);
    }
}
