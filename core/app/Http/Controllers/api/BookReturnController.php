<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookReturnResource;
use App\Models\backend\BookReturn;
use App\Models\backend\MarketingRepresentative;
use Illuminate\Http\Request;

class BookReturnController extends Controller
{
    public function index()
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
            'institution:id,name,email,phone,address,status',
            'product:id,name,slug,brand_id',
        ])
            ->where('marketing_representative_id', $mr->id)
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
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'issued_quantity' => ['required', 'integer', 'min:1'],
            'returned_quantity' => ['required', 'integer', 'min:1', 'lte:issued_quantity'],
            'note' => ['nullable', 'string'],
        ]);

        $mr = MarketingRepresentative::where('user_id', auth()->id())
            ->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        $bookReturn = BookReturn::create([
            'marketing_representative_id' => $mr->id,
            'institution_id' => $request->institution_id,
            'product_id' => $request->product_id,
            'issued_quantity' => $request->issued_quantity,
            'returned_quantity' => $request->returned_quantity,
            'note' => $request->note,

            // MR always creates as pending.
            'status' => 'pending',

            // These will be set by Admin/Backend.
            'received_by' => null,
            'received_date' => null,
        ]);

        $bookReturn->load([
            'institution:id,name,email,phone,address,status',
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
            'institution:id,name,email,phone,address,status',
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
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'issued_quantity' => ['required', 'integer', 'min:1'],
            'returned_quantity' => ['required', 'integer', 'min:1', 'lte:issued_quantity'],
            'note' => ['nullable', 'string'],
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

        $bookReturn->update([
            'institution_id' => $request->institution_id,
            'product_id' => $request->product_id,
            'issued_quantity' => $request->issued_quantity,
            'returned_quantity' => $request->returned_quantity,
            'note' => $request->note,
        ]);

        $bookReturn->fresh()->load([
            'institution:id,name,email,phone,address,status',
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
