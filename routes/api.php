<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Ticket\Http\Controllers\ProductController;
use Modules\Ticket\Http\Controllers\StationController;
use Modules\Caja\Http\Controllers\AgentPrintJobController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);

    // Rutas para reportes
    Route::get('/ticket/products', [ProductController::class, 'index']);
    Route::get('/ticket/stations', [StationController::class, 'index']);
});

// ====== Print Agent API Routes ======
Route::prefix('agent')->group(function () {
    // Login del agente (sin middleware)
    Route::post('/login', [AgentPrintJobController::class, 'login']);

    // Rutas protegidas con middleware de agente
    Route::middleware(\Modules\Caja\Http\Middleware\AuthenticatePrintAgent::class)->group(function () {
        Route::get('/print-jobs/pending', [AgentPrintJobController::class, 'getPending']);
        Route::post('/print-jobs/{id}/printed', [AgentPrintJobController::class, 'markPrinted']);
        Route::post('/print-jobs/{id}/failed', [AgentPrintJobController::class, 'markFailed']);
    });
});
