<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\BookRequestController;
use App\Http\Controllers\api\BookReturnController;
use App\Http\Controllers\api\CategoryController;
use App\Http\Controllers\api\CouponController;
use App\Http\Controllers\api\DailyVisitController;
use App\Http\Controllers\api\DashboardController;
use App\Http\Controllers\api\ExpenseCategoryController;
use App\Http\Controllers\api\ExpenseController;
use App\Http\Controllers\api\FundDistributionController;
use App\Http\Controllers\api\FundRequestController;
use App\Http\Controllers\api\OfferController;
use App\Http\Controllers\api\WebsiteSettingController;
use App\Http\Controllers\api\OrderController;
use App\Http\Controllers\api\ProductController;
use App\Http\Controllers\api\ProfileController;
use App\Http\Controllers\api\MrAuthController;
use App\Http\Controllers\api\MrLocationController;
use App\Http\Controllers\api\SpotSaleController;
use Illuminate\Http\Request;


Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
// Route::put('users', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');

// Profile
Route::get('/profile', [ProfileController::class, 'show'])->middleware('auth:sanctum');
Route::put('/profile', [ProfileController::class, 'update'])->middleware('auth:sanctum');

Route::get('categories', [CategoryController::class, 'index']);

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{slug}', [ProductController::class, 'show']);

Route::get('/swagger-test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Swagger is working perfectly!'
    ]);
});

Route::post('orders', [OrderController::class, 'create']);
// Route::get('orders', [OrderController::class, 'index'])->middleware('auth:sanctum');
Route::get('user/orders', [OrderController::class, 'userOrders'])->middleware('auth:sanctum');
Route::get('user/orders/{invoice_no}', [OrderController::class, 'show'])->middleware('auth:sanctum');
Route::get('orders/track-order/{invoice_no}', [OrderController::class, 'trackOrder']);

Route::post('/get-coupon', [CouponController::class, 'getCouponByCode']);
Route::get('/website-settings', [WebsiteSettingController::class, 'index']);

Route::get('/offers', [OfferController::class, 'index']);
Route::get('/offers/check-offer/{slug}', [ProductController::class, 'checkOffer']);
Route::get('/offers/{slug}/products', [OfferController::class, 'products']);


// Marketing-Representative
Route::prefix('mr')->name('api.mr.')->group(function () {

    // MR Login
    Route::post('/login', [MrAuthController::class, 'login'])->name('login');
    // MR Authenticated APIs
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [MrAuthController::class, 'me'])->name('me');
        Route::get('/profile', [MrAuthController::class, 'profile'])->name('profile');
        Route::put('/credentials', [MrAuthController::class, 'updateCredentials'])->name('credentials.update');

        Route::post('/logout', [MrAuthController::class, 'logout'])->name('logout');

        //Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        // Daily Visit for MR
        Route::get('/daily-visits', [DailyVisitController::class, 'index'])->name('daily-visits.index');
        Route::post('/daily-visits', [DailyVisitController::class, 'store'])->name('daily-visits.store');
        Route::get('/daily-visits/{id}', [DailyVisitController::class, 'show'])->name('daily-visits.show');

        // Expense Category
        Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index');
        Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
        Route::put('/expense-categories/{id}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
        Route::delete('/expense-categories/{id}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');

        //Expense
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');

        //Book_request
        Route::get('/book-requests', [BookRequestController::class, 'index'])->name('book-requests.index');
        Route::post('/book-requests', [BookRequestController::class, 'store'])->name('book-requests.store');
        Route::get('/book-requests/{id}', [BookRequestController::class, 'show'])->name('book-requests.show');
        Route::put('/book-requests/{id}', [BookRequestController::class, 'update'])->name('book-requests.update');
        Route::delete('/book-requests/{id}', [BookRequestController::class, 'destroy'])->name('book-requests.destroy');

        //Book_return
        Route::get('/book-returns', [BookReturnController::class, 'index'])->name('book-returns.index');
        Route::post('/book-returns', [BookReturnController::class, 'store'])->name('book-returns.store');
        Route::get('/book-returns/{id}', [BookReturnController::class, 'show'])->name('book-returns.show');
        Route::put('/book-returns/{id}', [BookReturnController::class, 'update'])->name('book-returns.update');
        Route::delete('/book-returns/{id}', [BookReturnController::class, 'destroy'])->name('book-returns.destroy');


        //Fund_request
        Route::get('/fund-requests', [FundRequestController::class, 'index'])->name('fund-requests.index');
        Route::post('/fund-requests', [FundRequestController::class, 'store'])->name('fund-requests.store');
        Route::get('/fund-requests/{id}', [FundRequestController::class, 'show'])->name('fund-requests.show');
        Route::put('/fund-requests/{id}', [FundRequestController::class, 'update'])->name('fund-requests.update');
        Route::delete('/fund-requests/{id}', [FundRequestController::class, 'destroy'])->name('fund-requests.destroy');

        //Fund_distribution
        Route::get('/fund-distributions', [FundDistributionController::class, 'index'])->name('fund-distributions.index');
        Route::get('/fund-distributions/{id}', [FundDistributionController::class, 'show'])->name('fund-distributions.show');

        //spot sale
        Route::get('/spot-sales', [SpotSaleController::class, 'index'])->name('spot-sales.index');
        Route::get('/spot-sales/{id}', [SpotSaleController::class, 'show'])->name('spot-sales.show');
        Route::post('/spot-sales', [SpotSaleController::class, 'create'])->name('spot-sales.create');
        Route::put('/spot-sales/{id}', [SpotSaleController::class, 'update'])->name('spot-sales.update');
        Route::delete('/spot-sales/{id}', [SpotSaleController::class, 'destroy'])->name('spot-sales.destroy');

        //mr_location
        Route::get('/locations', [MrLocationController::class, 'index'])->name('mr.locations.index');
        Route::post('/locations', [MrLocationController::class, 'store'])->name('mr.locations.store');
        Route::get('/locations/{id}', [MrLocationController::class, 'show'])->name('mr.locations.show');
    });
});
