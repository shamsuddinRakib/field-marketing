<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FundRequestResource;
use App\Models\backend\FundRequest;
use Illuminate\Http\Request;

class FundRequestController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(
            max((int) $request->input('per_page', 10), 1),
            100
        );

        $query = FundRequest::where('user_id', auth()->id());

        // Optional status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $fundRequests = $query
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Fund requests fetched successfully.',
            'data' => FundRequestResource::collection(
                $fundRequests->items()
            ),
            'pagination' => [
                'current_page' => $fundRequests->currentPage(),
                'per_page' => $fundRequests->perPage(),
                'total' => $fundRequests->total(),
                'last_page' => $fundRequests->lastPage(),
                'from' => $fundRequests->firstItem(),
                'to' => $fundRequests->lastItem(),
            ],
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string'],
        ]);

        $fundRequest = FundRequest::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'note' => $request->note,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fund request created successfully.',
            'data' => new FundRequestResource($fundRequest),
        ], 201);
    }

    public function show($id)
    {
        $fundRequest = FundRequest::where('id', $id)
            ->where('user_id', auth()->id()) 
            ->first();

        if (!$fundRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Fund request not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fund request fetched successfully.',
            'data' => new FundRequestResource($fundRequest),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string'],
        ]);

        $fundRequest = FundRequest::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$fundRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Fund request not found.',
            ], 404);
        }

        if ($fundRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending fund requests can be edited.',
            ], 403);
        }

        $fundRequest->update([
            'amount' => $request->amount,
            'note' => $request->note,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fund request updated successfully.',
            'data' => new FundRequestResource(
                $fundRequest->fresh()
            ),
        ]);
    }


    public function destroy($id)
    {
        $fundRequest = FundRequest::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$fundRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Fund request not found.',
            ], 404);
        }

        if ($fundRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending fund requests can be deleted.',
            ], 403);
        }

        $fundRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fund request deleted successfully.',
        ]);
    }
}
