<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UsuariosController extends Controller
{
    /**
     * Redirige al usuario al panel correcto basado en su rol.
     * Este método se usa después del login y al acceder a la ruta '/home'.
     */
    public function redirectToPanel()
    {
        // 1. Obtener el usuario autenticado
        $user = Auth::user();

        // 2. Redireccionar en base al rol
        if ($user->isAdmin()) {
            return redirect()->route('admin.panel');
        }

        if ($user->isAlumno()) {
            // Asume que tienes una ruta con nombre 'alumno.panel'
            return redirect()->route('alumno.panel');
        }

        if ($user->isProfesor()) {
            // Asume que tienes una ruta con nombre 'profesor.panel'
            return redirect()->route('profesor.panel');
        }

        if ($user->isTutorLaboral()) {
            // Asume que tienes una ruta con nombre 'tutor.panel'
            return redirect()->route('tutor.panel');
        }

        // Si el rol no estuviera definido en la bd (debería ser imposible si el Enum funciona bien)
        // Redirigir a la raíz con un error.
        return redirect('/')->withErrors('No se pudo determinar tu rol. Contacta a soporte.');
    }

    /**
     * Muestra el formulario de login.
     */
    public function showLoginForm()
    {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (Auth::check()) {
            return redirect()->route('home');
        }
        
        return view('usuarios.login');
    }

    /**
     * Procesa la solicitud de login.
     */
    public function login(Request $request)
    {
        // 1. Validar los datos del formulario
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. Intentar autenticar al usuario
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            
            // Regenerar la sesión para prevenir ataques de fijación de sesión
            $request->session()->regenerate();

            // Redirige a red
            return redirect()->route('home');
        }

        // 3. Si la autenticación falla, lanzar una excepción de validación
        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}