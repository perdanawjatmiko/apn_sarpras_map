<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\KoperasiController;
use App\Http\Controllers\Admin\KoperasiSarprasController;
use App\Http\Controllers\Admin\SarprasController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\KoperasiDetailController;
use App\Http\Controllers\PublicMapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicMapController::class, 'index'])->name('home');
Route::get('/koperasis/{koperasi}', KoperasiDetailController::class)->name('koperasis.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminController::class)->name('index');

        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('koperasis/template', [KoperasiController::class, 'template'])->name('koperasis.template');
        Route::post('koperasis/import', [KoperasiController::class, 'import'])->name('koperasis.import');
        Route::resource('koperasis', KoperasiController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('sarprases/import', [SarprasController::class, 'import'])->name('sarprases.import');
        Route::resource('sarprases', SarprasController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('koperasi-sarprases/template.csv', [KoperasiSarprasController::class, 'templateCsv'])->name('koperasi-sarprases.template.csv');
        Route::get('koperasi-sarprases/template.xlsx', [KoperasiSarprasController::class, 'templateXlsx'])->name('koperasi-sarprases.template.xlsx');
        Route::post('koperasi-sarprases/import', [KoperasiSarprasController::class, 'import'])->name('koperasi-sarprases.import');
        Route::resource('koperasi-sarprases', KoperasiSarprasController::class)
            ->parameters(['koperasi-sarprases' => 'koperasiSarpras'])
            ->only(['index', 'store', 'update', 'destroy']);
    });
});

require __DIR__.'/settings.php';
