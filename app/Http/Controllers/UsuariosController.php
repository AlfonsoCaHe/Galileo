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
use App\Models\TutorLaboral;

class UsuariosController extends Controller
{
    /**
     * Redirige al usuario al panel correcto basado en su rol. Este método se usa después del login para acceder a la ruta '/panel de cada rol'.
     * @method bool isAdmin()
     * @method bool isAlumno()
     * @method bool isProfesor()
     * @method bool isTutorLaboral()
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
            return redirect()->route('tutores.panel');
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

    /**
     * Método que redirige a la vista de creación de usuarios
     */
    public function create(){
        return view('usuarios.crear');
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
            // TODO: Se pueden añadir aquí bloques 'else if' para 'alumno', 'tutor_laboral', etc.

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
     * Redirige al listado de usuarios de la base de datos
     */
    public function show(){
        $usuarios = User::all();

        return view('usuarios.show',compact('usuarios'));
    }

    /**
     * Procesa la petición AJAX de DataTables.
     */
    public function showDataTable(Request $request) {
        
        // 1. Consulta Base (Selecciona los campos necesarios)
        $query = User::select(['id', 'name', 'email', 'rol']);

        // 2. FILTRADO (Búsqueda Global de DataTables)
        if (!empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'LIKE', "%{$searchValue}%")
                  ->orWhere('email', 'LIKE', "%{$searchValue}%")
                  ->orWhere('rol', 'LIKE', "%{$searchValue}%");
            });
        }
        
        // 3. CONTEO DE REGISTROS
        $recordsFiltered = $query->count();
        $recordsTotal = User::count(); 

        // 4. ORDENACIÓN (ORDER BY)
        if (isset($request->order[0]['column'])) {
            $columnIndex = $request->order[0]['column'];
            $columnName = $request->columns[$columnIndex]['name'];
            $direction = $request->order[0]['dir'];

            if (in_array($columnName, ['id', 'nombre', 'email', 'rol'])) {
                $dbColumn = ($columnName == 'nombre') ? 'name' : $columnName;
                $query->orderBy($dbColumn, $direction);
            }
        }

        // 5. PAGINACIÓN (LIMIT y OFFSET)
        if ($request->length != -1) {
            $query->limit($request->length)->offset($request->start);
        }

        // 6. OBTENEMOS LOS DATOS Y FORMATEAMOS LA RESPUESTA
        $usuarios = $query->get();
        
        $datos = [];
        foreach($usuarios as $usuario) {
            $editUrl = route('usuarios.editar', ['id' => $usuario->id]);
            
            // Formateamos el nombre para el atributo HTML
            $userName = htmlspecialchars($usuario->name, ENT_QUOTES, 'UTF-8'); 
            
            $acciones = '<a href="'.$editUrl.'" class="btn btn-warning btn-sm me-2">Modificar</a>';
            
            if (!$usuario->isAdmin()) {
                 $acciones .= ' <input type="button" 
                                    data-id="'.$usuario->id.'" 
                                    data-nombre="'.$userName.'"  
                                    value="Eliminar" 
                                    class="btn btn-danger btn-sm eliminar-usuario" />';
            }
            
            $datos[] = [
                'id' => $usuario->id,
                'nombre' => $usuario->name, // Mapeamos $usuario->name a la columna 'nombre' de DataTables
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'acciones' => $acciones
            ];
        }

        // 7. Devolver la respuesta en formato JSON
        return response()->json([
            'draw' => $request->draw, 
            'recordsTotal' => $recordsTotal, 
            'recordsFiltered' => $recordsFiltered, 
            'data' => $datos
        ]);
    }

    /**
     * Método para eliminar un usuario
     */
    public function eliminar(Request $request) {

        $usuario = User::find($request->id);

        //El usuario admin no se puede eliminar
        if ($usuario && $usuario->isAdmin()) {
            return response()->json(['error' => 'No se puede eliminar un usuario con rol de Administrador.'], 403);
        }

        if ($usuario) {
            $usuario->delete();
            return response()->json(['success' => 'Usuario eliminado correctamente.'], 200);
        }
        
        // Si el usuario no existe
        return response()->json(['error' => 'Usuario no encontrado.'], 404);
    }

    /**
     * Muestra el formulario para editar un usuario.
     */
    public function edit($id)
    {
        // Buscar al usuario o fallar si no existe
        $usuario = User::findOrFail($id);
        
        return view('usuarios.editar', compact('usuario'));
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

        // 2. Agregar la regla de contraseña si se proporciona
        if (!empty($request->password)) {
            $rules['password'] = 'nullable|min:8|confirmed';
        }

        $datos = $request->validate($rules);

        // 3. Preparar los datos para la actualización
        $updateData = [
            'name' => $datos['name'],
            'email' => $datos['email']
        ];

        // 4. Incluir la contraseña solo si se proporcionó un valor nuevo
        if (!empty($request->password)) {
            $updateData['password'] = $datos['password']; 
        }

        $usuario->update($updateData);

        return redirect()->route('usuarios.show')->with('success', 'Usuario actualizado correctamente.');
    }
}