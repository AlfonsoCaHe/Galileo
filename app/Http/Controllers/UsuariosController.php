<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Rules\ValidarTexto;
use App\Rules\ValidarEmail;
use Illuminate\Validation\Rule;

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
     * Procesa el intento de login y establece el contexto de la base de datos.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // 1. Validamos las credenciales
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            //Cargamos el usuario logueado y sus datos de sesión
            $user = Auth::user();

            if ($user->isAdmin()) {//Si es admin no hay que buscar su conexión
                Session::forget('DB_PROJECT_ID'); // Aseguramos que no haya un contexto residual de la sesión
                return redirect()->intended(route('admin.panel'));
            }

            // 2. Cargamos el perfil específico (rolable)
            $rolable = $user->rolable;

            // 3. Verificamos si el perfil está asociado a una ID de base de datos
            // Esta ID será que usaremos para cambiar la conexión.
            if ($rolable && property_exists($rolable, 'id_base_de_datos')) {
                // Almacenamos el ID de la base de datos en la sesión para no tener que buscarlo cada vez que realicemos una acción
                Session::put('DB_PROJECT_ID', $rolable->id_base_de_datos);
            } else {
                // Si el perfil no tiene id_base_de_datos (ej: Profesor sin proyecto asignado) eliminamos la sesión
                Session::forget('DB_PROJECT_ID');
            }

            return redirect()->intended(route('home'));

        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Session::forget('DB_PROJECT_ID');//Borramos el ID de la base de datos de conexión al cerrar la sesión

        return redirect('/');
    }

    /**
     * Redirige a la página de creación de usuario
     */
    public function create(){
        return view('usuarios.create');
    }

    /**
     * Almacena los datos de un nuevo usuario en la base de datos
     */
    public function store(Request $request){
        $datos = $request->validate([
            'name' => ['required', 'max:255', new ValidarTexto],
            'email' => ['required', 'max:255', new ValidarEmail],
            'password' => 'required',
            'rol' => ['required', 'string', Rule::in(['admin', 'alumno', 'profesor', 'tutor_laboral'])],
            'rolableid',
            'rolable_type'
        ]);
        
        User::create($datos);

        return redirect()->route('usuarios.show')->with('success', 'Nuevo usuario creado correctamente.');
    }

    /**
     * Borrar usuario
     */
    public function eliminarUsuario(Request $request) {

        $user_id = User::find($request->id);
        $user_id->delete();

        return response()->json(['success'=>true]);
    }
}