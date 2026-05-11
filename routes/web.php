<?php

use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinanceController as AdminFinanceController;
use App\Http\Controllers\Admin\FooterSettingsController as AdminFooterSettingsController;
use App\Http\Controllers\Admin\HomeSectionController as AdminHomeSectionController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportHubController as AdminReportHubController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\PosController as AdminPosController;
use App\Http\Controllers\Admin\UserPermissionController as AdminUserPermissionController;
use App\Http\Controllers\Admin\Accounting\AccountingController as AdminAccountingController;
use App\Http\Controllers\Admin\Accounting\AccountController as AdminAccountingAccountController;
use App\Http\Controllers\Admin\Accounting\ContactController as AdminAccountingContactController;
use App\Http\Controllers\Admin\Accounting\SalesInvoiceController as AdminSalesInvoiceController;
use App\Http\Controllers\Admin\Accounting\PurchaseInvoiceController as AdminPurchaseInvoiceController;
use App\Http\Controllers\Admin\Accounting\JournalEntryController as AdminJournalEntryController;
use App\Http\Controllers\Admin\Accounting\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\Accounting\ReportController as AdminAccountingReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\HomeController;
use App\Http\Controllers\Store\PageController as StorePageController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('store.home');
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware('auth')->name('dashboard');

Route::get('/products', [CategoryController::class, 'all'])->name('store.products.index');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('store.category.show');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('store.product.show');
Route::get('/search', [SearchController::class, 'index'])->name('store.search');
Route::get('/pages/{slug}', [StorePageController::class, 'show'])->name('store.pages.show');

