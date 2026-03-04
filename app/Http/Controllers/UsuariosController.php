<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Rules\ValidarTexto;
use App\Models\User;
use App\Models\Profesor;
use App\Models\Alumno;
use Yajra\DataTables\Facades\DataTables;

class UsuariosController extends Controller
{
    /**
     * Redirige al usuario al panel correcto basado en su rol. Este método se usa después del login para acceder a la ruta '/panel' de cada rol.
     * @method bool isAdmin()
     * @method bool isAlumno()
     * @method bool isProfesor()
     * @method bool isTutorLaboral()
     */
    public function redirectToPanel()
    {
        // 1. Obtenemos el usuario autenticado
        $user = Auth::user();

        // 2. Redireccionamos en base al rol
        if ($user->isAdmin()) {
            return redirect()->route('admin.panel');
        }

        if ($user->isAlumno()) {
            // Asume que tienes una ruta con nombre 'alumno.panel'
            return redirect()->route('alumnos.panel');
        }

        if ($user->isProfesor()) {
            // Asume que tienes una ruta con nombre 'profesor.panel'
            return redirect()->route('profesores.panel');
        }

        if ($user->isTutorLaboral()) {
            // Asume que tienes una ruta con nombre 'tutor.panel'
            return redirect()->route('tutores_laborales.panel');
        }

        // Si el rol no estuviera definido en la bd redirigiría de nuevo a la raíz con un error.
        return redirect('/')->withErrors('Acceso no permitido. Contacta con el administrador.');
    }

    /**
     * Muestra el formulario de login.
     */
    public function showLoginForm()
    {
        // Si el usuario ya está autenticado, redirige a su panel
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
        // 1. Validamos los datos del formulario
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. Intentamos autenticar al usuario
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            
            // Si se autentica correctamente, regeneramos la sesión para prevenir ataques de fijación de sesión
            $request->session()->regenerate();

            // Redirigimos a la red
            return redirect()->route('home');
        }

        // 3. Si la autenticación falla, se lanza una excepción de validación
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

    /**
     * Método que redirige a la vista de gestión de usuarios
     */
    public function index(){
        return view('gestion.usuarios.index');
    }

    /**
     * Método que redirige a la vista de creación de profesores
     */
    public function createProfesor(){
        return view('gestion.profesor.crear');
    }

    /**
     * Método que inserta un nuevo usuario en la base de datos y su tabla de rol asociada.
     */
    public function store(Request $request){

        // 1. Validación de los datos
        $datos = $request->validate([
            'name' => ['required', new ValidarTexto],
            'email' => 'required|email|unique:users,email', // El email debe ser único
            'password' => 'required|min:8',
            'rol' => 'required|in:admin,alumno,profesor,tutor_laboral'
        ]);

        // 2. Iniciar la Transacción para las relaciones polimórficas
        DB::beginTransaction();

        try {
            // 3. Crear el Usuario (Tabla `users`)
            $user = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => $datos['password'],
                'rol' => $datos['rol'],
            ]);

            // 4. Añadimos a la tabla del Rol Específico
            if ($user->rol === 'profesor') {
                
                // 4a. Crear el registro en la tabla 'profesores'
                $profesor = Profesor::create([
                    'nombre' => $user->name, // Usamos el nombre del User para el Profesor
                ]);
                
                // 4b. Actualizamos el registro del User con el enlace polimórfico
                // Usamos la clave primaria del profesor ('id_profesor') y la clase del modelo.
                $user->update([
                    'rolable_id' => $profesor->id_profesor, 
                    'rolable_type' => Profesor::class, 
                ]);

            }
            // Añadimos a la tabla del Rol Específico
            if ($user->rol === 'alumno') {
                
                // a. Crear el registro en la tabla 'alumnos'
                $alumno = Alumno::create([
                    'nombre' => $user->name, // Usamos el nombre del User para el Alumno
                    'database_id' => $request->database_id,
                ]);
                
                // b. Actualizamos el registro del User con el enlace polimórfico
                // Usamos la clave primaria del profesor ('id_alumno') y la clase del modelo.
                $user->update([
                    'rolable_id' => $alumno->id_alumno, 
                    'rolable_type' => Alumno::class, 
                ]);

            }

            // 5. Confirmar la transacción
            DB::commit();

