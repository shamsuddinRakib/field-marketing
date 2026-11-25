<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\SpotSaleResource;
use App\Models\backend\CurrentStock;
use App\Models\backend\Library;
use App\Models\backend\Product;
use App\Models\backend\SpotSale;
use App\Models\backend\SpotSaleCustomer;
use App\Models\backend\SpotSaleItem;
use App\Models\backend\Student;
use App\Models\backend\Teacher;

class SpotSaleController extends Controller
{
    public function index(Request $request)
    {
        $query = SpotSale::with([
            'items.product',
            'customer.institution',
            'customer.courseClass',
            'teacher',
            'library',
            'student.courseClass',
            'student.institution',
        ])
            ->where('user_id', auth('sanctum')->id())
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('customer_type')) {
            $ctype = $request->customer_type;
            $query->where(function ($q) use ($ctype) {
                $q->whereHas('customer', fn($c) => $c->where('customer_type', $ctype));
                if ($ctype === 'teacher') {
                    $q->orWhereNotNull('teacher_id');
                } elseif ($ctype === 'library') {
                    $q->orWhereNotNull('library_id');
                } elseif ($ctype === 'student') {
                    $q->orWhereNotNull('student_id');
                }
            });
        }

        if ($request->has('page') || $request->has('per_page')) {
            $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
            return SpotSaleResource::collection($query->paginate($perPage));
        }

