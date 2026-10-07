<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\SpotSaleResource;

class SpotSaleController extends Controller
{

    public function index(Request $request)
    {
        // return all spot sales under authenticated user
        $spotSales = \App\Models\backend\SpotSale::with('items.product', 'teacher', 'library')
            ->where('user_id', auth('sanctum')->id())
            ->get();
        return SpotSaleResource::collection($spotSales);
    }

    public function show($id)
    {
        $spotSale = \App\Models\backend\SpotSale::with('items.product', 'teacher', 'library')
            ->where('user_id', auth('sanctum')->id())
            ->find($id);
        if (!$spotSale) {
            return response()->json(['success' => false, 'message' => 'Spot sale not found'], 404);
        }
        return new SpotSaleResource($spotSale);
    }
    
    public function create(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|array',
            'product_id.*' => 'required|exists:products,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|integer|min:1',
            'customer_type' => 'required|string|in:teacher,library',
            'customer_id' => 'required|integer',
            'note' => 'nullable|string',
        ]);

        //fetch all products and then calculate total 
        $products = \App\Models\backend\Product::whereIn('id', $data['product_id'])->get();
        $total = 0;
        foreach ($products as $product) {
            $index = array_search($product->id, $data['product_id']);
            if ($index !== false) {
                $total += $product->price * $data['quantity'][$index];
            }
        }

        //wrap in transaction
        \DB::beginTransaction();

        try{

        //create spot sale
        if($data['customer_type'] == 'teacher'){
        $spotSale = \App\Models\backend\SpotSale::create([
            'user_id' => auth('sanctum')->id(),
            'teacher_id' => $data['customer_id'],
            'total' => $total,
            'note' => $data['note']
        ]);
        }else{
            $spotSale = \App\Models\backend\SpotSale::create([
                'user_id' => auth('sanctum')->id(),
                'library_id' => $data['customer_id'],
                'total' => $total,
                'note' => $data['note']
            ]);
        }

        //create spot sale items
        foreach ($products as $product) {
            $index = array_search($product->id, $data['product_id']);
            if ($index !== false) {
                \App\Models\backend\SpotSaleItem::create([
                    'spot_sale_id' => $spotSale->id,
                    'product_id' => $product->id,
                    'quantity' => $data['quantity'][$index],
                    'price' => $product->price,
                    'total' => $product->price * $data['quantity'][$index],
                ]);
            }
        }
        \DB::commit();
        }catch(\Exception $e){
            \DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Spot sale creation failed', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Spot sale created successfully', 'data' => $spotSale]);


    }

    public function update(Request $request, $id)
    {
          $data = $request->validate([
            'product_id' => 'required|array',
            'product_id.*' => 'required|exists:products,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|integer|min:1',
            'customer_type' => 'required|string|in:teacher,library',
            'customer_id' => 'required|integer',
            'note' => 'nullable|string',
        ]);

        $spotSale = \App\Models\backend\SpotSale::find($id);
        if (!$spotSale) {
            return response()->json(['success' => false, 'message' => 'Spot sale not found'], 404);
        }

        //fetch all products and then calculate total 
        $products = \App\Models\backend\Product::whereIn('id', $data['product_id'])->get();
        $total = 0;
        foreach ($products as $product) {
            $index = array_search($product->id, $data['product_id']);
            if ($index !== false) {
                $total += $product->price * $data['quantity'][$index];
            }
        }
        DB::beginTransaction();
        try{
        if($data['customer_type'] == 'teacher'){
            $spotSale->update([
                'teacher_id' => $data['customer_id'],
                'library_id' => null,
                'total' => $total,
                'note' => $data['note']
            ]);
        }else{
            $spotSale->update([
                'library_id' => $data['customer_id'],
                'teacher_id' => null,
                'total' => $total,
                'note' => $data['note']
            ]);
        }
        
        //delete existing items
        $spotSale->items()->delete();

        //create spot sale items
        foreach ($products as $product) {
            $index = array_search($product->id, $data['product_id']);
            if ($index !== false) {
                \App\Models\backend\SpotSaleItem::create([
                    'spot_sale_id' => $spotSale->id,
                    'product_id' => $product->id,
                    'quantity' => $data['quantity'][$index],
                    'price' => $product->price,
                    'total' => $product->price * $data['quantity'][$index],
                ]);
            }
        }

        DB::commit();
        } catch (\Exception $e){
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Spot sale update failed', 'error' => $e->getMessage()], 500);
        }
        return response()->json(['success' => true, 'message' => 'Spot sale updated successfully', 'data' => $spotSale]);
    }

    public function destroy($id)
    {
        $spotSale = \App\Models\backend\SpotSale::find($id);
        if (!$spotSale) {
            return response()->json(['success' => false, 'message' => 'Spot sale not found'], 404);
        }
        $spotSale->items()->delete(); // Delete related items first
        $spotSale->delete();


        return response()->json(['success' => true, 'message' => 'Spot sale deleted successfully']);
    }
}
