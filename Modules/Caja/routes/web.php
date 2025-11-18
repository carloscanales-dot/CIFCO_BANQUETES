<?php

use Illuminate\Support\Facades\Route;
use Modules\Caja\Http\Controllers\CajaController;
use Modules\Caja\Http\Controllers\PaymentTerminalController;
use Modules\Caja\Http\Controllers\PaymentTerminalSessionController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Rutas principales de caja
    Route::resource('cajas', CajaController::class)->names('caja');

    // Rutas CRUD para Payment Terminal
    Route::prefix('payment-terminals')->name('payment-terminals.')->group(function () {
        Route::get('/', [PaymentTerminalController::class, 'index'])->name('index');
        Route::post('/', [PaymentTerminalController::class, 'store'])->name('store');
        Route::put('/{paymentTerminal}', [PaymentTerminalController::class, 'update'])->name('update');
        Route::delete('/{paymentTerminal}', [PaymentTerminalController::class, 'destroy'])->name('destroy');
        Route::post('/{paymentTerminal}/toggle-status', [PaymentTerminalController::class, 'toggleStatus'])->name('toggle-status');
    });

    // 🔹 Rutas para Aperturas y Cierres de Terminales
    Route::prefix('terminal-sessions')->name('terminal-sessions.')->group(function () {
        // Vista principal (Inertia)
        Route::get('/', [PaymentTerminalSessionController::class, 'index'])->name('index');

        // Crear apertura (Axios)
        Route::post('/open', [PaymentTerminalSessionController::class, 'open'])->name('open');

        // Registrar cierre (Axios)
        Route::post('/{id}/close', [PaymentTerminalSessionController::class, 'close'])->name('close');

        // Exportar (generar y devolver) PDF del cierre por closingId
        Route::get('/closing/{closingId}/export', [PaymentTerminalSessionController::class, 'exportClosing'])
            ->name('closing.export');
    });
});
