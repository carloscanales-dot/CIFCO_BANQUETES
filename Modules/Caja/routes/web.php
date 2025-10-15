<?php

use Illuminate\Support\Facades\Route;
use Modules\Caja\Http\Controllers\CajaController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cajas', CajaController::class)->names('caja');
});
