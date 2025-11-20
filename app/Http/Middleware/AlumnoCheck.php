<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AlumnoCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Verificar si hay un usuario autenticado
        if (!Auth::check()) {
            // Si no hay sesión, redirigir al login
            return redirect('/login');
        }

        // 2. Verificar si el usuario tiene el rol de 'alumno' usando el modelo User
        $user = Auth::user();
        if (Auth::user()->isAlumno()) {
            // Si es 'alumno', permitir que continúe la solicitud
            return $next($request);
        }

        // 3. Si está autenticado pero NO es 'alumno', redirigir con un error 403 (Prohibido)
        abort(403, 'Acceso denegado. Se requiere rol de Alumno.');
    }
}