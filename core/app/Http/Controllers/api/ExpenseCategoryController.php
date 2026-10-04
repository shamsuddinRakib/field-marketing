<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get([
                'id',
                'name',
                'slug',
                'is_active',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense categories retrieved successfully.',
            'data' => $categories,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:expense_categories,slug'],
            'is_active' => ['required', 'boolean'],
        ]);

        $category = ExpenseCategory::create([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'is_active' => $request->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense category created successfully.',
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = ExpenseCategory::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name,' . $id],
            'slug' => ['nullable', 'string', 'max:255', 'unique:expense_categories,slug,' . $id],
            'is_active' => ['required', 'boolean'],
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'is_active' => $request->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense category updated successfully.',
            'data' => $category,
        ], 200);
    }

    public function destroy($id)
    {
        $category = ExpenseCategory::findOrFail($id);

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense category deleted successfully.',
        ], 200);
    }
}
