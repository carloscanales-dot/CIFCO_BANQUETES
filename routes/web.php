<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ReprintLogController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TransaccionController;
use App\Http\Controllers\ReporteEmpleadoController;
use App\Http\Controllers\TransaccionEmpleadoController;

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

Route::get('/dashboard/charts', [DashboardController::class, 'charts'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.charts');
// Datos para el módulo de Tickets (Dashboard)
Route::get('/dashboard/tickets-data', [\App\Http\Controllers\AnalyticsController::class, 'ticketsData'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.tickets.data');


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

    // Administración de empleados
    Route::get('/admin/employees', [EmployeeController::class, 'index'])->name('admin.employees');
    Route::post('/admin/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::put('/admin/employees/{id}', [EmployeeController::class, 'update'])->name('employees.update');

    // Administración de impresoras
    Route::get('/admin/printers', [PrinterController::class, 'index'])->name('admin.printers');
    Route::post('/printers', [PrinterController::class, 'store'])->name('printers.store');
    Route::put('/printers/{printer}', [PrinterController::class, 'update'])->name('printers.update');
    Route::delete('/printers/{printer}', [PrinterController::class, 'destroy'])->name('printers.destroy');

    // Historial de reimpresiones
    Route::get('/admin/reprint-logs', [ReprintLogController::class, 'index'])->name('admin.reprint-logs');

    // Reportes de Ventas Normales
    Route::get('/reportes/ventas', [ReporteController::class, 'index'])->name('reportes.ventas.index');
    Route::get('/api/reportes/data', [ReporteController::class, 'data'])->name('reportes.data');
    Route::get('/api/reportes/download/pdf', [ReporteController::class, 'downloadPDF'])->name('reportes.download.pdf');
    Route::get('/api/reportes/download/excel', [ReporteController::class, 'downloadExcel'])->name('reportes.download.excel');

    // Reportes de Empleados
    Route::get('/reportes/empleados', [ReporteEmpleadoController::class, 'index'])->name('reportes.empleados.index');
    Route::get('/api/reportes/empleados/data', [ReporteEmpleadoController::class, 'data'])->name('reportes.empleados.data');
    Route::get('/api/reportes/empleados/download/pdf', [ReporteEmpleadoController::class, 'downloadPDF'])->name('reportes.empleados.download.pdf');
    Route::get('/api/reportes/empleados/download/excel', [ReporteEmpleadoController::class, 'downloadExcel'])->name('reportes.empleados.download.excel');

    // Transacciones de Ventas Normales
    Route::get('/transacciones/ventas', [TransaccionController::class, 'index'])->name('transacciones.ventas.index');
    Route::get('/api/transacciones/list', [TransaccionController::class, 'list'])->name('transacciones.list');
    Route::get('/api/transacciones/{id}/details', [TransaccionController::class, 'details'])->name('transacciones.details');
    Route::put('/api/transacciones/{id}/cancel', [TransaccionController::class, 'cancel'])->name('transacciones.cancel');

    // Transacciones de Empleados
    Route::get('/transacciones/empleados', [TransaccionEmpleadoController::class, 'index'])->name('transacciones.empleados.index');
    Route::get('/api/transacciones/empleados/list', [TransaccionEmpleadoController::class, 'list'])->name('transacciones.empleados.list');
    Route::get('/api/transacciones/empleados/{id}/details', [TransaccionEmpleadoController::class, 'details'])->name('transacciones.empleados.details');
    Route::put('/api/transacciones/empleados/{id}/cancel', [TransaccionEmpleadoController::class, 'cancel'])->name('transacciones.empleados.cancel');
});

// Proxy HTTPS para impresoras Epson ePOS (evita contenido mixto en producción)
// Sin middleware de admin para permitir acceso desde cualquier usuario autenticado
Route::post('/printer-proxy', [PrinterController::class, 'proxyRequest'])
    ->middleware(['auth'])
    ->name('printer.proxy');

require __DIR__ . '/auth.php';