Route::get('/cart', [CartController::class, 'index'])->name('store.cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('store.cart.add');
Route::patch('/cart/item/{rowId}', [CartController::class, 'update'])->name('store.cart.update');
Route::delete('/cart/item/{rowId}', [CartController::class, 'remove'])->name('store.cart.remove');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('store.cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('store.cart.coupon.remove');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('store.checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('store.checkout.store');
Route::view('/order-success', 'store.order-success')->name('store.order.success');

Route::middleware(['auth', 'admin.access'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('dashboard/home-settings', [DashboardController::class, 'updateHomeSettings'])->name('dashboard.home-settings');

    Route::resource('categories', AdminCategoryController::class)->except('show');

    Route::get('products-trash', [AdminProductController::class, 'trash'])->name('products.trash');
    Route::get('products/export', [AdminProductController::class, 'exportCsv'])->name('products.export');
    Route::post('products/import', [AdminProductController::class, 'importCsv'])->name('products.import');
    Route::post('products/refresh-real-images', [AdminProductController::class, 'refreshRealImages'])->name('products.refresh-real-images');
    Route::delete('products/bulk-delete', [AdminProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
    Route::delete('products/delete-all', [AdminProductController::class, 'destroyAll'])->name('products.destroy-all');
    Route::get('products/barcodes/labels', [AdminProductController::class, 'labelsForm'])->name('products.labels');
    Route::post('products/barcodes/labels/print', [AdminProductController::class, 'labelsPrint'])->name('products.labels.print');
    Route::get('products/{product}/barcode', [AdminProductController::class, 'barcode'])->name('products.barcode');
    Route::patch('products/{id}/restore', [AdminProductController::class, 'restore'])->name('products.restore');
    Route::delete('products/{id}/force', [AdminProductController::class, 'forceDelete'])->name('products.forceDelete');
    Route::resource('products', AdminProductController::class)->except('show');
    Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/export', [AdminCustomerController::class, 'exportCsv'])->name('customers.export');
    Route::post('customers/import', [AdminCustomerController::class, 'importCsv'])->name('customers.import');

    Route::resource('banners', AdminBannerController::class)->except('show');
    Route::resource('pages', AdminPageController::class)->except('show');
    Route::get('footer', [AdminFooterSettingsController::class, 'edit'])->name('footer.edit');
    Route::put('footer', [AdminFooterSettingsController::class, 'update'])->name('footer.update');
    Route::patch('banners/settings/autoplay', [AdminBannerController::class, 'updateSliderAutoplay'])->name('banners.autoplay');
    Route::get('home-sections', [AdminHomeSectionController::class, 'index'])->name('home-sections.index');
    Route::get('home-sections/{homeSection}/edit', [AdminHomeSectionController::class, 'edit'])->name('home-sections.edit');
    Route::put('home-sections/{homeSection}', [AdminHomeSectionController::class, 'update'])->name('home-sections.update');
    Route::patch('home-sections/order', [AdminHomeSectionController::class, 'updateOrder'])->name('home-sections.order');
    Route::patch('home-sections/{homeSection}/items', [AdminHomeSectionController::class, 'updateItems'])->name('home-sections.items');
    Route::post('home-sections/{homeSection}/brands', [AdminHomeSectionController::class, 'updateBrands'])->name('home-sections.brands');

    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::get('finance', [AdminFinanceController::class, 'index'])->name('finance.index');
    Route::get('finance/export', [AdminFinanceController::class, 'export'])->name('finance.export');
    Route::get('reports', AdminReportHubController::class)->name('reports.index');

    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/', [AdminAccountingController::class, 'index'])->name('index');

        Route::get('accounts', [AdminAccountingAccountController::class, 'index'])->name('accounts.index');
        Route::post('accounts', [AdminAccountingAccountController::class, 'store'])->name('accounts.store');

        Route::get('contacts', [AdminAccountingContactController::class, 'index'])->name('contacts.index');
        Route::post('contacts', [AdminAccountingContactController::class, 'store'])->name('contacts.store');

        Route::get('sales', [AdminSalesInvoiceController::class, 'index'])->name('sales.index');
        Route::get('sales/create', [AdminSalesInvoiceController::class, 'create'])->name('sales.create');
        Route::post('sales', [AdminSalesInvoiceController::class, 'store'])->name('sales.store');

        Route::get('purchases', [AdminPurchaseInvoiceController::class, 'index'])->name('purchases.index');
        Route::get('purchases/create', [AdminPurchaseInvoiceController::class, 'create'])->name('purchases.create');
        Route::post('purchases', [AdminPurchaseInvoiceController::class, 'store'])->name('purchases.store');

        Route::get('journal', [AdminJournalEntryController::class, 'index'])->name('journal.index');
        Route::get('journal/create', [AdminJournalEntryController::class, 'create'])->name('journal.create');
        Route::post('journal', [AdminJournalEntryController::class, 'store'])->name('journal.store');

        Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/create', [AdminPaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [AdminPaymentController::class, 'store'])->name('payments.store');

        Route::get('reports/ledger', [AdminAccountingReportController::class, 'ledger'])->name('reports.ledger');
        Route::get('reports/ledger/excel', [AdminAccountingReportController::class, 'ledgerExcel'])->name('reports.ledger.excel');
        Route::get('reports/ledger/pdf', [AdminAccountingReportController::class, 'ledgerPdf'])->name('reports.ledger.pdf');
        Route::get('reports/trial-balance', [AdminAccountingReportController::class, 'trialBalance'])->name('reports.trial-balance');
        Route::get('reports/trial-balance/excel', [AdminAccountingReportController::class, 'trialBalanceExcel'])->name('reports.trial-balance.excel');
        Route::get('reports/trial-balance/pdf', [AdminAccountingReportController::class, 'trialBalancePdf'])->name('reports.trial-balance.pdf');
        Route::get('reports/income-statement', [AdminAccountingReportController::class, 'incomeStatement'])->name('reports.income-statement');
        Route::get('reports/income-statement/excel', [AdminAccountingReportController::class, 'incomeStatementExcel'])->name('reports.income-statement.excel');
        Route::get('reports/income-statement/pdf', [AdminAccountingReportController::class, 'incomeStatementPdf'])->name('reports.income-statement.pdf');
        Route::get('reports/balance-sheet', [AdminAccountingReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::get('reports/balance-sheet/excel', [AdminAccountingReportController::class, 'balanceSheetExcel'])->name('reports.balance-sheet.excel');
        Route::get('reports/balance-sheet/pdf', [AdminAccountingReportController::class, 'balanceSheetPdf'])->name('reports.balance-sheet.pdf');
        Route::get('reports/cash-flow', [AdminAccountingReportController::class, 'cashFlow'])->name('reports.cash-flow');
        Route::get('reports/cash-flow/excel', [AdminAccountingReportController::class, 'cashFlowExcel'])->name('reports.cash-flow.excel');
        Route::get('reports/cash-flow/pdf', [AdminAccountingReportController::class, 'cashFlowPdf'])->name('reports.cash-flow.pdf');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [AdminInventoryController::class, 'index'])->name('index')->middleware('permission:inventory.view');
        Route::post('sync-products', [AdminInventoryController::class, 'syncProducts'])->name('sync-products')->middleware('permission:inventory.adjust');
        Route::get('export/overview', [AdminInventoryController::class, 'exportOverview'])->name('export.overview')->middleware('permission:inventory.view');
        Route::get('stocks', [AdminInventoryController::class, 'stocks'])->name('stocks')->middleware('permission:inventory.view');
        Route::get('movements', [AdminInventoryController::class, 'movements'])->name('movements')->middleware('permission:inventory.view');
        Route::get('warehouses', [AdminInventoryController::class, 'warehouses'])->name('warehouses')->middleware('permission:inventory.warehouse.manage');
        Route::post('warehouses', [AdminInventoryController::class, 'storeWarehouse'])->name('warehouses.store')->middleware('permission:inventory.warehouse.manage');
        Route::get('transfer', [AdminInventoryController::class, 'transferForm'])->name('transfer.form')->middleware('permission:inventory.transfer');
        Route::post('transfer', [AdminInventoryController::class, 'transfer'])->name('transfer.store')->middleware('permission:inventory.transfer');
        Route::get('adjustment', [AdminInventoryController::class, 'adjustmentForm'])->name('adjustment.form')->middleware('permission:inventory.adjust');
        Route::post('adjustment', [AdminInventoryController::class, 'adjustment'])->name('adjustment.store')->middleware('permission:inventory.adjust');
        Route::get('receive', [AdminInventoryController::class, 'receiveForm'])->name('receive.form')->middleware('permission:inventory.receive');
        Route::post('receive', [AdminInventoryController::class, 'receive'])->name('receive.store')->middleware('permission:inventory.receive');
        Route::get('issue', [AdminInventoryController::class, 'issueForm'])->name('issue.form')->middleware('permission:inventory.issue');
        Route::post('issue', [AdminInventoryController::class, 'issue'])->name('issue.store')->middleware('permission:inventory.issue');
        Route::get('alerts', [AdminInventoryController::class, 'alerts'])->name('alerts')->middleware('permission:inventory.alerts');
        Route::get('stock-card', [AdminInventoryController::class, 'stockCard'])->name('stock-card')->middleware('permission:inventory.stock_card');
        Route::get('counts', [AdminInventoryController::class, 'counts'])->name('counts.index')->middleware('permission:inventory.count');
        Route::get('counts/create', [AdminInventoryController::class, 'createCount'])->name('counts.create')->middleware('permission:inventory.count');
        Route::post('counts', [AdminInventoryController::class, 'storeCount'])->name('counts.store')->middleware('permission:inventory.count');
        Route::get('counts/{count}', [AdminInventoryController::class, 'showCount'])->name('counts.show')->middleware('permission:inventory.count');
        Route::post('counts/{count}/items', [AdminInventoryController::class, 'updateCountItems'])->name('counts.items.update')->middleware('permission:inventory.count');
        Route::post('counts/{count}/post', [AdminInventoryController::class, 'postCount'])->name('counts.post')->middleware('permission:inventory.count.post');
        Route::get('movements/{movement}/pdf', [AdminInventoryController::class, 'movementPdf'])->name('movements.pdf')->middleware('permission:inventory.pdf');
        Route::get('counts/{count}/pdf', [AdminInventoryController::class, 'countPdf'])->name('counts.pdf')->middleware('permission:inventory.pdf');
    });

    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [AdminPosController::class, 'index'])->name('index')->middleware('permission:pos.sell');
        Route::post('/', [AdminPosController::class, 'store'])->name('store')->middleware('permission:pos.sell');
        Route::get('history', [AdminPosController::class, 'history'])->name('history')->middleware('permission:pos.history');
        Route::get('{sale}', [AdminPosController::class, 'show'])->name('show')->middleware('permission:pos.history');
        Route::get('{sale}/receipt', [AdminPosController::class, 'receipt'])->name('receipt')->middleware('permission:pos.history');
    });

    Route::get('users/permissions', [AdminUserPermissionController::class, 'index'])->name('users.permissions.index');
    Route::post('users/permissions', [AdminUserPermissionController::class, 'update'])->name('users.permissions.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'], true)) {
        session(['locale' => $locale]);
    }

    return back();
})->name('locale.switch');

require __DIR__.'/auth.php';

