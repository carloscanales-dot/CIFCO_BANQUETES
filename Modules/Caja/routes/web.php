<?php

use Illuminate\Support\Facades\Route;
use Modules\Caja\Http\Controllers\CajaController;
use Modules\Caja\Http\Controllers\PaymentTerminalController;
use Modules\Caja\Http\Controllers\PaymentTerminalSessionController;
use Modules\Caja\Http\Controllers\TransactionController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Rutas principales de caja
    Route::resource('cajas', CajaController::class)->names('caja');

    // Rutas CRUD para Payment Terminal
    Route::prefix('payment-terminals')->name('payment-terminals.')->group(function () {
        Route::get('/', [PaymentTerminalController::class, 'index'])->name('index');
        Route::post('/', [PaymentTerminalController::class, 'store'])->name('store');
        Route::put('/{paymentTerminal}', [PaymentTerminalController::class, 'update'])->name('update');
        Route::delete('/{paymentTerminal}', [PaymentTerminalController::class, 'destroy'])->name('destroy');
        Route::get('/{paymentTerminal}/status', [PaymentTerminalController::class, 'getStatus'])->name('status');
    });

    // 🔹 Rutas para Aperturas y Cierres de Terminales
    Route::prefix('terminal-sessions')->name('terminal-sessions.')->group(function () {
        // Vista principal (Inertia)
        Route::get('/', [PaymentTerminalSessionController::class, 'index'])->name('index');

        // Obtener la terminal actual por station_id
        Route::get('current', [PaymentTerminalSessionController::class, 'current'])->name('current');

        // Pre-cierre (crea registro en payment_terminal_closing con estado PRE_CIERRE)
        Route::post('{openingId}/preclose', [PaymentTerminalSessionController::class, 'preclose'])->name('preclose');

        // Crear apertura (Axios)
        Route::post('open', [PaymentTerminalSessionController::class, 'open'])->name('open');

        // Registrar cierre (Axios)
        Route::post('{openingId}/close', [PaymentTerminalSessionController::class, 'close'])->name('close');

        // Exportar (generar y devolver) PDF del cierre por closingId
        Route::get('closing/{closingId}/export', [PaymentTerminalSessionController::class, 'exportClosing'])
            ->name('closing.export');
    });

            // routes (dentro del group 'auth,verified')
        Route::get('/historial-ventas', [\Modules\Caja\Http\Controllers\TransactionController::class, 'index'])
            ->name('historial-ventas.index');

        Route::get('/historial-empleados', [\Modules\Caja\Http\Controllers\TransactionController::class, 'empleados'])
            ->name('historial-empleados.index');

        Route::post('/historial-ventas/{transaction}/refund', [\Modules\Caja\Http\Controllers\TransactionController::class, 'refund'])
            ->name('historial-ventas.refund');

        Route::post('/transactions/{transaction}/reprint', [\Modules\Caja\Http\Controllers\TransactionController::class, 'reprint'])
            ->name('transactions.reprint');

});
