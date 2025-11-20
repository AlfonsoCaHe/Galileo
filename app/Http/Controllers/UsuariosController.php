<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UsuariosController extends Controller
{
    /**
     * Muestra el formulario de login.
     */
    public function showLoginForm()
    {
        // Si el usuario ya está autenticado, redirigir al panel de inicio
        if (Auth::check()) {
            return redirect('/home'); // O a la ruta que prefieras para usuarios logueados
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

            // 3. Redirección basada en el rol (Usando los métodos helper con 'es')
            $user = Auth::user();

            // dd($user);

            if ($user->isAdmin()) { // Uso del nuevo método esAdmin()
                // Redirigir al panel de administrador
                return redirect()->intended(route('admin.panel'));
            } 
            
            // Si no es admin, redirigir a una página general o error 403 (ajustar según necesites)
            return redirect('/home')->with('status', '¡Bienvenido!'); 

            // NOTA: Más adelante, aquí harías las redirecciones específicas 
            // para 'alumno', 'profesor', y 'tutor_laboral'.
        }

        // 4. Si la autenticación falla, lanzar una excepción de validación
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

        return redirect('/login');
    }
}