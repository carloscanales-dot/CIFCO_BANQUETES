<?php

use Illuminate\Support\Facades\Route;
use Modules\Alimentacion\Http\Controllers\AlimentacionController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('alimentacions', AlimentacionController::class)->names('alimentacion');
});
