<?php

use App\Http\Controllers\PublicKoperasiDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/koperasi/statistics', [PublicKoperasiDataController::class, 'statistics'])->name('koperasi.statistics');
    Route::get('/koperasi/locations', [PublicKoperasiDataController::class, 'locations'])->name('koperasi.locations');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
