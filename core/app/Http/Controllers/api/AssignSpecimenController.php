<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignSpecimenResource;
use App\Models\backend\AssignSpecimen;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\Teacher;
use App\Models\backend\Library;
use App\Models\backend\Institution;
use App\Models\backend\CurrentStock;
use App\Models\backend\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssignSpecimenController extends Controller
{
    /**
     * Return all specimen/product deliveries made by the authenticated MR
     * with pagination and optional filters.
     */
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

        if ($request->filled('institution_id') && Schema::hasColumn('assign_specimens', 'institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        if ($request->filled('assign_inst_type')) {
            $query->where('assign_inst_type', $request->assign_inst_type);
        }

        if ($request->filled('upazila_id')) {
            $query->where('upazila_id', $request->upazila_id);
        }

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->division_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('items.product', function ($pq) use ($request) {
                $pq->where('category_id', $request->category_id);
            });
        }

        if ($request->filled('subcategory_id')) {
            $query->whereHas('items.product', function ($pq) use ($request) {
                $pq->where('subcategory_id', $request->subcategory_id);
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('note', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('items.product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('product_code', 'like', "%{$search}%")
                            ->orWhere('edition', 'like', "%{$search}%");
                    })
                    ->orWhereHas('teacher', function ($tq) use ($search) {
                        $tq->where('teacher_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('library', function ($lq) use ($search) {
                        $lq->where('library_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
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
        $mr = MarketingRepresentative::where('user_id', auth()->id())
            ->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        // Backward-compatible input normalization for items
        if (!$request->has('items') && $request->has('product_items')) {
            $request->merge(['items' => $request->input('product_items')]);
        } elseif (!$request->has('items') && $request->filled('product_id')) {
            $request->merge([
                'items' => [
                    [
                        'product_id' => $request->input('product_id'),
                        'quantity'   => $request->input('quantity', 1),
                    ],
                ],
            ]);
        }

        // Normalize 0, "0", or empty string IDs to null for smooth mobile integration
        $idFields = ['teacher_id', 'library_id', 'institution_id', 'upazila_id', 'district_id', 'division_id'];
        foreach ($idFields as $field) {
            if ($request->has($field)) {
                $val = $request->input($field);
                if ($val === 0 || $val === '0' || $val === '' || $val === null) {
                    $request->merge([$field => null]);
                }
            }
        }

        $data = $request->validate([
            'customer_type'      => ['nullable', 'string', 'in:teacher,library,institution'],
            'teacher_id'         => ['nullable', 'integer', Rule::exists('teachers', 'id')->whereNull('deleted_at')],
            'library_id'         => ['nullable', 'integer', Rule::exists('libraries', 'id')->whereNull('deleted_at')],
            'institution_id'     => ['nullable', 'integer', Rule::exists('institutions', 'id')->whereNull('deleted_at')],
            'assign_inst_type'   => ['nullable', 'string', 'in:school,college,coaching,batch'],
            'upazila_id'         => ['nullable', 'integer', 'exists:upazilas,id'],
            'district_id'        => ['nullable', 'integer', 'exists:districts,district_id'],
            'division_id'        => ['nullable', 'integer', 'exists:divisions,id'],
            'address'            => ['nullable', 'string', 'max:255'],
            'note'               => ['nullable', 'string', 'max:255'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $recipientCount = count(array_filter([
            $data['teacher_id'] ?? null,
            $data['library_id'] ?? null,
            $data['institution_id'] ?? null,
        ]));

        if ($recipientCount !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Exactly one recipient is required: teacher_id, library_id or institution_id.',
                'errors' => [
                    'recipient' => [
                        'Provide exactly one recipient ID.',
                    ],
                ],
            ], 422);
        }

        $teacherId = !empty($data['teacher_id']) ? $data['teacher_id'] : null;
        $libraryId = !empty($data['library_id']) ? $data['library_id'] : null;
        $institutionId = !empty($data['institution_id']) ? $data['institution_id'] : null;
        $assignInstType = $teacherId ? ($data['assign_inst_type'] ?? null) : null;

        if ($teacherId && empty($assignInstType)) {
            $t = Teacher::with('institution')->find($teacherId);
            $assignInstType = $this->autoDetectInstType($t?->institution?->institution_name);
        }

        $upazilaId = $data['upazila_id'] ?? null;
        $districtId = $data['district_id'] ?? null;
        $divisionId = $data['division_id'] ?? null;
        $address = $data['address'] ?? null;

        // Auto-resolve location from recipient if not provided
        if ($teacherId && (!$upazilaId || !$districtId || !$divisionId || !$address)) {
            $teacher = Teacher::with('institution')->find($teacherId);
            if ($teacher) {
                $upazilaId = $upazilaId ?? ($teacher->upazila_id ?? $teacher->institution?->upazila_id);
                $districtId = $districtId ?? ($teacher->district_id ?? $teacher->institution?->district_id);
                $divisionId = $divisionId ?? ($teacher->division_id ?? $teacher->institution?->division_id);
                $address = $address ?? ($teacher->address ?: $teacher->institution?->address);
            }
        } elseif ($libraryId && (!$upazilaId || !$districtId || !$divisionId || !$address)) {
            $library = Library::find($libraryId);
            if ($library) {
                $upazilaId = $upazilaId ?? $library->upazila_id;
                $districtId = $districtId ?? $library->district_id;
                $divisionId = $divisionId ?? $library->division_id;
                $address = $address ?? $library->address;
            }
        } elseif ($institutionId && (!$upazilaId || !$districtId || !$divisionId || !$address)) {
            $institution = Institution::find($institutionId);
            if ($institution) {
                $upazilaId = $upazilaId ?? $institution->upazila_id;
                $districtId = $districtId ?? $institution->district_id;
                $divisionId = $divisionId ?? $institution->division_id;
                $address = $address ?? $institution->address;
            }
        }

        // Merge duplicate product items by summing quantity
        $merged = [];
        foreach ($data['items'] as $item) {
            $pid = (int) $item['product_id'];
            $merged[$pid] = ($merged[$pid] ?? 0) + (int) $item['quantity'];
        }

        // Verify MR has sufficient stock in hand
        $userId     = auth()->id();
        $productIds = array_keys($merged);
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($merged as $productId => $quantity) {
            $product     = $products->get($productId);
            $productName = $product?->name ?? "Product #{$productId}";

            $stockRecord    = CurrentStock::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();
            $availableStock = $stockRecord ? (int) $stockRecord->specimen_stock : 0;

            if ($availableStock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient specimen stock for '{$productName}'. Available in hand: {$availableStock}, requested: {$quantity}.",
                    'errors'  => [
                        'quantity' => ["Insufficient specimen stock for '{$productName}' (Available: {$availableStock})."],
                    ],
                ], 422);
            }
        }

        $specimen = DB::transaction(function () use ($data, $merged, $teacherId, $libraryId, $institutionId, $assignInstType, $upazilaId, $districtId, $divisionId, $address) {
            $payload = [
                'user_id'          => auth()->id(),
                'teacher_id'       => $teacherId,
                'library_id'       => $libraryId,
                'assign_inst_type' => $assignInstType,
                'upazila_id'       => $upazilaId,
                'district_id'      => $districtId,
                'division_id'      => $divisionId,
                'address'          => $address,
                'note'             => $data['note'] ?? null,
                'status'           => 'pending',
            ];

            if ($institutionId && Schema::hasColumn('assign_specimens', 'institution_id')) {
                $payload['institution_id'] = $institutionId;
            }

            $specimen = AssignSpecimen::create($payload);

            foreach ($merged as $productId => $qty) {
                $specimen->items()->create([
                    'product_id' => $productId,
                    'quantity'   => $qty,
                ]);
            }

            return $specimen;
        });

        $specimen->load($this->relations());

        return response()->json([
            'success' => true,
            'message' => 'Assign specimen created successfully.',
            'data' => new AssignSpecimenResource($specimen),
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

        $specimen = AssignSpecimen::with($this->relations())
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$specimen) {
            return response()->json([
                'success' => false,
                'message' => 'Assign specimen not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assign specimen fetched successfully.',
            'data' => new AssignSpecimenResource($specimen),
        ]);
    }

    public function update(Request $request, $id)
    {
        $mr = MarketingRepresentative::where('user_id', auth()->id())
            ->first();

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        // Backward-compatible input normalization for items
        if (!$request->has('items') && $request->has('product_items')) {
            $request->merge(['items' => $request->input('product_items')]);
        } elseif (!$request->has('items') && $request->filled('product_id')) {
            $request->merge([
                'items' => [
                    [
                        'product_id' => $request->input('product_id'),
                        'quantity'   => $request->input('quantity', 1),
                    ],
                ],
            ]);
        }

        // Normalize 0, "0", or empty string IDs to null for smooth mobile integration
        $idFields = ['teacher_id', 'library_id', 'institution_id', 'upazila_id', 'district_id', 'division_id'];
        foreach ($idFields as $field) {
            if ($request->has($field)) {
                $val = $request->input($field);
                if ($val === 0 || $val === '0' || $val === '' || $val === null) {
                    $request->merge([$field => null]);
                }
            }
        }

        $data = $request->validate([
            'customer_type'      => ['nullable', 'string', 'in:teacher,library,institution'],
            'teacher_id'         => ['nullable', 'integer', Rule::exists('teachers', 'id')->whereNull('deleted_at')],
            'library_id'         => ['nullable', 'integer', Rule::exists('libraries', 'id')->whereNull('deleted_at')],
            'institution_id'     => ['nullable', 'integer', Rule::exists('institutions', 'id')->whereNull('deleted_at')],
            'assign_inst_type'   => ['nullable', 'string', 'in:school,college,coaching,batch'],
            'upazila_id'         => ['nullable', 'integer', 'exists:upazilas,id'],
            'district_id'        => ['nullable', 'integer', 'exists:districts,district_id'],
            'division_id'        => ['nullable', 'integer', 'exists:divisions,id'],
            'address'            => ['nullable', 'string', 'max:255'],
            'note'               => ['nullable', 'string', 'max:255'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $recipientCount = count(array_filter([
            $data['teacher_id'] ?? null,
            $data['library_id'] ?? null,
            $data['institution_id'] ?? null,
        ]));

        if ($recipientCount !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Exactly one recipient is required: teacher_id, library_id or institution_id.',
                'errors' => [
                    'recipient' => [
                        'Provide exactly one recipient ID.',
                    ],
                ],
            ], 422);
        }

        $specimen = AssignSpecimen::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$specimen) {
            return response()->json([
                'success' => false,
                'message' => 'Assign specimen not found.',
            ], 404);
        }

        if ($specimen->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending assign specimens can be edited.',
            ], 403);
        }

        $teacherId = !empty($data['teacher_id']) ? $data['teacher_id'] : null;
        $libraryId = !empty($data['library_id']) ? $data['library_id'] : null;
        $institutionId = !empty($data['institution_id']) ? $data['institution_id'] : null;
        $assignInstType = $teacherId ? ($data['assign_inst_type'] ?? null) : null;

        if ($teacherId && empty($assignInstType)) {
            $t = Teacher::with('institution')->find($teacherId);
            $assignInstType = $this->autoDetectInstType($t?->institution?->institution_name);
        }

        $upazilaId = $data['upazila_id'] ?? null;
        $districtId = $data['district_id'] ?? null;
        $divisionId = $data['division_id'] ?? null;
        $address = $data['address'] ?? null;

        // Auto-resolve location from recipient if not provided
        if ($teacherId && (!$upazilaId || !$districtId || !$divisionId || !$address)) {
            $teacher = Teacher::with('institution')->find($teacherId);
            if ($teacher) {
                $upazilaId = $upazilaId ?? ($teacher->upazila_id ?? $teacher->institution?->upazila_id);
                $districtId = $districtId ?? ($teacher->district_id ?? $teacher->institution?->district_id);
                $divisionId = $divisionId ?? ($teacher->division_id ?? $teacher->institution?->division_id);
                $address = $address ?? ($teacher->address ?: $teacher->institution?->address);
            }
        } elseif ($libraryId && (!$upazilaId || !$districtId || !$divisionId || !$address)) {
            $library = Library::find($libraryId);
            if ($library) {
                $upazilaId = $upazilaId ?? $library->upazila_id;
                $districtId = $districtId ?? $library->district_id;
                $divisionId = $divisionId ?? $library->division_id;
                $address = $address ?? $library->address;
            }
        } elseif ($institutionId && (!$upazilaId || !$districtId || !$divisionId || !$address)) {
            $institution = Institution::find($institutionId);
            if ($institution) {
                $upazilaId = $upazilaId ?? $institution->upazila_id;
                $districtId = $districtId ?? $institution->district_id;
                $divisionId = $divisionId ?? $institution->division_id;
                $address = $address ?? $institution->address;
            }
        }

        // Merge duplicate product items by summing quantity
        $merged = [];
        foreach ($data['items'] as $item) {
            $pid = (int) $item['product_id'];
            $merged[$pid] = ($merged[$pid] ?? 0) + (int) $item['quantity'];
        }

        // Verify MR has sufficient stock in hand
        $userId     = auth()->id();
        $productIds = array_keys($merged);
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($merged as $productId => $quantity) {
            $product     = $products->get($productId);
            $productName = $product?->name ?? "Product #{$productId}";

            $stockRecord    = CurrentStock::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();
            $availableStock = $stockRecord ? (int) $stockRecord->specimen_stock : 0;

            if ($availableStock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient specimen stock for '{$productName}'. Available in hand: {$availableStock}, requested: {$quantity}.",
                    'errors'  => [
                        'quantity' => ["Insufficient specimen stock for '{$productName}' (Available: {$availableStock})."],
                    ],
                ], 422);
            }
        }

        DB::transaction(function () use ($specimen, $data, $merged, $teacherId, $libraryId, $institutionId, $assignInstType, $upazilaId, $districtId, $divisionId, $address) {
            $updatePayload = [
                'teacher_id'       => $teacherId,
                'library_id'       => $libraryId,
                'assign_inst_type' => $assignInstType,
                'upazila_id'       => $upazilaId,
                'district_id'      => $districtId,
                'division_id'      => $divisionId,
                'address'          => $address,
                'note'             => $data['note'] ?? null,
            ];

            if (Schema::hasColumn('assign_specimens', 'institution_id')) {
                $updatePayload['institution_id'] = $institutionId;
            }

            $specimen->update($updatePayload);

            $specimen->items()->delete();

            foreach ($merged as $productId => $qty) {
                $specimen->items()->create([
                    'product_id' => $productId,
                    'quantity'   => $qty,
                ]);
            }
        });

        $specimen->load($this->relations());

        return response()->json([
            'success' => true,
            'message' => 'Assign specimen updated successfully.',
            'data' => new AssignSpecimenResource($specimen),
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

        $specimen = AssignSpecimen::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$specimen) {
            return response()->json([
                'success' => false,
                'message' => 'Assign specimen not found.',
            ], 404);
        }

        if ($specimen->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending assign specimens can be deleted.',
            ], 403);
        }

        DB::transaction(function () use ($specimen) {
            $specimen->items()->delete();
            $specimen->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Assign specimen deleted successfully.',
        ]);
    }

    private function relations(): array
    {
        $relations = [
            'items.product:id,name,slug,product_code,edition,price,thumbnail_image,brand_id,category_id,subcategory_id',
            'items.product.category:id,name,slug',
            'items.product.subcategory:id,name,slug',
            'items.product.brand:id,name',
            'teacher.institution',
            'teacher.institution.upazila',
            'teacher.institution.district',
            'teacher.institution.division',
            'teacher.upazila',
            'teacher.district',
            'teacher.division',
            'library.upazila',
            'library.district',
            'library.division',
            'upazila',
            'district',
            'division',
        ];

        if (Schema::hasColumn('assign_specimens', 'institution_id')) {
            $relations[] = 'institution.upazila';
            $relations[] = 'institution.district';
            $relations[] = 'institution.division';
        }

        return $relations;
    }

    private function autoDetectInstType(?string $name): string
    {
        if (!$name) {
            return 'school';
        }

        $lower = mb_strtolower($name);
        if (str_contains($lower, 'college') || str_contains($lower, 'কলেজ')) {
            return 'college';
        }
        if (str_contains($lower, 'school') || str_contains($lower, 'স্কুল') || str_contains($lower, 'বিদ্যালয়') || str_contains($lower, 'মাদ্রাসা') || str_contains($lower, 'madrasah')) {
            return 'school';
        }
        if (str_contains($lower, 'coaching') || str_contains($lower, 'কোচিং')) {
            return 'coaching';
        }
        if (str_contains($lower, 'batch') || str_contains($lower, 'ব্যাচ')) {
            return 'batch';
        }

        return 'school';
    }
}
