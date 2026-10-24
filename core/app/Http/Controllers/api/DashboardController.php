<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookRequestResource;
use App\Http\Resources\SpotSaleResource;
use App\Models\backend\BookRequest;
use App\Models\backend\DailyVisit;
use App\Models\backend\Expense;
use App\Models\backend\SpotSale;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

    // public function index(Request $request)
    // {
    //     $request->validate([
    //         'date' => ['nullable', 'date'],
    //         'from_date' => ['nullable', 'date', 'required_with:to_date'],
    //         'to_date' => [
    //             'nullable',
    //             'date',
    //             'required_with:from_date',
    //             'after_or_equal:from_date',
    //         ],
    //     ]);

    //     $today = now()->toDateString();
    //     $userId = auth()->id();

    //     // Apply date filter
    //     $applyDateFilter = function ($query, $column) use ($request, $today) {
    //         if ($request->filled('date')) {
    //             $query->whereDate($column, $request->date);
    //         } elseif ($request->filled('from_date') && $request->filled('to_date')) {
    //             $query->whereDate($column, '>=', $request->from_date)
    //                 ->whereDate($column, '<=', $request->to_date);
    //         } else {
    //             $query->whereDate($column, $today);
    //         }

    //         return $query;
    //     };

    //     // Total visits for the selected date/range
    //     $visitQuery = DailyVisit::where('user_id', $userId);

    //     $totalVisit = $applyDateFilter($visitQuery, 'visit_date')->count();

    //     // Total posted expense amount for the selected date/range
    //     $expenseQuery = Expense::where('created_by', $userId)
    //         ->where('status', 'posted');

    //     $totalExpense = $applyDateFilter(
    //         $expenseQuery,
    //         'expense_date'
    //     )->sum('total_amount');

    //     // Spot sales for the selected date/range
    //     $spotSaleQuery = SpotSale::with([
    //         'items.product',
    //         'teacher',
    //         'library',
    //     ])->where('user_id', $userId);

    //     $applyDateFilter($spotSaleQuery, 'created_at');

    //     $pendingSpotSales = (clone $spotSaleQuery)
    //         ->where('status', 'pending')
    //         ->latest()
    //         ->get();

    //     $approvedSpotSales = (clone $spotSaleQuery)
    //         ->where('status', 'approved')
    //         ->latest()
    //         ->get();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Dashboard data fetched successfully.',
    //         'data' => [
    //             'date' => $request->date ?? (
    //                 $request->filled('from_date')
    //                 ? null
    //                 : $today
    //             ),
    //             'from_date' => $request->from_date,
    //             'to_date' => $request->to_date,

    //             'total_visit' => $totalVisit,
    //             'total_expense' => (float) $totalExpense,

    //             'spot_sales' => [
    //                 'pending' => SpotSaleResource::collection(
    //                     $pendingSpotSales
    //                 ),
    //                 'approved' => SpotSaleResource::collection(
    //                     $approvedSpotSales
    //                 ),
    //             ],
    //         ],
    //     ]);
    // }

    public function index(Request $request)
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'from_date' => [
                'nullable',
                'date',
                'required_with:to_date',
            ],
            'to_date' => [
                'nullable',
                'date',
                'required_with:from_date',
                'after_or_equal:from_date',
            ],
        ]);

        $today = now()->toDateString();
        $user = auth()->user();
        $userId = $user->id;

        // Apply the selected date or date-range filter.
        $applyDateFilter = function ($query, $column) use (
            $request,
            $today
        ) {
            if ($request->filled('date')) {
                $query->whereDate($column, $request->date);
            } elseif (
                $request->filled('from_date') &&
                $request->filled('to_date')
            ) {
                $query->whereDate(
                    $column,
                    '>=',
                    $request->from_date
                )->whereDate(
                    $column,
                    '<=',
                    $request->to_date
                );
            } else {
                $query->whereDate($column, $today);
            }

            return $query;
        };

        // 1. Total visits
        $visitQuery = DailyVisit::where('user_id', $userId);

        $totalVisit = $applyDateFilter(
            $visitQuery,
            'visit_date'
        )->count();

        // 2. Total posted expenses
        $expenseQuery = Expense::where('created_by', $userId)
            ->where('status', 'posted');

        $totalExpense = $applyDateFilter(
            $expenseQuery,
            'expense_date'
        )->sum('total_amount');

        // 3. Book requests belonging to the authenticated MR
        $marketingRepresentativeId =
            $user->marketingRepresentative?->id;

        $bookRequestQuery = BookRequest::with('product')
            ->where(
                'marketing_representative_id',
                $marketingRepresentativeId ?? 0
            );

        $applyDateFilter(
            $bookRequestQuery,
            'request_date'
        );

        $totalBookRequests = (clone $bookRequestQuery)->count();

        $pendingBookRequestCount = (clone $bookRequestQuery)
            ->where('status', 'pending')
            ->count();

        $approvedBookRequestCount = (clone $bookRequestQuery)
            ->where('status', 'approved')
            ->count();

        $rejectedBookRequestCount = (clone $bookRequestQuery)
            ->where('status', 'rejected')
            ->count();

        $pendingBookRequests = (clone $bookRequestQuery)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $approvedBookRequests = (clone $bookRequestQuery)
            ->where('status', 'approved')
            ->latest()
            ->get();

        $rejectedBookRequests = (clone $bookRequestQuery)
            ->where('status', 'rejected')
            ->latest()
            ->get();

        // 4. Spot sales belonging to the authenticated user
        $spotSaleQuery = SpotSale::with([
            'items.product',
            'teacher',
            'library',
        ])->where('user_id', $userId);

        $applyDateFilter($spotSaleQuery, 'created_at');

        $totalSpotSales = (clone $spotSaleQuery)->count();

        $pendingSpotSaleCount = (clone $spotSaleQuery)
            ->where('status', 'pending')
            ->count();

        $approvedSpotSaleCount = (clone $spotSaleQuery)
            ->where('status', 'approved')
            ->count();

        $pendingSpotSales = (clone $spotSaleQuery)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $approvedSpotSales = (clone $spotSaleQuery)
            ->where('status', 'approved')
            ->latest()
            ->get();

        // 5. Response
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

                'total_book_requests' => $totalBookRequests,
                'pending_book_requests' => $pendingBookRequestCount,
                'approved_book_requests' => $approvedBookRequestCount,
                'rejected_book_requests' => $rejectedBookRequestCount,

                'total_spot_sales' => $totalSpotSales,
                'pending_spot_sales' => $pendingSpotSaleCount,
                'approved_spot_sales' => $approvedSpotSaleCount,

                'book_requests' => [
                    'pending' => BookRequestResource::collection(
                        $pendingBookRequests
                    ),
                    'approved' => BookRequestResource::collection(
                        $approvedBookRequests
                    ),
                    'rejected' => BookRequestResource::collection(
                        $rejectedBookRequests
                    ),
                ],

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
