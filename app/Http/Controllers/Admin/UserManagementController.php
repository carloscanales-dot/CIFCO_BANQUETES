<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;


class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get();
        $roles = Role::all();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'roles' => 'required|array',

        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make('Dd12345@'), // Contraseña por defecto
        ]);

        // Asignar roles
        $user->syncRoles($request->roles);
        // 
        return redirect()->back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'roles' => 'required|array',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        $user->syncRoles($request->roles);
        return redirect()->back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function resetPassword(User $user)
    {
        // Generar contraseña aleatoria
        $newPassword = Str::random(10);

        // Guardarla encriptada en la base de datos
        $user->password = Hash::make($newPassword);
        $user->save();

        // Enviar correo con la nueva contraseña
        Mail::to($user->email)->send(new PasswordResetMail($user, $newPassword));

        // Mostrar mensaje de éxito
        return redirect()->back()->with('success', 'La contraseña fue reseteada y enviada al correo del usuario.');
    }

    public function updatePassword(Request $request)
    {
        // Validación
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Actualizar contraseña
        $user = $request->user();
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Cerrar sesión de forma segura
        Auth::logout(); // <-- método correcto en minúsculas

        // Invalidar sesión y regenerar token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirigir al login con mensaje flash
        return redirect()->route('login')->with('success', 'Tu contraseña fue cambiada correctamente. Inicia sesión con la nueva contraseña.');
    }
}
