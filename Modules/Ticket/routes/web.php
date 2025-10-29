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
// Prefijo 'ticket' y autenticación
Route::middleware(['auth'])->prefix('ticket')->group(function () {

    // 🛡️ Solo Administrador
    Route::middleware('role:Administrador')->group(function () {
        Route::resource('/ticket', TicketController::class)->except(['show']);
    });

    // 🛡️ Administrador o Empleado
    Route::middleware('role:Administrador|Empleado')->group(function () {
        Route::resource('/fair', FairController::class)->except(['show']);
        Route::resource('/station', StationController::class)->except(['show']);
        Route::resource('/product', ProductController::class)->except(['show']);
        Route::get('/product/all', [ProductController::class, 'all'])->name('product.all');
        // Route::resource('/stationUser', StationUserController::class)->except(['show']);
        // Route::resource('/stationProduct', StationProductController::class)->except(['show']);
        Route::resource('/stationTicket', StationTicketController::class)->only(['index']);

          // Locations
        Route::get('/location/list/{fair}', [LocationController::class, 'list'])->name('location.list'); // Obtener locations por feria
        Route::get('/location/list-all', [LocationController::class, 'listAll'])->name('location.listAll'); // Obtener todas las locations
        Route::post('/location/store', [LocationController::class, 'store'])->name('location.store');      // Crear location
        Route::delete('/location/{location}', [LocationController::class, 'destroy'])->name('location.destroy'); // Eliminar location


        // Rutas de listas
        Route::get('/station/list/{status}', [StationController::class, 'list'])->name('station.list');
        Route::get('/station/list-by-fair/{fair}', [StationController::class, 'listByFair'])->name('station.listByFair');
        Route::get('/product/list/{status}', [ProductController::class, 'list'])->name('product.list');
        // Asignar múltiples productos a una estación
        // Asignar múltiples productos a una estación
        Route::post('/station/{station}/products', [StationProductController::class, 'store'])
            ->name('station.products.store');
        Route::get('/station/{station}/products', [StationProductController::class, 'getProducts'])
            ->name('station.products.get');


        // Exportar tickets de estación
        Route::get('/stationTicket/export', [StationTicketController::class, 'export'])->name('stationTicket.export');
    });

    // 🛡️ Todos los roles: Administrador, Empleado o Lector
    Route::middleware('role:Administrador|Empleado|Lector')->group(function () {
        Route::get('/reader/index', [ReaderController::class, 'index'])->name('reader.index');
        Route::post('/reader/store', [ReaderController::class, 'store'])->name('reader.store');
        Route::get('/reader/report', [ReaderController::class, 'ticketPdf'])->name('reader.ticketPdf');
        Route::get('/reader/ticket/{uuid}', [ReaderController::class, 'validateTicket'])->name('reader.validateTicket');
    });
});
