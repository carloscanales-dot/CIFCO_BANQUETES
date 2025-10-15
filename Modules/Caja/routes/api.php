<?php

use Illuminate\Support\Facades\Route;
use Modules\Caja\Http\Controllers\CajaController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('cajas', CajaController::class)->names('caja');
});
