<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/landing', [DashboardController::class, 'landing'])->middleware(['auth', 'verified'])->name('landing');
Route::get('/Administrar', [DashboardController::class, 'administrar'])->middleware(['auth', 'verified'])->name('administrar');
Route::get('/dashboard/charts', [DashboardController::class, 'charts']);
Route::get('/ventas', [DashboardController::class, 'ventas'])->name('ventas');
Route::get('/creditos', [DashboardController::class, 'creditos'])->name('creditos');


require __DIR__ . '/auth.php';