            return redirect()->route('usuarios.show')->with('success', 'Usuario y rol de ' . $user->rol . ' creados correctamente.');

        } catch (\Exception $e) {
            // 6. Revertir la transacción (si algo falla)
            DB::rollBack();
            
            return back()->withInput()->with('error', 'Error al crear el usuario y su rol. Inténtelo de nuevo. (Detalle: ' . $e->getMessage() . ')');
        }
    }

    /**
     * AJAX: Procesa la petición de DataTables de la tabla de gestión de usuarios
     */
    public function showDataTable(Request $request)
    {
        // Filtro: 'inactivos' muestra la papelera, cualquier otra cosa muestra los activos
        $verInactivos = $request->input('estado') === 'inactivos';

        if ($verInactivos) {
            $query = User::onlyTrashed(); 
        } else {
            $query = User::query(); 
        }

        // Generamos cada tupla para poder controlar qué aparece en ellas
        return DataTables::of($query)
            ->addColumn('estado', function ($usuario) {
                // Protegemos al admin de un borrado accidental no mostrando su switch
                if ($usuario->rol === 'admin') {
                    return '<span> </span>';
                }

                // Protección de auto-borrado: No puedes desactivarte a ti mismo, pensado por si en el futuro se añaden otros administradores
                if (Auth::id() === $usuario->id) {
                    return '<span class="badge bg-success">TU USUARIO</span>';
                }

                // Si está borrado con Soft Delete (trashed) el switch está apagado. Si no, encendido.
                $estaActivo = !$usuario->trashed();
                $checked = $estaActivo ? 'checked' : '';
                $texto = $estaActivo ? 'ACTIVO' : 'INACTIVO';
                $claseTexto = $estaActivo ? 'text-success' : 'text-danger';

                return '
                    <form action="'.route('gestion.usuarios.toggle', $usuario->id).'" method="POST">
                        '.csrf_field().'
                        '.method_field('PUT').'
                        <div class="form-check form-switch d-flex justify-content-center">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   role="switch" 
                                   style="cursor: pointer; transform: scale(1.2);" 
                                   onchange="this.form.submit()" 
                                   '.$checked.'>
                        </div>
                        <small class="fw-bold '.$claseTexto.'">'.$texto.'</small>
                    </form>
                ';
            })
            ->editColumn('rol', function($usuario) {
                // Formato visual de roles
                $colors = [
                    'admin' => 'danger',
                    'profesor' => 'primary',
                    'alumno' => 'info text-dark',
                    'tutor_laboral' => 'success'
                ];
                // Si por alguna razón el rol no coincide, usa el color 'secondary'
                $color = $colors[$usuario->rol] ?? 'secondary';
                
                // Retornamos el HTML
                return '<span class="badge bg-'.$color.'">'.strtoupper($usuario->rol).'</span>';
            })
            ->addColumn('acciones', function ($usuario) {
                return '
                    <form action="'.route('gestion.usuarios.edit', $usuario->id).'" method="GET" class="d-inline">
                        '.csrf_field().' 
                        <button type="submit" class="btn btn-sm btn-warning shadow-sm" title="Editar">
                            Editar
                        </button>
                    </form>';
            })
            ->rawColumns(['estado', 'rol', 'acciones'])
            ->make(true);
    }

    /**
     * Método Toggle: Si existe, lo borra (Soft Delete). Si está borrado, lo restaura.
     */
    public function toggleEstado($id)
    {
        // Buscamos incluso en la papelera para poder restaurar
        $usuario = User::withTrashed()->findOrFail($id);

        // 1. Protección Admin
        if ($usuario->rol === 'admin') {
            return redirect()->back()->withErrors('No puedes desactivar al Administrador principal.');
        }

        // 2. Protección Auto-desactivación
        if (Auth::id() === $usuario->id) {
            return redirect()->back()->withErrors('No puedes desactivar tu propia cuenta.');
        }

        try {
            if ($usuario->trashed()) {
                // Si estaba borrado -> Restauramos (Activar)
                $usuario->restore();
                $mensaje = "Usuario activado correctamente.";
            } else {
                // Si estaba activo -> Borramos (Desactivar)
                $usuario->delete();
                $mensaje = "Usuario desactivado correctamente.";
            }

            return redirect()->back()->with('success', $mensaje);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al cambiar estado: ' . $e->getMessage());
        }
    }

    /**
     * Realiza un Soft Delete del usuario.
     */
    public function destroy($id)
    {
        $usuario = User::findOrFail($id);

        // No se puede borrar al admin ni tampoco a uno mismo
        if ($usuario->rol === 'admin') {
            return response()->json(['error' => 'No está permitido eliminar al Administrador principal.'], 403);
        }
        if (Auth::id() === $usuario->id) {
            return response()->json(['error' => 'No puedes eliminar tu propia cuenta.'], 403);
        }

        try {
            $usuario->delete();
            return response()->json(['success' => 'Usuario enviado a la papelera correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Restaura un usuario eliminado.
     */
    public function restore($id)
    {
        try {
            $usuario = User::onlyTrashed()->findOrFail($id);
            // Función que deshace el Soft Delete
            $usuario->restore();
            return response()->json(['success' => 'Usuario restaurado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al restaurar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Muestra el formulario para editar un usuario.
     */
    public function edit($id)
    {
        // Buscar al usuario o fallar si no existe
        $usuario = User::findOrFail($id);
        
        return view('gestion.usuarios.edit', compact('usuario'));
    }

    /**
     * Procesa la actualización de un usuario.
     */
    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        // 1. Reglas de Validación
        $rules = [
            'name' => ['required', new ValidarTexto],
            'email' => 'required|email|unique:users,email,' . $id
        ];

        // 2. Agregar la regla para comprobar la contraseña si se proporciona una nueva
        if (!empty($request->password)) {
            $rules['password'] = 'nullable|min:8|confirmed';
        }

        $datos = $request->validate($rules);

        // 3. Prepara los datos para la actualización
        $updateData = [
            'name' => $datos['name'],
            'email' => $datos['email']
        ];

        // 4. Incluimos la contraseña solo si se proporcionó un valor nuevo
        if (!empty($request->password)) {
            $updateData['password'] = $datos['password']; 
        }

        $usuario->update($updateData);

        return redirect()->route('gestion.usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }
}