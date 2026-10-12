<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\DailyVisit;
use App\Models\backend\Expense;
use Illuminate\Http\Request;

class CostPerResultController extends Controller
{
    public function index()
    {
        return view('backend.modules.report.cost_per_result');
    }

    public function costPerResult(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $totalVisit = DailyVisit::whereDate('created_at', '>=', $data['start_date'])
                ->whereDate('created_at', '<=', $data['end_date'])
                ->where('user_id', $data['user_id'])
                ->count();

        $totalExpense = (float) Expense::whereDate('expense_date', '>=', $data['start_date'])
            ->whereDate('expense_date', '<=', $data['end_date'])
            ->where('user_id', $data['user_id'])
            ->sum('total_amount');

        $costPerResult = $totalVisit > 0 ? $totalExpense / $totalVisit : 0;

        return response()->json([
            'total_visit' => $totalVisit,
            'total_expense' => $totalExpense,
            'cost_per_result' => $costPerResult,
        ]);
    }
}
