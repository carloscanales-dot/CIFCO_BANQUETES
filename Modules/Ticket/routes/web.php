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

Route::middleware('auth')->prefix('ticket')->group(function () {

    // 🛡️ Solo Administrador
    Route::middleware('role:Administrador')->group(function () {
        Route::resource('/ticket', TicketController::class)->except(['show']);
    });

    // 🛡️ Administrador y Empleado (módulos generales)
    Route::middleware('role:Administrador,Empleado')->group(function () {
        Route::resource('/fair', FairController::class)->except(['show']);
        Route::resource('/station', StationController::class)->except(['show']);
        Route::resource('/product', ProductController::class)->except(['show']);
        Route::resource('/stationUser', StationUserController::class)->except(['show']);
        Route::resource('/stationProduct', StationProductController::class)->except(['show']);
        Route::resource('/stationTicket', StationTicketController::class)->only(['index']);

        Route::controller(StationController::class)->group(function () {
            Route::get('/station/list/{status}', 'list')->name('station.list');
        });

        Route::controller(ProductController::class)->group(function () {
            Route::get('/product/list/{status}', 'list')->name('product.list');
        });

        Route::controller(StationTicketController::class)->group(function () {
            Route::get('/stationTicket/export', 'export')->name('stationTicket.export');
        });
    });

    // 🛡️ Todos los roles (Administrador, Empleado, Lector): acceso al lector
    Route::middleware('role:Administrador,Empleado,Lector')->group(function () {
        Route::controller(ReaderController::class)->group(function () {
            Route::get('/reader/index', 'index')->name('reader.index');
            Route::post('/reader/store', 'store')->name('reader.store');
            Route::get('/reader/report', 'ticketPdf')->name('reader.ticketPdf');
            Route::get('/reader/ticket/{uuid}', 'validateTicket')->name('reader.validateTicket');
        });
    });
});
