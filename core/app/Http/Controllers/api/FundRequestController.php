<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FundRequestResource;
use App\Models\backend\FundRequest;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class FundRequestController extends Controller
{
    #[OA\Get(
        path: '/api/mr/fund-requests',
        operationId: 'listFundRequests',
        tags: ['Fund Requests'],
        summary: 'List own fund requests',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'pending')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Fund requests fetched successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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


    #[OA\Post(
        path: '/api/mr/fund-requests',
        operationId: 'storeFundRequest',
        tags: ['Fund Requests'],
        summary: 'Create a fund request',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', example: 5000),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Tour advance'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Fund request created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/mr/fund-requests/{id}',
        operationId: 'showFundRequest',
        tags: ['Fund Requests'],
        summary: 'Show a single fund request',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Fund request fetched successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Fund request not found'),
        ]
    )]
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

    #[OA\Put(
        path: '/api/mr/fund-requests/{id}',
        operationId: 'updateFundRequest',
        tags: ['Fund Requests'],
        summary: 'Update a pending fund request',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', example: 6000),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Updated note'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Fund request updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Only pending fund requests can be edited'),
            new OA\Response(response: 404, description: 'Fund request not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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


    #[OA\Delete(
        path: '/api/mr/fund-requests/{id}',
        operationId: 'deleteFundRequest',
        tags: ['Fund Requests'],
        summary: 'Delete a pending fund request',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Fund request deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Only pending fund requests can be deleted'),
            new OA\Response(response: 404, description: 'Fund request not found'),
        ]
    )]
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
