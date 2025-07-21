<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('products', ProductController::class);
    Route::get('/stocks/create', [StockController::class, 'create'])->name('stocks.create');
    Route::post('/stocks/store', [StockController::class, 'store'])->name('stocks.store');

    Route::get('/stock-history', [StockHistoryController::class, 'index'])->name('stock-history.index');

    Route::get('/forecast', [\App\Http\Controllers\ForecastController::class, 'index'])->name('forecast.index');
    Route::post('/forecast/data', [\App\Http\Controllers\ForecastController::class, 'getData'])->name('forecast.data');

    Route::resource('sales', SaleController::class)
        ->only(['index','create','store','show']);

    Route::get('sales/{sale}/receipt', [SaleController::class, 'receipt'])
        ->name('sales.receipt');

});


require __DIR__.'/auth.php';
