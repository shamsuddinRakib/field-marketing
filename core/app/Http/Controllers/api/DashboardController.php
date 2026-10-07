<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\DailyVisit;
use App\Models\backend\Expense;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $userId = auth()->id();

        // Today's total visit
        $totalVisit = DailyVisit::where('user_id', $userId)
            ->whereDate('visit_date', $today)
            ->count();

        // Today's total posted expense
        $totalExpense = Expense::where('created_by', $userId)
            ->whereDate('expense_date', $today)
            ->where('status', 'posted')
            ->sum('total_amount');

        return response()->json([
            'success' => true,
            'message' => 'Dashboard data fetched successfully.',
            'data' => [
                'date' => $today,
                'total_visit' => $totalVisit,
                'total_expense' => (float) $totalExpense,
            ],
        ]);
    }
}
