<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrinterController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Aquí se registran las rutas de la aplicación. Todas las rutas dentro del
| grupo con middleware 'auth' requieren autenticación de usuario.
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

// Dashboard principal
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Página de inicio interna (solo usuarios verificados)
Route::get('/landing', [DashboardController::class, 'landing'])
    ->middleware(['auth', 'verified'])
    ->name('landing');

// CRUD de usuarios (sin create/edit/show/destroy ya que son manejados por Inertia)
Route::resource('users', UserManagementController::class)
    ->except(['create', 'edit', 'show', 'destroy']);

// Otras vistas
Route::get('/Administrar', [DashboardController::class, 'administrar'])
    ->middleware(['auth', 'verified'])
    ->name('administrar');

Route::get('/dashboard/charts', [DashboardController::class, 'charts']);
Route::get('/ventas', [DashboardController::class, 'ventas'])->name('ventas');
Route::get('/creditos', [DashboardController::class, 'creditos'])->name('creditos');

// Grupo de rutas protegidas (solo usuarios autenticados y verificados)
Route::middleware(['auth', 'verified'])->group(function () {

    // Administración de usuarios
    Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');

    // Reset de contraseña (administrador resetea a otro usuario)
    Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
        ->name('users.reset-password');
    // Vista del formulario de cambio de contraseña
    Route::get('/user/update-password', function () {
        return Inertia::render('Profile/UpdatePassword'); // 👈 Página Inertia que mostrarás
    })->name('user.update-password.form');

    // Acción que guarda la nueva contraseña
    Route::post('/user/update-password', [UserManagementController::class, 'updatePassword'])
        ->name('user.update-password');

    // Administración de impresoras
    Route::get('/admin/printers', [PrinterController::class, 'index'])->name('admin.printers');
    Route::post('/printers', [PrinterController::class, 'store'])->name('printers.store');
    Route::put('/printers/{printer}', [PrinterController::class, 'update'])->name('printers.update');
    Route::delete('/printers/{printer}', [PrinterController::class, 'destroy'])->name('printers.destroy');
});

require __DIR__ . '/auth.php';
