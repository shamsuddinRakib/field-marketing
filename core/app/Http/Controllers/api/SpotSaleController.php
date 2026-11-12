<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\SpotSaleResource;
use App\Models\backend\Library;
use App\Models\backend\Product;
use App\Models\backend\SpotSale;
use App\Models\backend\SpotSaleItem;
use App\Models\backend\Student;
use App\Models\backend\Teacher;

class SpotSaleController extends Controller
{

    public function index(Request $request)
    {
        // return all spot sales under authenticated user
        $spotSales = SpotSale::with('items.product', 'teacher', 'library', 'student.courseClass', 'student.institution')
            ->where('user_id', auth('sanctum')->id())
            ->get();
        return SpotSaleResource::collection($spotSales);
    }

    public function show($id)
    {
        $spotSale = SpotSale::with('items.product', 'teacher', 'library', 'student.courseClass', 'student.institution')
            ->where('user_id', auth('sanctum')->id())
            ->find($id);
        if (!$spotSale) {
            return response()->json(['success' => false, 'message' => 'Spot sale not found'], 404);
        }
        return new SpotSaleResource($spotSale);
    }

    // public function create(Request $request)
    // {
    //     $data = $request->validate([
    //         'product_id' => 'required|array',
    //         'product_id.*' => 'required|exists:products,id',
    //         'quantity' => 'required|array',
    //         'quantity.*' => 'required|integer|min:1',
    //         'customer_type' => 'required|string|in:teacher,library',
    //         'customer_id' => 'required|integer',
    //         'note' => 'nullable|string',
    //     ]);

    //     //fetch all products and then calculate total 
    //     $products = Product::whereIn('id', $data['product_id'])->get();
    //     $total = 0;
    //     foreach ($products as $product) {
    //         $index = array_search($product->id, $data['product_id']);
    //         if ($index !== false) {
    //             $total += $product->price * $data['quantity'][$index];
    //         }
    //     }

    //     //wrap in transaction
    //     DB::beginTransaction();

    //     try{

    //     //create spot sale
    //     if($data['customer_type'] == 'teacher'){
    //     $spotSale = SpotSale::create([
    //         'user_id' => auth('sanctum')->id(),
    //         'teacher_id' => $data['customer_id'],
    //         'total' => $total,
    //         'note' => $data['note']
    //     ]);
    //     }else{
    //         $spotSale = SpotSale::create([
    //             'user_id' => auth('sanctum')->id(),
    //             'library_id' => $data['customer_id'],
    //             'total' => $total,
    //             'note' => $data['note']
    //         ]);
    //     }

    //     //create spot sale items
    //     foreach ($products as $product) {
    //         $index = array_search($product->id, $data['product_id']);
    //         if ($index !== false) {
    //             SpotSaleItem::create([
    //                 'spot_sale_id' => $spotSale->id,
    //                 'product_id' => $product->id,
    //                 'quantity' => $data['quantity'][$index],
    //                 'price' => $product->price,
    //                 'total' => $product->price * $data['quantity'][$index],
    //             ]);
    //         }
    //     }
    //     DB::commit();
    //     }catch(\Exception $e){
    //         DB::rollBack();
    //         return response()->json(['success' => false, 'message' => 'Spot sale creation failed', 'error' => $e->getMessage()], 500);
    //     }

    //     return response()->json(['success' => true, 'message' => 'Spot sale created successfully', 'data' => $spotSale]);


    // }

    // public function update(Request $request, $id)
    // {
    //       $data = $request->validate([
    //         'product_id' => 'required|array',
    //         'product_id.*' => 'required|exists:products,id',
    //         'quantity' => 'required|array',
    //         'quantity.*' => 'required|integer|min:1',
    //         'customer_type' => 'required|string|in:teacher,library',
    //         'customer_id' => 'required|integer',
    //         'note' => 'nullable|string',
    //     ]);

    //     $spotSale = SpotSale::find($id);
    //     if (!$spotSale) {
    //         return response()->json(['success' => false, 'message' => 'Spot sale not found'], 404);
    //     }

    //     if($spotSale->status === 'approved'){
    //         return response()->json(['success' => false, 'message' => 'Approved spot sale cannot be updated'], 400);
    //     }

    //     //fetch all products and then calculate total 
    //     $products = Product::whereIn('id', $data['product_id'])->get();
    //     $total = 0;
    //     foreach ($products as $product) {
    //         $index = array_search($product->id, $data['product_id']);
    //         if ($index !== false) {
    //             $total += $product->price * $data['quantity'][$index];
    //         }
    //     }
    //     DB::beginTransaction();
    //     try{
    //     if($data['customer_type'] == 'teacher'){
    //         $spotSale->update([
    //             'teacher_id' => $data['customer_id'],
    //             'library_id' => null,
    //             'total' => $total,
    //             'note' => $data['note']
    //         ]);
    //     }else{
    //         $spotSale->update([
    //             'library_id' => $data['customer_id'],
    //             'teacher_id' => null,
    //             'total' => $total,
    //             'note' => $data['note']
    //         ]);
    //     }

    //     //delete existing items
    //     $spotSale->items()->delete();

    //     //create spot sale items
    //     foreach ($products as $product) {
    //         $index = array_search($product->id, $data['product_id']);
    //         if ($index !== false) {
    //             SpotSaleItem::create([
    //                 'spot_sale_id' => $spotSale->id,
    //                 'product_id' => $product->id,
    //                 'quantity' => $data['quantity'][$index],
    //                 'price' => $product->price,
    //                 'total' => $product->price * $data['quantity'][$index],
    //             ]);
    //         }
    //     }

