<?php

use Illuminate\Support\Facades\Route;
use Modules\Alimentacion\Http\Controllers\AlimentacionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('alimentacions', AlimentacionController::class)->names('alimentacion');
});
