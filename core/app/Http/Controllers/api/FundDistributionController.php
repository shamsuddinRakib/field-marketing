<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FundDistributionResource;
use App\Models\backend\FundDistribution;
use Illuminate\Http\Request;

class FundDistributionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(
            max((int) $request->input('per_page', 10), 1),
            100
        );

        $query = FundDistribution::where('user_id', auth()->id());

        // Optional status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $fundDistributions = $query
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Fund distributions fetched successfully.',
            'data' => FundDistributionResource::collection(
                $fundDistributions->items()
            ),
            'pagination' => [
                'current_page' => $fundDistributions->currentPage(),
                'per_page' => $fundDistributions->perPage(),
                'total' => $fundDistributions->total(),
                'last_page' => $fundDistributions->lastPage(),
                'from' => $fundDistributions->firstItem(),
                'to' => $fundDistributions->lastItem(),
            ],
        ]);
    }

    /**
     * Show a single fund distribution.
     */
    public function show($id)
    {
        $fundDistribution = FundDistribution::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$fundDistribution) {
            return response()->json([
                'success' => false,
                'message' => 'Fund distribution not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fund distribution fetched successfully.',
            'data' => new FundDistributionResource($fundDistribution),
        ]);
    }
}
