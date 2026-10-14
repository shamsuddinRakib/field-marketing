<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpotSaleResource;
use App\Models\backend\DailyVisit;
use App\Models\backend\Expense;
use App\Models\backend\SpotSale;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    
    public function index(Request $request)
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'from_date' => ['nullable', 'date', 'required_with:to_date'],
            'to_date' => [
                'nullable',
                'date',
                'required_with:from_date',
                'after_or_equal:from_date',
            ],
        ]);

        $today = now()->toDateString();
        $userId = auth()->id();

        // Apply date filter
        $applyDateFilter = function ($query, $column) use ($request, $today) {
            if ($request->filled('date')) {
                $query->whereDate($column, $request->date);
            } elseif ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereDate($column, '>=', $request->from_date)
                    ->whereDate($column, '<=', $request->to_date);
            } else {
                $query->whereDate($column, $today);
            }

            return $query;
        };

        // Total visits for the selected date/range
        $visitQuery = DailyVisit::where('user_id', $userId);

        $totalVisit = $applyDateFilter($visitQuery, 'visit_date')->count();

        // Total posted expense amount for the selected date/range
        $expenseQuery = Expense::where('created_by', $userId)
            ->where('status', 'posted');

        $totalExpense = $applyDateFilter(
            $expenseQuery,
            'expense_date'
        )->sum('total_amount');

        // Spot sales for the selected date/range
        $spotSaleQuery = SpotSale::with([
            'items.product',
            'teacher',
            'library',
        ])->where('user_id', $userId);

        $applyDateFilter($spotSaleQuery, 'created_at');

        $pendingSpotSales = (clone $spotSaleQuery)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $approvedSpotSales = (clone $spotSaleQuery)
            ->where('status', 'approved')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard data fetched successfully.',
            'data' => [
                'date' => $request->date ?? (
                    $request->filled('from_date')
                    ? null
                    : $today
                ),
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,

                'total_visit' => $totalVisit,
                'total_expense' => (float) $totalExpense,

                'spot_sales' => [
                    'pending' => SpotSaleResource::collection(
                        $pendingSpotSales
                    ),
                    'approved' => SpotSaleResource::collection(
                        $approvedSpotSales
                    ),
                ],
            ],
        ]);
    }
}
