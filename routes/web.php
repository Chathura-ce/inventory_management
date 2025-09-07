<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
//    return view('welcome');
    return view('auth.login');

});

//Route::get('/dashboard', function () {
//    return view('dashboard');
//})->middleware(['auth', 'verified'])->name('dashboard');



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('auth')
        ->name('dashboard');

    Route::resource('products', ProductController::class);
    Route::get('/stocks/create', [StockController::class, 'create'])->name('stocks.create');
    Route::post('/stocks/store', [StockController::class, 'store'])->name('stocks.store');

    Route::get('/stock-history', [StockHistoryController::class, 'index'])->name('stock-history.index');

    Route::get('/forecast', [\App\Http\Controllers\ForecastController::class, 'index'])->name('forecast.index');
    Route::post('/forecast/data', [\App\Http\Controllers\ForecastController::class, 'getData'])->name('forecast.data');
    Route::get('/forecast/accuracy', [\App\Http\Controllers\ForecastController::class, 'accuracy'])->name('forecast.accuracy');

    Route::resource('sales', SaleController::class)
        ->only(['index','create','store','show']);

    Route::get('sales/{sale}/receipt', [SaleController::class, 'receipt'])
        ->name('sales.receipt');
    Route::resource('sales', SaleController::class)
        ->only(['index','create','store','show']);


    Route::prefix('reports')
        ->group(function() {
            Route::get('/',                 [ReportController::class, 'index'])            ->name('reports.index');
            Route::get('sales-summary',     [ReportController::class, 'salesSummary'])     ->name('reports.salesSummary');
            Route::get('stock-balance',     [ReportController::class, 'stockBalance'])     ->name('reports.stockBalance');
            Route::get('stock-movement',    [ReportController::class, 'stockMovement'])    ->name('reports.stockMovement');
        });

    Route::get('notifications/unread',      [NotificationController::class,'unread'])    ->name('notifications.unread');

    Route::get('notifications',             [NotificationController::class,'index'])     ->name('notifications.index');
    Route::get('notifications/{id}',        [NotificationController::class,'show'])      ->name('notifications.show');
    Route::post('notifications/{id}/read',  [NotificationController::class,'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.readAll');
    Route::get('/forecast/diag', [\App\Http\Controllers\ForecastController::class, 'diag']);
    Route::get('/products/{product}/historical-prices', [\App\Http\Controllers\HistoricalPriceController::class, 'index'])
        ->name('historical-prices.index');

});


require __DIR__.'/auth.php';
