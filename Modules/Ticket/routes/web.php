<?php

use Illuminate\Support\Facades\Route;
use Modules\Ticket\Http\Controllers\TicketController;
use Modules\Ticket\Http\Controllers\FairController;
use Modules\Ticket\Http\Controllers\StationController;
use Modules\Ticket\Http\Controllers\ProductController;
use Modules\Ticket\Http\Controllers\ReaderController;
use Modules\Ticket\Http\Controllers\StationProductController;
use Modules\Ticket\Http\Controllers\StationUserController;
use Modules\Ticket\Http\Controllers\StationTicketController;
use Modules\Ticket\Http\Controllers\LocationController;
use Modules\Ticket\Http\Controllers\StationSaleController;
use Modules\Caja\Http\Controllers\TransactionController;
use Modules\Ticket\Http\Controllers\EmployeeController;

// Prefijo 'ticket' y autenticación
Route::middleware(['auth'])->prefix('ticket')->group(function () {
    // Solo Administrador
    Route::middleware('role:Administrador')->group(function () {
        Route::resource('/ticket', TicketController::class)->except(['show']);
        // CRUD completo de locaciones
        Route::resource('/location', LocationController::class)->except(['show']);
    });
    // Administrador o Empleado
    Route::middleware('role:Administrador|Empleado')->group(function () {
        Route::resource('/fair', FairController::class)->except(['show']);
        Route::resource('/station', StationController::class)->except(['show']);
        Route::resource('/product', ProductController::class)->except(['show']);
        Route::get('/product/all', [ProductController::class, 'all'])->name('product.all');
        Route::resource('/stationTicket', StationTicketController::class)->only(['index']);
        Route::get('cajas/products', [StationSaleController::class, 'getProductsForUser'])->name('cajas.products');
        Route::get('/stations/my-products', [StationSaleController::class, 'getProductsForUser'])
        ->name('stations.my-products');
        Route::post('cajas/transactions/store', [TransactionController::class, 'store'])->name('transactions.store');
        // Empleados
        Route::get('/empleados/list', [EmployeeController::class, 'list'])->name('employees.list');
        // Rutas de listas y asignaciones
        Route::get('/station/list/{status}', [StationController::class, 'list'])->name('station.list');
        Route::get('/station/list-by-fair/{fair}', [StationController::class, 'listByFair'])->name('station.listByFair');
        Route::get('/product/list/{status}', [ProductController::class, 'list'])->name('product.list');
        Route::post('/station/{station}/products', [StationProductController::class, 'store'])->name('station.products.store');
        Route::get('/station/{station}/products', [StationProductController::class, 'getProducts'])->name('station.products.get');
        Route::post('/stations/{station_id}/users', [StationUserController::class, 'store']);
        Route::get('/stations/{station_id}/users', [StationUserController::class, 'assignedUsers']);
        // Exportar tickets
        Route::get('/stationTicket/export', [StationTicketController::class, 'export'])->name('stationTicket.export');
    });
    // Todos los roles: Administrador, Empleado o Lector
    Route::middleware('role:Administrador|Empleado|Lector')->group(function () {
        Route::get('/reader/index', [ReaderController::class, 'index'])->name('reader.index');
        Route::post('/reader/store', [ReaderController::class, 'store'])->name('reader.store');
        Route::get('/reader/report', [ReaderController::class, 'ticketPdf'])->name('reader.ticketPdf');
        Route::get('/reader/ticket/{uuid}', [ReaderController::class, 'validateTicket'])->name('reader.validateTicket');
    });
});