        return SpotSaleResource::collection($query->get());
    }

    public function show($id)
    {
        $spotSale = SpotSale::with([
            'items.product',
            'customer.institution',
            'customer.courseClass',
            'teacher',
            'library',
            'student.courseClass',
            'student.institution',
        ])
            ->where('user_id', auth('sanctum')->id())
            ->find($id);

        if (!$spotSale) {
            return response()->json([
                'success' => false,
                'message' => 'Spot sale not found.',
            ], 404);
        }

        return new SpotSaleResource($spotSale);
    }

    public function store(Request $request)
    {
        return $this->create($request);
    }

    public function create(Request $request)
    {
        // 1. Normalization: allow items array or parallel product_id & quantity arrays
        if (!$request->has('product_id') && $request->has('items') && is_array($request->input('items'))) {
            $pids = [];
            $qtys = [];
            foreach ($request->input('items') as $item) {
                if (isset($item['product_id']) && isset($item['quantity'])) {
                    $pids[] = $item['product_id'];
                    $qtys[] = $item['quantity'];
                }
            }
            $request->merge([
                'product_id' => $pids,
                'quantity'   => $qtys,
            ]);
        }

        // 2. Normalization: fallback for legacy field aliases
        $ctype = $request->input('customer_type');
        if (!$request->filled('name')) {
            $resolvedName = $request->input('customer_name')
                ?? $request->input($ctype . '_name')
                ?? $request->input('student_name')
                ?? $request->input('teacher_name')
                ?? $request->input('library_name');
            if ($resolvedName) {
                $request->merge(['name' => $resolvedName]);
            }
        }
        if (!$request->filled('phone')) {
            $resolvedPhone = $request->input('customer_phone')
                ?? $request->input($ctype . '_phone');
            if ($resolvedPhone) {
                $request->merge(['phone' => $resolvedPhone]);
            }
        }
        if (!$request->filled('institution_id') && $request->filled($ctype . '_institution_id')) {
            $request->merge(['institution_id' => $request->input($ctype . '_institution_id')]);
        }
        if (!$request->filled('course_class_id') && $request->filled('student_course_class_id')) {
            $request->merge(['course_class_id' => $request->input('student_course_class_id')]);
        }
        if (!$request->filled('address') && $request->filled($ctype . '_address')) {
            $request->merge(['address' => $request->input($ctype . '_address')]);
        }

        // 3. Clean standard validation
        $data = $request->validate([
            'items'           => 'nullable|array',
            'product_id'      => 'required|array|min:1',
            'product_id.*'    => 'required|integer|exists:products,id',
            'quantity'        => 'required|array|min:1',
            'quantity.*'      => 'required|integer|min:1',
            'customer_type'   => 'required|string|in:teacher,library,student',
            'name'            => 'required|string|max:255',
            'phone'           => 'nullable|string|max:50',
            'address'         => 'nullable|string|max:255',
            'institution_id'  => 'nullable|integer|exists:institutions,id|required_if:customer_type,student',
            'course_class_id' => 'nullable|integer|exists:course_class,id|required_if:customer_type,student',
            'discount'        => 'nullable|numeric|min:0',
            'note'            => 'nullable|string|max:255',
        ]);

        if (count($data['product_id']) !== count($data['quantity'])) {
            return response()->json([
                'success' => false,
                'message' => 'Product and quantity counts must match.',
            ], 422);
        }

        $customerType  = $data['customer_type'];
        $customerName  = trim((string) $data['name']);
        $phone         = $data['phone'] ?? null;
        $address       = $data['address'] ?? null;
        $institutionId = $data['institution_id'] ?? null;
        $courseClassId = $data['course_class_id'] ?? null;

        $teacherId = null;
        $libraryId = null;
        $studentId = null;

        // Legacy lookup fallback if customer_id was passed
        if ($customerType === 'teacher' && $request->filled('customer_id')) {
            $teacher = Teacher::find($request->input('customer_id'));
            if ($teacher) {
                $teacherId = $teacher->id;
                if (empty($phone)) $phone = $teacher->phone;
                if (empty($institutionId)) $institutionId = $teacher->institution_id;
            }
        } elseif ($customerType === 'library' && $request->filled('customer_id')) {
            $library = Library::find($request->input('customer_id'));
            if ($library) {
                $libraryId = $library->id;
                if (empty($phone)) $phone = $library->phone;
                if (empty($address)) $address = $library->address;
            }
        }

        // Merge duplicate product IDs by summing quantities
        $mergedItems = [];
        foreach ($data['product_id'] as $index => $pid) {
            $pid = (int) $pid;
            $qty = (int) $data['quantity'][$index];
            $mergedItems[$pid] = ($mergedItems[$pid] ?? 0) + $qty;
        }

        $productIds = array_keys($mergedItems);
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $userId    = auth('sanctum')->id();
        $total     = 0;
        $lineItems = [];

        foreach ($mergedItems as $productId => $quantity) {
            $product = $products->get($productId);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => "Product #{$productId} not found.",
                ], 422);
            }

            // Verify MR has sufficient stock in hand
            $stockRecord = CurrentStock::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();
            $availableStock = $stockRecord ? (int) $stockRecord->sale_stock : 0;

            if ($availableStock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient sale stock for '{$product->name}'. Available in hand: {$availableStock}, requested: {$quantity}.",
                    'errors'  => [
                        'quantity' => ["Insufficient sale stock for '{$product->name}' (Available: {$availableStock})."],
                    ],
                ], 422);
            }

            $price     = (float) ($product->price ?? 0);
            $lineTotal = round($price * $quantity, 2);
            $total    += $lineTotal;

            $lineItems[] = [
                'product_id' => $productId,
                'quantity'   => $quantity,
                'price'      => $price,
                'total'      => $lineTotal,
            ];
        }

        DB::beginTransaction();

        try {
            $customer = SpotSaleCustomer::create([
                'user_id'         => $userId,
                'customer_type'   => $customerType,
                'name'            => $customerName,
                'phone'           => $phone ?: null,
                'institution_id'  => $institutionId ?: null,
                'course_class_id' => $courseClassId ?: null,
                'address'         => $address ?: null,
            ]);

            $subtotal = round($total, 2);
            $discount = max(0, (float) ($request->input('discount', 0)));
            if ($discount > $subtotal) {
                $discount = $subtotal;
            }
            $grandTotal = round(max(0, $subtotal - $discount), 2);

            $spotSale = SpotSale::create([
                'user_id'     => $userId,
                'customer_id' => $customer->id,
                'teacher_id'  => $teacherId,
                'library_id'  => $libraryId,
                'student_id'  => $studentId,
                'subtotal'    => $subtotal,
                'discount'    => $discount,
                'total'       => $grandTotal,
                'status'      => 'pending',
                'note'        => $data['note'] ?? null,
            ]);

            foreach ($lineItems as $item) {
                SpotSaleItem::create([
                    'spot_sale_id' => $spotSale->id,
                    'product_id'   => $item['product_id'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'total'        => $item['total'],
                ]);
            }

            DB::commit();

            $spotSale->load([
                'items.product',
                'customer.institution',
                'customer.courseClass',
                'teacher',
                'library',
                'student.courseClass',
                'student.institution',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Spot sale created successfully.',
                'data'    => new SpotSaleResource($spotSale),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Spot sale creation failed.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $userId   = auth('sanctum')->id();
        $spotSale = SpotSale::where('user_id', $userId)->find($id);

        if (!$spotSale) {
            return response()->json([
                'success' => false,
                'message' => 'Spot sale not found.',
            ], 404);
        }

        if ($spotSale->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending spot sales can be updated.',
            ], 403);
        }

        // Normalization: allow items array or parallel product_id & quantity arrays
        if (!$request->has('product_id') && $request->has('items') && is_array($request->input('items'))) {
            $pids = [];
            $qtys = [];
            foreach ($request->input('items') as $item) {
                if (isset($item['product_id']) && isset($item['quantity'])) {
                    $pids[] = $item['product_id'];
                    $qtys[] = $item['quantity'];
                }
            }
            $request->merge([
                'product_id' => $pids,
                'quantity'   => $qtys,
            ]);
        }

        $data = $request->validate([
            'product_id'      => 'required|array|min:1',
            'product_id.*'    => 'required|integer|exists:products,id',
            'quantity'        => 'required|array|min:1',
            'quantity.*'      => 'required|integer|min:1',
            'customer_type'   => 'required|string|in:teacher,library,student',
            'customer_id'     => 'nullable|required_if:customer_type,teacher,library|integer',
            'student_name'    => 'nullable|required_if:customer_type,student|string|max:255',
            'course_class_id' => 'nullable|required_if:customer_type,student|integer|exists:course_class,id',
            'institution_id'  => 'nullable|required_if:customer_type,student|integer|exists:institutions,id',
            'note'            => 'nullable|string|max:255',
            'discount'        => 'nullable|numeric|min:0',
        ]);

        if (count($data['product_id']) !== count($data['quantity'])) {
            return response()->json([
                'success' => false,
                'message' => 'Product and quantity counts must match.',
            ], 422);
        }

        $teacherId = null;
        $libraryId = null;
        $studentId = null;

        if ($data['customer_type'] === 'teacher') {
            $customer = Teacher::find($data['customer_id']);

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found.',
                ], 422);
            }

            $teacherId = $customer->id;
        } elseif ($data['customer_type'] === 'library') {
            $customer = Library::find($data['customer_id']);

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Library not found.',
                ], 422);
            }

            $libraryId = $customer->id;
        }

        // Merge duplicate product IDs by summing quantities
        $mergedItems = [];
        foreach ($data['product_id'] as $index => $pid) {
            $pid = (int) $pid;
            $qty = (int) $data['quantity'][$index];
            $mergedItems[$pid] = ($mergedItems[$pid] ?? 0) + $qty;
        }

        $productIds = array_keys($mergedItems);
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $total     = 0;
        $lineItems = [];

        foreach ($mergedItems as $productId => $quantity) {
            $product = $products->get($productId);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => "Product #{$productId} not found.",
                ], 422);
            }

            // Verify MR has sufficient stock in hand
            $stockRecord = CurrentStock::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();
            $availableStock = $stockRecord ? (int) $stockRecord->sale_stock : 0;

            if ($availableStock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient sale stock for '{$product->name}'. Available in hand: {$availableStock}, requested: {$quantity}.",
                    'errors'  => [
                        'quantity' => ["Insufficient sale stock for '{$product->name}' (Available: {$availableStock})."],
                    ],
                ], 422);
            }

            $price     = (float) ($product->price ?? 0);
            $lineTotal = round($price * $quantity, 2);
            $total    += $lineTotal;

            $lineItems[] = [
                'product_id' => $productId,
                'quantity'   => $quantity,
                'price'      => $price,
                'total'      => $lineTotal,
            ];
        }

        DB::beginTransaction();

        try {
            if ($data['customer_type'] === 'student') {
                $student = $spotSale->student_id
                    ? Student::find($spotSale->student_id)
                    : null;

                if ($student) {
                    $student->update([
                        'student_name'    => $data['student_name'],
                        'course_class_id' => $data['course_class_id'],
                        'institution_id'  => $data['institution_id'],
                    ]);
                } else {
                    $student = Student::create([
                        'user_id'         => $userId,
                        'student_name'    => $data['student_name'],
                        'course_class_id' => $data['course_class_id'],
                        'institution_id'  => $data['institution_id'],
                    ]);
                }

                $studentId = $student->id;
            }

            $subtotal = round($total, 2);
            $discount = max(0, (float) ($request->input('discount', 0)));
            if ($discount > $subtotal) {
                $discount = $subtotal;
            }
            $grandTotal = round(max(0, $subtotal - $discount), 2);

            $spotSale->update([
                'teacher_id' => $teacherId,
                'library_id' => $libraryId,
                'student_id' => $studentId,
                'subtotal'   => $subtotal,
                'discount'   => $discount,
                'total'      => $grandTotal,
                'note'       => $data['note'] ?? null,
            ]);

            $spotSale->items()->delete();

            foreach ($lineItems as $item) {
                SpotSaleItem::create([
                    'spot_sale_id' => $spotSale->id,
                    'product_id'   => $item['product_id'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'total'        => $item['total'],
                ]);
            }

            DB::commit();

            $spotSale->load([
                'items.product',
                'teacher',
                'library',
                'student.courseClass',
                'student.institution',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Spot sale updated successfully.',
                'data'    => new SpotSaleResource($spotSale),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Spot sale update failed.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $spotSale = SpotSale::where('user_id', auth('sanctum')->id())->find($id);

        if (!$spotSale) {
            return response()->json([
                'success' => false,
                'message' => 'Spot sale not found.',
            ], 404);
        }

        if ($spotSale->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending spot sales can be deleted.',
            ], 403);
        }

        DB::transaction(function () use ($spotSale) {
            $spotSale->items()->delete();
            $spotSale->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Spot sale deleted successfully.',
        ]);
    }
}

