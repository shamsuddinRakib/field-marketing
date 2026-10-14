<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignSpecimenResource;
use App\Models\backend\AssignSpecimen;
use App\Models\backend\MarketingRepresentative;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssignSpecimenController extends Controller
{
    /**
     * Return all specimen/product deliveries made by the authenticated MR
     * with pagination and optional filters.
     */
    public function index(Request $request)
    {
        $mr = MarketingRepresentative::where('user_id', auth()->id())
            ->firstOrFail();

        $perPage = min(
            max((int) $request->input('per_page', 10), 1),
            100
        );

        $query = AssignSpecimen::with($this->relations())
            ->where('user_id', auth()->id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('library_id')) {
            $query->where('library_id', $request->library_id);
        }

        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('note', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        $specimens = $query
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Assign specimens fetched successfully.',
            'data' => AssignSpecimenResource::collection($specimens->items()),
            'pagination' => [
                'current_page' => $specimens->currentPage(),
                'per_page' => $specimens->perPage(),
                'total' => $specimens->total(),
                'last_page' => $specimens->lastPage(),
                'from' => $specimens->firstItem(),
                'to' => $specimens->lastItem(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSpecimenRequest($request);

        $mr = MarketingRepresentative::where('user_id', auth()->id())->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        $specimen = AssignSpecimen::create([
            'user_id' => auth()->id(),
            'product_id' => $data['product_id'],
            'teacher_id' => $data['teacher_id'] ?? null,
            'library_id' => $data['library_id'] ?? null,
            'institution_id' => $data['institution_id'] ?? null,
            'quantity' => $data['quantity'],
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);

        $specimen->load($this->relations());

        return response()->json([
            'success' => true,
            'message' => 'Product delivery created successfully.',
            'data' => new AssignSpecimenResource($specimen),
        ], 201);
    }

    public function show($id)
    {
        $mr = MarketingRepresentative::where('user_id', auth()->id())->firstOrFail();

        $specimen = AssignSpecimen::with($this->relations())
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$specimen) {
            return response()->json([
                'success' => false,
                'message' => 'Product delivery not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product delivery fetched successfully.',
            'data' => new AssignSpecimenResource($specimen),
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validateSpecimenRequest($request);

        $mr = MarketingRepresentative::where('user_id', auth()->id())->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        $specimen = AssignSpecimen::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$specimen) {
            return response()->json([
                'success' => false,
                'message' => 'Product delivery not found.',
            ], 404);
        }

        if ($specimen->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending product deliveries can be edited.',
            ], 403);
        }

        $specimen->update([
            'product_id' => $data['product_id'],
            'teacher_id' => $data['teacher_id'] ?? null,
            'library_id' => $data['library_id'] ?? null,
            'institution_id' => $data['institution_id'] ?? null,
            'quantity' => $data['quantity'],
            'note' => $data['note'] ?? null,
        ]);

        $specimen->load($this->relations());

        return response()->json([
            'success' => true,
            'message' => 'Product delivery updated successfully.',
            'data' => new AssignSpecimenResource($specimen),
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

        $specimen = AssignSpecimen::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$specimen) {
            return response()->json([
                'success' => false,
                'message' => 'Product delivery not found.',
            ], 404);
        }

        if ($specimen->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending product deliveries can be deleted.',
            ], 403);
        }

        $specimen->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product delivery deleted successfully.',
        ]);
    }

    private function validateSpecimenRequest(Request $request): array
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'teacher_id' => ['nullable', 'integer', Rule::exists('teachers', 'id')->whereNull('deleted_at')],
            'library_id' => ['nullable', 'integer', Rule::exists('libraries', 'id')->whereNull('deleted_at')],
            'institution_id' => ['nullable', 'integer', Rule::exists('institutions', 'id')->whereNull('deleted_at')],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if (
            empty($data['teacher_id'])
            && empty($data['institution_id'])
            && empty($data['library_id'])
        ) {
            abort(response()->json([
                'success' => false,
                'message' => 'Either teacher_id, institution_id or library_id is required.',
                'errors' => [
                    'recipient' => ['Either teacher_id, institution_id or library_id is required.'],
                ],
            ], 422));
        }

        return $data;
    }

    private function relations(): array
    {
        return [
            'product:id,name,slug,sku,price,thumbnail_image,brand_id',
            'teacher:id,teacher_name,designation,department,institution_id',
            'library:id,library_name,code,phone,address',
            'institution:id,institution_name,code,phone,address',
        ];
    }
}