    //     DB::commit();
    //     } catch (\Exception $e){
    //         DB::rollBack();
    //         return response()->json(['success' => false, 'message' => 'Spot sale update failed', 'error' => $e->getMessage()], 500);
    //     }
    //     return response()->json(['success' => true, 'message' => 'Spot sale updated successfully', 'data' => $spotSale]);
    // }

    public function create(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|integer|distinct|exists:products,id',
            'quantity' => 'required|array|min:1',
            'quantity.*' => 'required|integer|min:1',
            'customer_type' => 'required|string|in:teacher,library,student',
            'customer_id' => 'nullable|required_if:customer_type,teacher,library|integer',
            'student_name' => 'nullable|required_if:customer_type,student|string|max:255',
            'course_class_id' => 'nullable|required_if:customer_type,student|integer|exists:course_class,id',
            'institution_id' => 'nullable|required_if:customer_type,student|integer|exists:institutions,id',
            'note' => 'nullable|string',
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

        $products = Product::whereIn('id', $data['product_id'])
            ->get()
            ->keyBy('id');

        $total = 0;

        foreach ($data['product_id'] as $index => $productId) {
            $total += $products[$productId]->price * $data['quantity'][$index];
        }

        DB::beginTransaction();

        try {
            if ($data['customer_type'] === 'student') {
                $student = Student::create([
                    'user_id' => auth('sanctum')->id(),
                    'student_name' => $data['student_name'],
                    'course_class_id' => $data['course_class_id'],
                    'institution_id' => $data['institution_id'],
                ]);

                $studentId = $student->id;
            }

            $spotSale = SpotSale::create([
                'user_id' => auth('sanctum')->id(),
                'teacher_id' => $teacherId,
                'library_id' => $libraryId,
                'student_id' => $studentId,
                'total' => $total,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($data['product_id'] as $index => $productId) {
                $product = $products[$productId];
                $quantity = $data['quantity'][$index];

                SpotSaleItem::create([
                    'spot_sale_id' => $spotSale->id,
                    'product_id' => $productId,
                    'teacher_id' => $teacherId,
                    'library_id' => $libraryId,
                    'student_id' => $studentId,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'total' => $product->price * $quantity,
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
                'message' => 'Spot sale created successfully.',
                'data' => new SpotSaleResource($spotSale),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Spot sale creation failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|integer|distinct|exists:products,id',
            'quantity' => 'required|array|min:1',
            'quantity.*' => 'required|integer|min:1',
            'customer_type' => 'required|string|in:teacher,library,student',
            'customer_id' => 'nullable|required_if:customer_type,teacher,library|integer',
            'student_name' => 'nullable|required_if:customer_type,student|string|max:255',
            'course_class_id' => 'nullable|required_if:customer_type,student|integer|exists:course_class,id',
            'institution_id' => 'nullable|required_if:customer_type,student|integer|exists:institutions,id',
            'note' => 'nullable|string',
        ]);

        if (count($data['product_id']) !== count($data['quantity'])) {
            return response()->json([
                'success' => false,
                'message' => 'Product and quantity counts must match.',
            ], 422);
        }

        $spotSale = SpotSale::where('user_id', auth('sanctum')->id())
            ->find($id);

        if (!$spotSale) {
            return response()->json([
                'success' => false,
                'message' => 'Spot sale not found.',
            ], 404);
        }

        if ($spotSale->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Approved spot sale cannot be updated.',
            ], 400);
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

        $products = Product::whereIn('id', $data['product_id'])
            ->get()
            ->keyBy('id');

        $total = 0;

        foreach ($data['product_id'] as $index => $productId) {
            $total += $products[$productId]->price * $data['quantity'][$index];
        }

        DB::beginTransaction();

        try {
            if ($data['customer_type'] === 'student') {
                $student = $spotSale->student_id
                    ? Student::find($spotSale->student_id)
                    : null;

                if ($student) {
                    $student->update([
                        'student_name' => $data['student_name'],
                        'course_class_id' => $data['course_class_id'],
                        'institution_id' => $data['institution_id'],
                    ]);
                } else {
                    $student = Student::create([
                        'user_id' => auth('sanctum')->id(),
                        'student_name' => $data['student_name'],
                        'course_class_id' => $data['course_class_id'],
                        'institution_id' => $data['institution_id'],
                    ]);
                }

                $studentId = $student->id;
            }

            $spotSale->update([
                'teacher_id' => $teacherId,
                'library_id' => $libraryId,
                'student_id' => $studentId,
                'total' => $total,
                'note' => $data['note'] ?? null,
            ]);

            $spotSale->items()->delete();

            foreach ($data['product_id'] as $index => $productId) {
                $product = $products[$productId];
                $quantity = $data['quantity'][$index];

                SpotSaleItem::create([
                    'spot_sale_id' => $spotSale->id,
                    'product_id' => $productId,
                    'teacher_id' => $teacherId,
                    'library_id' => $libraryId,
                    'student_id' => $studentId,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'total' => $product->price * $quantity,
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
                'data' => new SpotSaleResource($spotSale),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Spot sale update failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        // $spotSale = SpotSale::find($id);
        $spotSale = SpotSale::where('user_id', auth('sanctum')->id())->find($id);

        if (!$spotSale) {
            return response()->json(['success' => false, 'message' => 'Spot sale not found'], 404);
        }
        $spotSale->items()->delete(); // Delete related items first
        $spotSale->delete();


        return response()->json(['success' => true, 'message' => 'Spot sale deleted successfully']);
    }
}
