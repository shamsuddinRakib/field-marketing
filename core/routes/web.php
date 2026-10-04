<?php

use App\Http\Controllers\backend\AccountController;
use App\Http\Controllers\backend\AccountLedgerController;
use App\Http\Controllers\backend\AccountTransferController;
use App\Http\Controllers\backend\AccountTypeController;
use App\Http\Controllers\backend\AssignSpecimenController;
use App\Http\Controllers\backend\BookRequestController;
use App\Http\Controllers\backend\BookReturnController;
use App\Http\Controllers\backend\BranchAccountController;
use App\Http\Controllers\backend\BrandController;
use App\Http\Controllers\backend\BusinessJournalController;
use App\Http\Controllers\backend\CategoryController;
use App\Http\Controllers\backend\CategoryTypeController;
use App\Http\Controllers\backend\ClassController;
use App\Http\Controllers\backend\ColorController;
use App\Http\Controllers\backend\CompanySettingController;
use App\Http\Controllers\backend\CountryController;
use App\Http\Controllers\backend\CouponController;
use App\Http\Controllers\backend\CustomerController;
use App\Http\Controllers\backend\CustomerLedgerController;
use App\Http\Controllers\backend\DailyPaymentSummaryController;
use App\Http\Controllers\backend\DailyVisitController;
use App\Http\Controllers\backend\DepartmentController;
use App\Http\Controllers\backend\DistrictController;
use App\Http\Controllers\backend\DivisionController;
use App\Http\Controllers\backend\ExpenseCategoryController;
use App\Http\Controllers\backend\ExpenseController;
use App\Http\Controllers\backend\FiscalYearController;
use App\Http\Controllers\backend\JournalEntryController;
use App\Http\Controllers\backend\LoyaltyController;
use App\Http\Controllers\backend\OpeningBalanceController;
use App\Http\Controllers\backend\PaperQualityController;
use App\Http\Controllers\backend\PaymentTypeController;
use App\Http\Controllers\backend\PosController;
use App\Http\Controllers\backend\PosSalePaymentController;
use App\Http\Controllers\backend\ProductController;
use App\Http\Controllers\backend\ProductTypeController;
use App\Http\Controllers\backend\PurchaseController;
use App\Http\Controllers\backend\PurchasePaymentController;
use App\Http\Controllers\backend\PurchaseReceiptController;
use App\Http\Controllers\backend\PurchaseReturnController;
use App\Http\Controllers\backend\SaleReturnController;
use App\Http\Controllers\backend\SizeController;
use App\Http\Controllers\backend\SpotSaleController;
use App\Http\Controllers\backend\StockAdjustmentController;
use App\Http\Controllers\backend\StockLedgerController;
use App\Http\Controllers\backend\StockOpeningController;
use App\Http\Controllers\backend\StockTransferController;
use App\Http\Controllers\backend\SubCategoryController;
use App\Http\Controllers\backend\SupplierController;
use App\Http\Controllers\backend\UnitController;
use App\Http\Controllers\backend\UpazilaController;
use App\Http\Controllers\backend\WarehouseController;
use App\Http\Controllers\backend\SalesReportController;
use App\Http\Controllers\backend\PurchaseReportController;
use App\Http\Controllers\backend\ExpenseReportController;
use App\Http\Controllers\backend\FundDistributionController;
use App\Http\Controllers\backend\FundRequestController;
use App\Http\Controllers\backend\OfferController;
use App\Http\Controllers\backend\ReportModuleController;
use App\Http\Controllers\backend\MarketingRepresentativeController;
use App\Http\Controllers\backend\InstitutionController;
use App\Http\Controllers\backend\LibraryController;
use App\Http\Controllers\backend\ProductDistributionController;
use App\Http\Controllers\backend\SubjectController;
use App\Http\Controllers\backend\TeacherController;

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'perm', 'branchscope'])->group(function () {
    Route::prefix('country')->name('country.')->group(function () {

        Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
        Route::get('countries/create-modal', [CountryController::class, 'createModal'])->name('countries.createModal');
        Route::post('countries/list', [CountryController::class, 'listAjax'])->name('countries.list.ajax');
        Route::post('countries', [CountryController::class, 'store'])->name('countries.store');
        Route::get('countries/{country}/edit-modal', [CountryController::class, 'editModal'])->whereNumber('country')->name('countries.editModal');
        Route::get('countries/{country}', [CountryController::class, 'show'])->name('countries.show');
        Route::put('countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');
    });

    // Division Management

    Route::prefix('division')->name('division.')->group(function () {

        Route::get('divisions', [DivisionController::class, 'index'])->name('divisions.index');
        Route::get('divisions/create-modal', [DivisionController::class, 'createModal'])->name('divisions.createModal');
        Route::post('divisions/list', [DivisionController::class, 'listAjax'])->name('divisions.list.ajax');
        Route::post('divisions', [DivisionController::class, 'store'])->name('divisions.store');
        Route::get('divisions/{division}/edit-modal', [DivisionController::class, 'editModal'])->whereNumber('division')->name('divisions.editModal');
        Route::get('divisions/{division}', [DivisionController::class, 'show'])->name('divisions.show');
        Route::put('divisions/{division}', [DivisionController::class, 'update'])->name('divisions.update');
        Route::delete('divisions/{division}', [DivisionController::class, 'destroy'])->name('divisions.destroy');
    });

    // District Management

    Route::prefix('district')->name('district.')->group(function () {

        Route::get('districts', [DistrictController::class, 'index'])->name('districts.index');
        Route::get('districts/create-modal', [DistrictController::class, 'createModal'])->name('districts.createModal');
        Route::post('districts/list', [DistrictController::class, 'listAjax'])->name('districts.list.ajax');
        Route::post('districts', [DistrictController::class, 'store'])->name('districts.store');
        Route::get('districts/{district}/edit-modal', [DistrictController::class, 'editModal'])->whereNumber('district')->name('districts.editModal');
        Route::get('districts/{district}', [DistrictController::class, 'show'])->name('districts.show');
        Route::put('districts/{district}', [DistrictController::class, 'update'])->name('districts.update');
        Route::delete('districts/{district}', [DistrictController::class, 'destroy'])->name('districts.destroy');
    });

    // Upazila Management

    Route::prefix('upazila')->name('upazila.')->group(function () {

        Route::get('upazilas', [UpazilaController::class, 'index'])->name('upazilas.index');
        Route::get('upazilas/create-modal', [UpazilaController::class, 'createModal'])->name('upazilas.createModal');
        Route::post('upazilas/list', [UpazilaController::class, 'listAjax'])->name('upazilas.list.ajax');
        Route::post('upazilas', [UpazilaController::class, 'store'])->name('upazilas.store');
        Route::get('upazilas/{upazila}/edit-modal', [UpazilaController::class, 'editModal'])->whereNumber('upazila')->name('upazilas.editModal');
        Route::get('upazilas/{upazila}', [UpazilaController::class, 'show'])->name('upazilas.show');
        Route::put('upazilas/{upazila}', [UpazilaController::class, 'update'])->name('upazilas.update');
        Route::delete('upazilas/{upazila}', [UpazilaController::class, 'destroy'])->name('upazilas.destroy');
    });
    //category type management
    Route::prefix('category-type')->name('category-type.')->group(function () {

        Route::get('category-types', [CategoryTypeController::class, 'index'])->name('category-types.index');
        Route::get('category-types/create-modal', [CategoryTypeController::class, 'createModal'])->name('category-types.createModal');
        Route::post('category-types/list', [CategoryTypeController::class, 'listAjax'])->name('category-types.list.ajax');
        Route::post('category-types', [CategoryTypeController::class, 'store'])->name('category-types.store');
        Route::get('category-types/{categoryType}/edit-modal', [CategoryTypeController::class, 'editModal'])->whereNumber('categoryType')->name('category-types.editModal');
        Route::put('category-types/{categoryType}', [CategoryTypeController::class, 'update'])->name('category-types.update');
        Route::get('category-types/{CategoryType}', [CategoryTypeController::class, 'show'])->name('category-types.show');
        Route::delete('category-types/{CategoryType}', [CategoryTypeController::class, 'destroy'])->name('category-types.destroy');

        // Route::get('category-types/select2', [CategoryTypeController::class, 'select2'])->name('category-types.select2');
        // Route::get('category-types/select2', [CategoryTypeController::class, 'select2'])->name('types.select2');

    });

    // Category Management

    Route::prefix('category')->name('category.')->group(function () {

        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/create-modal', [CategoryController::class, 'createModal'])->name('categories.createModal');
        Route::post('categories/list', [CategoryController::class, 'listAjax'])->name('categories.list.ajax');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit-modal', [CategoryController::class, 'editModal'])->whereNumber('category')->name('categories.editModal');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        //live serach
        Route::get('category-types/select2', [CategoryTypeController::class, 'select2'])->name('types.select2');
        Route::get('categories/select2/type', [CategoryController::class, 'select2'])->name('cat.select2');
    });

    // Subcategory Management

    Route::prefix('subcategory')->name('subcategory.')->group(function () {

        Route::get('subcategories', [SubCategoryController::class, 'index'])->name('subcategories.index');
        Route::get('subcategories/create-modal', [SubCategoryController::class, 'createModal'])->name('subcategories.createModal');
        Route::post('subcategories/list', [SubCategoryController::class, 'listAjax'])->name('subcategories.list.ajax');
        Route::post('subcategories', [SubCategoryController::class, 'store'])->name('subcategories.store');
        Route::get('subcategories/{subcategory}/edit-modal', [SubCategoryController::class, 'editModal'])->whereNumber('subcategory')->name('subcategories.editModal');
        Route::get('subcategories/{subcategory}', [SubCategoryController::class, 'show'])->name('subcategories.show');
        Route::put('subcategories/{subcategory}', [SubCategoryController::class, 'update'])->name('subcategories.update');
        Route::delete('subcategories/{subcategory}', [SubCategoryController::class, 'destroy'])->name('subcategories.destroy');

        Route::get('subcategories/select2/type', [SubCategoryController::class, 'select2'])->name('select2');
    });

    // Brands Management

    Route::prefix('brand')->name('brand.')->group(function () {

        Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
        Route::get('brands/create', [BrandController::class, 'createModal'])->name('brands.create');
        Route::post('brands/list', [BrandController::class, 'listAjax'])->name('brands.list.ajax');
        Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
        Route::get('brands/edit/{brand}', [BrandController::class, 'editModal'])->whereNumber('brand')->name('brands.edit');
        Route::put('brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
        Route::get('brands/{brand}', [BrandController::class, 'show'])->name('brands.show');
        Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
        Route::get('brand/select2/type', [BrandController::class, 'select2'])->name('select2');
    });
    // Class Management

    Route::prefix('class')->name('class.')->group(function () {

        Route::get('classes', [ClassController::class, 'index'])->name('class.index');
        Route::get('classes/create', [ClassController::class, 'createModal'])->name('class.create');
        Route::post('classes/list', [ClassController::class, 'listAjax'])->name('class.list.ajax');
        Route::post('classes', [ClassController::class, 'store'])->name('class.store');
        Route::get('classes/edit/{class}', [ClassController::class, 'editModal'])->whereNumber('class')->name('class.edit');
        Route::put('classes/{class}', [ClassController::class, 'update'])->name('class.update');
        Route::get('classes/{class}', [ClassController::class, 'show'])->name('class.show');
        Route::delete('classes/{class}', [ClassController::class, 'destroy'])->name('class.destroy');
        Route::get('classes/select2/type', [ClassController::class, 'select2'])->name('select2');
    });

    //Department Management
    Route::prefix('department')->group(function () {

        Route::get('department', [DepartmentController::class, 'index'])->name('department.index');
        Route::get('create-modal', [DepartmentController::class, 'createModal'])->name('department.createModal');
        Route::post('list', [DepartmentController::class, 'listAjax'])->name('department.list.ajax');
        Route::post('department', [DepartmentController::class, 'store'])->name('department.store');
        Route::get('edit-modal/{department}', [DepartmentController::class, 'editModal'])->whereNumber('department')->name('department.editModal');
        Route::put('{department}', [DepartmentController::class, 'update'])->name('department.update');
        Route::delete('delete/{department}', [DepartmentController::class, 'destroy'])->name('department.destroy');
        Route::get('department/select2', [DepartmentController::class, 'select2'])->name('department.select2');
    });

    // Marketing Representative Management (backend)
    Route::prefix('marketing-representative')->name('marketing-representative.')->group(function () {
        Route::get('marketing-representatives', [MarketingRepresentativeController::class, 'index'])->name('marketing-representatives.index');
        Route::post('marketing-representatives/list', [MarketingRepresentativeController::class, 'listAjax'])->name('marketing-representatives.list.ajax');
        Route::get('marketing-representatives/create-modal', [MarketingRepresentativeController::class, 'createModal'])->name('marketing-representatives.createModal');
        Route::get('marketing-representatives/upazilas-select2', [MarketingRepresentativeController::class, 'upazilasSelect2'])->name('marketing-representatives.upazilas.select2');
        Route::get('marketing-representatives/select2', [MarketingRepresentativeController::class, 'select2'])->name('marketing-representatives.select2');
        Route::post('marketing-representatives', [MarketingRepresentativeController::class, 'store'])->name('marketing-representatives.store');
        Route::get('marketing-representatives/{representative}', [MarketingRepresentativeController::class, 'show'])->whereNumber('representative')->name('marketing-representatives.show');
        Route::get('marketing-representatives/{representative}/edit-modal', [MarketingRepresentativeController::class, 'editModal'])->whereNumber('representative')->name('marketing-representatives.editModal');
        Route::put('marketing-representatives/{representative}', [MarketingRepresentativeController::class, 'update'])->whereNumber('representative')->name('marketing-representatives.update');
        Route::delete('marketing-representatives/{representative}', [MarketingRepresentativeController::class, 'destroy'])->whereNumber('representative')->name('marketing-representatives.destroy');
    });

    // institution

    Route::prefix('institution')->name('institution.')->group(function () {
        Route::get('institutions', [InstitutionController::class, 'index'])->name('institutions.index');
        Route::post('institutions/list', [InstitutionController::class, 'listAjax'])->name('institutions.list.ajax');
        Route::get('institutions/create-modal', [InstitutionController::class, 'createModal'])->name('institutions.createModal');
        Route::get('institutions/upazilas-select2', [InstitutionController::class, 'upazilasSelect2'])->name('institutions.upazilas.select2');
        Route::get('institutions/select2', [InstitutionController::class, 'institutionsSelect2'])->name('institutions.select2');
        Route::post('institutions', [InstitutionController::class, 'store'])->name('institutions.store');
        Route::get('institutions/{institution}/edit-modal', [InstitutionController::class, 'editModal'])->whereNumber('institution')->name('institutions.editModal');
        Route::put('institutions/{institution}', [InstitutionController::class, 'update'])->whereNumber('institution')->name('institutions.update');
        Route::delete('institutions/{institution}', [InstitutionController::class, 'destroy'])->whereNumber('institution')->name('institutions.destroy');
    });
    Route::prefix('teacher')->name('teacher.')->group(function () {
        Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::post('teachers/list', [TeacherController::class, 'listAjax'])->name('teachers.list.ajax');
        Route::get('teachers/create-modal', [TeacherController::class, 'createModal'])->name('teachers.createModal');
        Route::get('teachers/institutions-select2', [TeacherController::class, 'institutionsSelect2'])->name('teachers.institutions.select2');
        Route::post('teachers', [TeacherController::class, 'store'])->name('teachers.store');
        Route::get('teachers/{teacher}/edit-modal', [TeacherController::class, 'editModal'])->whereNumber('teacher')->name('teachers.editModal');
        Route::put('teachers/{teacher}', [TeacherController::class, 'update'])->whereNumber('teacher')->name('teachers.update');
        Route::delete('teachers/{teacher}', [TeacherController::class, 'destroy'])->whereNumber('teacher')->name('teachers.destroy');
    });

    Route::prefix('subject')->group(function () {

        Route::get('subjects', [SubjectController::class, 'index'])->name('subject.index');
        Route::get('create-modal', [SubjectController::class, 'createModal'])->name('subject.createModal');
        Route::post('list', [SubjectController::class, 'listAjax'])->name('subject.list.ajax');
        Route::post('subjects', [SubjectController::class, 'store'])->name('subject.store');
        Route::get('edit-modal/{subject}', [SubjectController::class, 'editModal'])->whereNumber('subject')->name('subject.editModal');
        Route::put('{subject}', [SubjectController::class, 'update'])->name('subject.update');
        Route::delete('delete/{subject}', [SubjectController::class, 'destroy'])->name('subject.destroy');
        Route::get('subject/select2', [SubjectController::class, 'select2'])->name('subject.select2');
    });

    // library

    Route::prefix('library')->name('library.')->group(function () {
        Route::get('libraries', [LibraryController::class, 'index'])->name('libraries.index');
        Route::post('libraries/list', [LibraryController::class, 'listAjax'])->name('libraries.list.ajax');
        Route::get('libraries/create-modal', [LibraryController::class, 'createModal'])->name('libraries.createModal');
        Route::get('libraries/upazilas-select2', [LibraryController::class, 'upazilasSelect2'])->name('libraries.upazilas.select2');
        Route::get('libraries/select2', [LibraryController::class, 'librariesSelect2'])->name('libraries.select2');
        Route::post('libraries', [LibraryController::class, 'store'])->name('libraries.store');
        Route::get('libraries/{library}/edit-modal', [LibraryController::class, 'editModal'])->whereNumber('library')->name('libraries.editModal');
        Route::put('libraries/{library}', [LibraryController::class, 'update'])->whereNumber('library')->name('libraries.update');
        Route::delete('libraries/{library}', [LibraryController::class, 'destroy'])->whereNumber('library')->name('libraries.destroy');
    });




    Route::prefix('product-distributions')->name('product-distribution.')->group(function () {
        Route::get('/', [ProductDistributionController::class, 'index'])->name('index');
        Route::post('/list-ajax', [ProductDistributionController::class, 'listAjax'])->name('listAjax');
        Route::get('/create', [ProductDistributionController::class, 'createModal'])->name('create');
        Route::post('/store', [ProductDistributionController::class, 'store'])->name('store');
        Route::get('/edit/{productDistribution}', [ProductDistributionController::class, 'editModal'])->whereNumber('productDistribution')->name('edit');
        Route::put('/update/{productDistribution}', [ProductDistributionController::class, 'update'])->name('update');
        Route::delete('/{productDistribution}', [ProductDistributionController::class, 'destroy'])->name('destroy');
        Route::get('/status-modal/{productDistribution}', [ProductDistributionController::class, 'statusModal'])->name('statusModal');
        Route::post('/status-modal/{productDistribution}', [ProductDistributionController::class, 'updateStatus'])->name('updateStatus');
    });

    //fund distribution
    Route::prefix('fund-distributions')->name('fund-distribution.')->group(function () {
        Route::get('/', [FundDistributionController::class, 'index'])->name('index');
        Route::post('/list-ajax', [FundDistributionController::class, 'listAjax'])->name('listAjax');
        Route::get('/create', [FundDistributionController::class, 'createModal'])->name('create');
        Route::post('/store', [FundDistributionController::class, 'store'])->name('store');
        Route::get('/edit/{fundDistribution}', [FundDistributionController::class, 'editModal'])->whereNumber('fundDistribution')->name('edit');
        Route::put('/update/{fundDistribution}', [FundDistributionController::class, 'update'])->name('update');
        Route::delete('/{fundDistribution}', [FundDistributionController::class, 'destroy'])->name('destroy');
        Route::get('/status-modal/{fundDistribution}', [FundDistributionController::class, 'statusModal'])->name('statusModal');
        Route::post('/status-modal/{fundDistribution}', [FundDistributionController::class, 'updateStatus'])->name('updateStatus');
    });

    Route::prefix('daily-visit')->name('daily-visit.')->group(function () {
        Route::get('/', [DailyVisitController::class, 'index'])->name('index');
        Route::post('/list-ajax', [DailyVisitController::class, 'listAjax'])->name('listAjax');
        Route::get('/create', [DailyVisitController::class, 'createModal'])->name('create');
        Route::post('/store', [DailyVisitController::class, 'store'])->name('store');
        Route::get('/status-modal/{dailyVisit}', [DailyVisitController::class, 'statusModal'])->name('statusModal');
        Route::get('/select2', [DailyVisitController::class, 'select2'])->name('select2');
        Route::post('/status-modal/{dailyVisit}', [DailyVisitController::class, 'updateStatus'])->name('updateStatus');
    });

    // assign specimen
    Route::prefix('assign-specimen')->name('assign-specimen.')->group(function () {
        Route::get('/', [AssignSpecimenController::class, 'index'])->name('index');
        Route::post('/list-ajax', [AssignSpecimenController::class, 'listAjax'])->name('listAjax');
        Route::get('/create', [AssignSpecimenController::class, 'createModal'])->name('create');
        Route::post('/store', [AssignSpecimenController::class, 'store'])->name('store');
        Route::get('/status-modal/{assignSpecimen}', [AssignSpecimenController::class, 'statusModal'])->name('statusModal');
        Route::get('/edit/{assignSpecimen}', [AssignSpecimenController::class, 'editModal'])->whereNumber('assignSpecimen')->name('edit');
        Route::put('/update/{assignSpecimen}', [AssignSpecimenController::class, 'update'])->name('update');
        Route::post('/status-modal/{assignSpecimen}', [AssignSpecimenController::class, 'updateStatus'])->name('updateStatus');
        Route::delete('/{assignSpecimen}', [AssignSpecimenController::class, 'destroy'])->name('destroy');
    });
    // fund request
    Route::prefix('fund-request')->name('fund-request.')->group(function () {
        Route::get('/', [FundRequestController::class, 'index'])->name('index');
        Route::post('/list-ajax', [FundRequestController::class, 'listAjax'])->name('listAjax');
        Route::get('/create', [FundRequestController::class, 'createModal'])->name('create');
        Route::post('/store', [FundRequestController::class, 'store'])->name('store');
        Route::get('/status-modal/{fundRequest}', [FundRequestController::class, 'statusModal'])->name('statusModal');
        Route::get('/edit/{fundRequest}', [FundRequestController::class, 'editModal'])->whereNumber('fundRequest')->name('edit');
        Route::put('/update/{fundRequest}', [FundRequestController::class, 'update'])->name('update');
        Route::post('/status-modal/{fundRequest}', [FundRequestController::class, 'updateStatus'])->name('updateStatus');
        Route::delete('/{fundRequest}', [FundRequestController::class, 'destroy'])->name('destroy');
    });

    // Book Request Management

    Route::prefix('book-request')->name('book-request.')->group(function () {
        Route::get('book-requests', [BookRequestController::class, 'index'])->name('book-requests.index');
        Route::post('book-requests/list', [BookRequestController::class, 'listAjax'])->name('book-requests.list.ajax');
        Route::get('book-requests/create-modal', [BookRequestController::class, 'createModal'])->name('book-requests.createModal');
        Route::get('book-requests/representatives-select2', [BookRequestController::class, 'representativesSelect2'])->name('book-requests.representatives.select2');
        Route::post('book-requests', [BookRequestController::class, 'store'])->name('book-requests.store');
        Route::get('book-requests/{bookRequest}', [BookRequestController::class, 'show'])->whereNumber('bookRequest')->name('book-requests.show');
        Route::get('book-requests/{bookRequest}/edit-modal', [BookRequestController::class, 'editModal'])->whereNumber('bookRequest')->name('book-requests.editModal');
        Route::put('book-requests/{bookRequest}', [BookRequestController::class, 'update'])->whereNumber('bookRequest')->name('book-requests.update');
        Route::delete('book-requests/{bookRequest}', [BookRequestController::class, 'destroy'])->whereNumber('bookRequest')->name('book-requests.destroy');
    });

    // Book Return Management

    Route::prefix('book-return')->name('book-return.')->group(function () {
        Route::get('book-returns', [BookReturnController::class, 'index'])->name('book-returns.index');
        Route::post('book-returns/list', [BookReturnController::class, 'listAjax'])->name('book-returns.list.ajax');
        Route::get('book-returns/create-modal', [BookReturnController::class, 'createModal'])->name('book-returns.createModal');
        Route::get('book-returns/representatives-select2', [BookReturnController::class, 'representativesSelect2'])->name('book-returns.representatives.select2');
        Route::post('book-returns', [BookReturnController::class, 'store'])->name('book-returns.store');
        Route::get('book-returns/{bookReturn}', [BookReturnController::class, 'show'])->whereNumber('bookReturn')->name('book-returns.show');
        Route::get('book-returns/{bookReturn}/edit-modal', [BookReturnController::class, 'editModal'])->whereNumber('bookReturn')->name('book-returns.editModal');
        Route::put('book-returns/{bookReturn}', [BookReturnController::class, 'update'])->whereNumber('bookReturn')->name('book-returns.update');
        Route::delete('book-returns/{bookReturn}', [BookReturnController::class, 'destroy'])->whereNumber('bookReturn')->name('book-returns.destroy');
    });



    // teacher

    Route::prefix('institution')->name('institution.')->group(function () {
        Route::get('institutions', [InstitutionController::class, 'index'])->name('institutions.index');
        Route::post('institutions/list', [InstitutionController::class, 'listAjax'])->name('institutions.list.ajax');
        Route::get('institutions/create-modal', [InstitutionController::class, 'createModal'])->name('institutions.createModal');
        Route::get('institutions/upazilas-select2', [InstitutionController::class, 'upazilasSelect2'])->name('institutions.upazilas.select2');
        Route::get('institutions/select2', [InstitutionController::class, 'institutionsSelect2'])->name('institutions.select2');
        Route::post('institutions', [InstitutionController::class, 'store'])->name('institutions.store');
        Route::get('institutions/{institution}/edit-modal', [InstitutionController::class, 'editModal'])->whereNumber('institution')->name('institutions.editModal');
        Route::put('institutions/{institution}', [InstitutionController::class, 'update'])->whereNumber('institution')->name('institutions.update');
        Route::delete('institutions/{institution}', [InstitutionController::class, 'destroy'])->whereNumber('institution')->name('institutions.destroy');
    });
    Route::prefix('teacher')->name('teacher.')->group(function () {
        Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::post('teachers/list', [TeacherController::class, 'listAjax'])->name('teachers.list.ajax');
        Route::get('teachers/create-modal', [TeacherController::class, 'createModal'])->name('teachers.createModal');
        Route::get('teachers/institutions-select2', [TeacherController::class, 'institutionsSelect2'])->name('teachers.institutions.select2');
        Route::post('teachers', [TeacherController::class, 'store'])->name('teachers.store');
        Route::get('teachers/{teacher}/edit-modal', [TeacherController::class, 'editModal'])->whereNumber('teacher')->name('teachers.editModal');
        Route::put('teachers/{teacher}', [TeacherController::class, 'update'])->whereNumber('teacher')->name('teachers.update');
        Route::delete('teachers/{teacher}', [TeacherController::class, 'destroy'])->whereNumber('teacher')->name('teachers.destroy');
    });

    // Unit Management
    Route::prefix('units')->name('units.')->group(function () {

        Route::get('/', [UnitController::class, 'index'])->name('index');
        Route::post('/list', [UnitController::class, 'listAjax'])->name('list.ajax');
        Route::get('/create-modal', [UnitController::class, 'createModal'])->name('createModal');
        Route::get('/{unit}/edit-modal', [UnitController::class, 'editModal'])->whereNumber('unit')->name('editModal');
        Route::post('/', [UnitController::class, 'store'])->name('store');
        Route::put('/{unit}', [UnitController::class, 'update'])->whereNumber('unit')->name('update');
        Route::delete('/{unit}', [UnitController::class, 'destroy'])->whereNumber('unit')->name('destroy');
        Route::get('unit/select2/type', [UnitController::class, 'select2'])->name('select2');
    });

    //color management
    Route::prefix('color')->name('color.')->group(function () {

        Route::get('colors', [ColorController::class, 'index'])->name('colors.index');
        Route::get('colors/create-modal', [ColorController::class, 'createModal'])->name('colors.createModal');
        Route::post('colors/list', [ColorController::class, 'listAjax'])->name('colors.list.ajax');
        Route::post('colors', [ColorController::class, 'store'])->name('colors.store');
        Route::get('colors/edit-modal/{color}', [ColorController::class, 'editModal'])->whereNumber('color')->name('colors.editModal');
        Route::put('colors/{color}', [ColorController::class, 'update'])->name('colors.update');
        Route::get('colors/{color}', [ColorController::class, 'show'])->name('colors.show');
        Route::delete('colors/{color}', [ColorController::class, 'destroy'])->name('colors.destroy');
        Route::get('colors/select2/type', [ColorController::class, 'select2'])->name('select2');
    });

    // Size Management
    Route::prefix('sizes')->name('sizes.')->group(function () {

        Route::get('/', [SizeController::class, 'index'])->name('index');
        Route::post('/list', [SizeController::class, 'listAjax'])->name('list.ajax');
        Route::get('/create-modal', [SizeController::class, 'createModal'])->name('createModal');
        Route::get('/{size}/edit-modal', [SizeController::class, 'editModal'])->whereNumber('size')->name('editModal');
        Route::post('/', [SizeController::class, 'store'])->name('store');
        Route::put('/{size}', [SizeController::class, 'update'])->whereNumber('size')->name('update');
        Route::delete('/{size}', [SizeController::class, 'destroy'])->whereNumber('size')->name('destroy');
        Route::get('size/select2/type', [SizeController::class, 'select2'])->name('select2');
    });
    // paper quality Management
    Route::prefix('paper_quality')->name('paper_quality.')->group(function () {

        Route::get('/', [PaperQualityController::class, 'index'])->name('index');
        Route::post('paper_quality/list', [PaperQualityController::class, 'listAjax'])->name('list.ajax');
        Route::get('paper_quality/create-modal', [PaperQualityController::class, 'createModal'])->name('createModal');
        Route::get('paper_quality/{paperQuality}/edit-modal', [PaperQualityController::class, 'editModal'])->whereNumber('size')->name('editModal');
        Route::post('/paper_quality', [PaperQualityController::class, 'store'])->name('store');
        Route::put('paper_quality/{paperQuality}', [PaperQualityController::class, 'update'])->whereNumber('paperQuality')->name('update');
        Route::delete('paper_quality/{paperQuality}', [PaperQualityController::class, 'destroy'])->whereNumber('paperQuality')->name('destroy');
        Route::get('paper_quality/select2/type', [PaperQualityController::class, 'select2'])->name('select2');
    });

    // Product Management
    Route::prefix('product')->name('product.')->group(function () {

        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        // Route::get('product/view/{product}', [ProductController::class, 'index'])->name('products.view');
        Route::get('products/create', [ProductController::class, 'createModal'])->name('products.create');
        Route::post('products/list', [ProductController::class, 'listAjax'])->name('products.list.ajax');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::get('products/edit-modal/{product}', [ProductController::class, 'editModal'])->whereNumber('product')->name('products.editModal');
        Route::post('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::get('products/category', [ProductController::class, 'select2Category'])->name('products.category');
        Route::get('products/subcategory', [ProductController::class, 'select2Subcategory'])->name('products.subcategory');
        Route::get('products/brand', [ProductController::class, 'select2Brand'])->name('products.brand');
        Route::get('products/color', [ProductController::class, 'select2Color'])->name('products.color');
        Route::get('products/size', [ProductController::class, 'select2Size'])->name('products.size');
        Route::get('products/product-type', [ProductController::class, 'select2ProductType'])->name('products.product-type');
        Route::get('products/select2/all', [ProductController::class, 'select2'])->name('select2');
        // Parent list
        Route::get('/products/parents/get', [ProductController::class, 'parentsIndex'])->name('parents.index');
        Route::get('/products/parents/select2', [ProductController::class, 'parentsSelect2'])->name('parents.select2');
        Route::get('/products/{product}/variants', [ProductController::class, 'variants'])->name('variants');
        Route::get('products/import_csv/file', [ProductController::class, 'importCsvModal'])->name('import_csv');
        Route::post('products/handle/upload_csv', [ProductController::class, 'importCsv'])->name('handle_csv');

        Route::get('products/product-list/allProducts', [ProductController::class, 'productList'])->name('productsList');
        Route::get('products/childProductList/{product}', [ProductController::class, 'childProductList'])->name('childProductList');
        Route::get('products/product-search/{name}', [ProductController::class, 'productSearch'])->name('productsSearch');
        Route::get('products/product-byCategory/{category}', [ProductController::class, 'productByCategory'])->name('productsByCategory');
        Route::get('products/barcode/index', [ProductController::class, 'barcodeIndex'])->name('products.barcode');
        Route::get('products/{product}/barcode-preview', [ProductController::class, 'barcodePreview'])->name('barcode.preview');
    });

    // Product type Management
    Route::prefix('product-type')->name('product-type.')->group(function () {

        Route::get('product-types', [ProductTypeController::class, 'index'])->name('product-types.index');
        Route::get('product-types/create-modal', [ProductTypeController::class, 'createModal'])->name('product-types.createModal');
        Route::post('product-types/list', [ProductTypeController::class, 'listAjax'])->name('product-types.list.ajax');
        Route::post('product-types', [ProductTypeController::class, 'store'])->name('product-types.store');
        Route::get('product-types/edit-modal/{productType}', [ProductTypeController::class, 'editModal'])->whereNumber('productType')->name('product-types.editModal');
        Route::put('product-types/{productType}', [ProductTypeController::class, 'update'])->name('product-types.update');
        Route::get('product-types/{productType}', [ProductTypeController::class, 'show'])->name('product-types.show');
        Route::delete('product-types/{productType}', [ProductTypeController::class, 'destroy'])->name('product-types.destroy');
        Route::get('product-types/select2/type', [ProductTypeController::class, 'select2'])->name('select2');
    });

    // Company Setting Routes
    Route::prefix('website-setting')->name('website-setting.')->group(function () {
        Route::get('website-settings', [App\Http\Controllers\backend\WebsiteSettingController::class, 'index'])->name('website-settings.index');
        Route::post('website-settings/list', [App\Http\Controllers\backend\WebsiteSettingController::class, 'listAjax'])->name('website-settings.list.ajax');
        Route::get('website-settings/create', [App\Http\Controllers\backend\WebsiteSettingController::class, 'create'])->name('website-settings.create');
        Route::post('website-settings', [App\Http\Controllers\backend\WebsiteSettingController::class, 'store'])->name('website-settings.store');
        Route::get('website-settings/{setting}/edit', [App\Http\Controllers\backend\WebsiteSettingController::class, 'edit'])->name('website-settings.edit');
        Route::put('website-settings/{setting}', [App\Http\Controllers\backend\WebsiteSettingController::class, 'update'])->name('website-settings.update');
        Route::delete('website-settings/{setting}', [App\Http\Controllers\backend\WebsiteSettingController::class, 'destroy'])->name('website-settings.destroy');
    });
    // Branch Account Routes
    Route::prefix('branch-accounts')->name('branch-accounts.')->group(function () {

        Route::get('/', [BranchAccountController::class, 'index'])->name('index');
        Route::post('branch-accounts/assign', [BranchAccountController::class, 'assign'])->name('assign');
        Route::get('/branch-accounts/{branch}/accounts', [BranchAccountController::class, 'assignedAccounts'])->name('assigned');
    });


    // Expense Category Management
    Route::prefix('expenseCategories')->name('expenseCategories.')->group(function () {

        Route::get('expenseCategories', [ExpenseCategoryController::class, 'index'])->name('index');
        Route::get('expenseCategories/create', [ExpenseCategoryController::class, 'createModal'])->name('create');
        Route::post('expenseCategories/list', [ExpenseCategoryController::class, 'listAjax'])->name('list.ajax');
        Route::post('expenseCategories', [ExpenseCategoryController::class, 'store'])->name('store');
        Route::get('expenseCategories/edit/{expenseCategory}', [ExpenseCategoryController::class, 'editModal'])->whereNumber('expenseCategory')->name('edit');
        Route::put('expenseCategories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('update');
        Route::get('expenseCategories/{expenseCategory}', [ExpenseCategoryController::class, 'show'])->name('show');
        Route::delete('expenseCategories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('destroy');
        Route::get('expenseCategories/select2/type', [ExpenseCategoryController::class, 'select2'])->name('select2');
    });

    // Expense Management
    Route::prefix('expenses')->name('expenses.')->group(function () {

        Route::get('expense', [ExpenseController::class, 'index'])->name('index');
        Route::get('expense/create', [ExpenseController::class, 'createModal'])->name('createModal');
        Route::post('expense/list', [ExpenseController::class, 'listAjax'])->name('list.ajax');
        Route::post('expense', [ExpenseController::class, 'store'])->name('store');
        Route::get('expense/edit/{expense}', [ExpenseController::class, 'editModal'])->whereNumber('expense')->name('editModal');
        Route::put('expense/{expense}', [ExpenseController::class, 'update'])->name('update');
        Route::post('expense/{expense}/toggle-status', [ExpenseController::class, 'toggleStatus'])->whereNumber('expense')->name('toggleStatus');
        Route::get('expense/{expense}', [ExpenseController::class, 'show'])->name('show');
        Route::delete('expense/{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
        Route::get('expense/select2/type', [ExpenseController::class, 'select2'])->name('select2');
        Route::get('expenses/{expense}/invoice',[ExpenseController::class, 'invoice'])->name('invoice');
    });

    // Spot Sale Management
    Route::prefix('spot-sales')->name('spot-sales.')->group(function () {

        Route::get('spot-sale', [SpotSaleController::class, 'index'])->name('index');
        Route::get('spot-sale/create', [SpotSaleController::class, 'createModal'])->name('createModal');
        Route::post('spot-sale/list', [SpotSaleController::class, 'listAjax'])->name('list.ajax');
        Route::post('spot-sale', [SpotSaleController::class, 'store'])->name('store');
        Route::get('spot-sale/edit/{spotSale}', [SpotSaleController::class, 'editModal'])->whereNumber('spotSale')->name('editModal');
        Route::put('spot-sale/{spotSale}', [SpotSaleController::class, 'update'])->whereNumber('spotSale')->name('update');
        Route::post('spot-sale/{spotSale}/toggle-status', [SpotSaleController::class, 'toggleStatus'])->whereNumber('spotSale')->name('toggleStatus');
        Route::get('spot-sale/{spotSale}', [SpotSaleController::class, 'show'])->whereNumber('spotSale')->name('show');
        Route::delete('spot-sale/{spotSale}', [SpotSaleController::class, 'destroy'])->whereNumber('spotSale')->name('destroy');
        Route::get('product-price/{product}', [SpotSaleController::class, 'productPrice'])->whereNumber('product')->name('productPrice');
    });
});
