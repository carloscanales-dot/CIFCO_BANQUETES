<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\UserController;
/*use Modules\Admin\Http\Controllers\AdminController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('admins', AdminController::class)->names('admin');
});*/

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::controller(UserController::class)->group(function () {
        Route::get('/user/list', 'list')->name('user.list');
    });
});
