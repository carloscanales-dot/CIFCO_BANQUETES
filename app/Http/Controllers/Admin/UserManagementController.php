<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Mail\PasswordResetNotification;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get();
        $roles = Role::all(['id', 'name']);

        return Inertia::render('Admin/UserManagement', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $user)
    {
        // 1. Validar los datos que llegan del formulario
        $validatedData = $request->validate([
            'telefono' => ['nullable', 'string', 'max:20'],
            'roles' => ['present', 'array'], // 'present' permite que el array esté vacío
            'roles.*' => ['exists:roles,id'] // Asegura que cada ID de rol exista en la tabla 'roles'
        ]);

        // 2. Actualizar el teléfono del usuario
        $user->telefono = $validatedData['telefono'];
        $user->save();

        // 3. Sincronizar los roles usando el método de Spatie
        // syncRoles() se encarga de añadir/quitar los roles necesarios.
        // Si el array 'roles' está vacío, syncRoles() quitará todos los roles al usuario.
        $user->syncRoles($validatedData['roles'] ?? []);

        // 4. Redirigir de vuelta a la página de administración de usuarios
        // Inertia se encargará de recargar los datos actualizados.
        return Redirect::back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function store(Request $request)
    {
        // 1. Validar los datos del nuevo usuario
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'area_id' => ['required', 'exists:areas,id'],
            'cargo_id' => ['required', 'exists:cargos,id'],
            'sexo' => ['required', 'string', 'in:M,F'],
            'fecha_nacimiento' => ['nullable', 'date'],
        ]);

        // 2. Crear el usuario
        // El modelo User ya se encarga de hashear la contraseña automáticamente
        // gracias a la propiedad 'casts' que definimos.
        User::create($validatedData);

        // 3. Redirigir con un mensaje de éxito
        // El usuario se creará sin roles. Se pueden asignar después editándolo.
        return Redirect::back()->with('success', 'Usuario creado correctamente.');
    }

    // public function resetPassword(Request $request, User $user)
    // {
    //     // 1. Generar una contraseña aleatoria y segura
    //     $newPassword = Str::random(10);

    //     // 2. Actualizar la contraseña del usuario en la base de datos
    //     $user->password = Hash::make($newPassword);
    //     $user->save();

    //     // 3. Enviar el correo electrónico al usuario con la nueva contraseña
    //     try {
    //         // Se envía el correo al email del usuario que se está editando.
    //         Mail::to($user->email)->send(new PasswordResetNotification($user, $newPassword));
    //     } catch (\Exception $e) {
    //         // Registra el error detallado en el log de Laravel para poder depurar.
    //         Log::error('Error al enviar correo de reseteo de contraseña: ' . $e->getMessage());

    //         // Devuelve un mensaje de error más informativo al usuario.
    //         return Redirect::back()->with('error', 'La contraseña fue reseteada, pero hubo un problema al enviar el correo.');
    //     }

    //     return Redirect::back()->with('success', 'Contraseña reseteada y enviada al usuario.');
    // }
}
