<?php

namespace App\Http\Controllers;

use App\Models\Profesor; 
use App\Models\Proyecto; 
use App\Models\ProfesorModulo;
use App\Models\Alumno; 
use App\Models\Modulo;
use App\Models\Ras; 
use App\Models\Tarea; 
use App\Models\Criterio;
use App\Models\User;
use App\Rules\ValidarTexto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

class ProfesorController extends Controller
{
    /**
     * Método para las conexiones dinámicas necesarias para las consultas
     */ 
    private function setDynamicConnection($proyecto_id)
    {
        $proyecto = Proyecto::where('id_base_de_datos', $proyecto_id)->firstOrFail();
        $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
        
        $config = config('database.connections.' . config('database.default'));
        $config['database'] = $proyecto->conexion;
        
        Config::set("database.connections.{$connectionName}", $config);

        Modulo::getConnectionResolver()->setDefaultConnection($connectionName);
        Alumno::getConnectionResolver()->setDefaultConnection($connectionName);
        ProfesorModulo::getConnectionResolver()->setDefaultConnection($connectionName);
        Ras::getConnectionResolver()->setDefaultConnection($connectionName); 
        Tarea::getConnectionResolver()->setDefaultConnection($connectionName); 
        Criterio::getConnectionResolver()->setDefaultConnection($connectionName); 
        
        return $connectionName;
    }

    /**
     * Método para las restaurar las conexiones dinámicas
     */
    private function restoreConnection()
    {   
        Modulo::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));
        ProfesorModulo::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Ras::getConnectionResolver()->setDefaultConnection(config('database.default')); 
        Tarea::getConnectionResolver()->setDefaultConnection(config('database.default')); 
        Criterio::getConnectionResolver()->setDefaultConnection(config('database.default')); 
    }

    /**
     * Método que redirige a la vista para mostrar los profesores
     */
    public function indexProfesores()
    {
        // El modelo Profesor usa la conexión Galileo por defecto
        $profesores = Profesor::all(); 

        return view('profesor.index', compact('profesores'));
    }

    public function mostrarAlumnos(Request $request, $profesor_id)
    {
        // 1. Obtenemos el profesor de la BD Galileo
        $profesor = Profesor::findOrFail($profesor_id);
        $filtro = $request->input('filtro', 'docente');// Fijamos la propiedad del filtro de la datatable
        $alumnosGlobal = collect();// Colección de alumnos a mostrar
        
        // 2. Obtenemos todas las bases de datos de proyecto activas
        $proyectos = Proyecto::where('finalizado', 0)->get();

        foreach ($proyectos as $proyecto) {// Recorremos los proyectos
            
            // Configuramos la conexión dinámica para esta BD de proyecto
            $conexion_proyecto_nombre = $this->setDynamicConnection($proyecto->id_base_de_datos);
            
            // 3. Consulta de Alumnos en la BD actual
            $alumnosQuery = Alumno::query();

            if ($filtro === 'todos') { // Si el filtro de la tabla es 'todos'
                // --- Opción A: Alumnos de todos los módulos que imparte ---
                
                $moduloIds = DB::connection($conexion_proyecto_nombre)
                               ->table('profesor_modulo')
                               ->where('profesor_id', $profesor_id)
                               ->pluck('modulo_id');

                $alumnosLocal = $alumnosQuery
                                 ->whereHas('modulos', function ($query) use ($moduloIds) {
                                     $query->whereIn('modulo_id', $moduloIds);
                                 })->get();

            } else { // $filtro === 'docente'
                // --- Opción B: Solo aquellos de los que es tutor docente ---

                $alumnosLocal = $alumnosQuery
                                 ->where('tutor_docente_id', $profesor_id)
                                 ->get();
            }

            // Añadimos el nombre del proyecto a cada alumno para la vista
            $alumnosLocal->each(function ($alumno) use ($proyecto) {
                $alumno->proyecto_nombre = $proyecto->proyecto;
            });
            
            // 4. Agregamos los resultados a la colección global
            $alumnosGlobal = $alumnosGlobal->merge($alumnosLocal);
        }

        // 5. Devolvemos la conexión (limpieza de la conexión)
        $this->restoreConnection();

        return view('profesor.alumnos', ['profesor' => $profesor, 'alumnos' => $alumnosGlobal, 'filtro' => $filtro]);
    }

    /**
     * Método que redirige a la vista de gestión de profesores
     */
    public function index(Request $request)
    {
        // 1. Capturamos el filtro, por defecto 'todos'
        $filtro = $request->get('estado', 'todos');

        // 2. Preparamos la consulta base
        $query = Profesor::query();

        // 3. Aplicamos el filtro si es necesario
        if ($filtro === 'activos') {
            $query->where('activo', 1);
        } elseif ($filtro === 'inactivos') {
            $query->where('activo', 0);
        }
        
        // Ejecutamos la consulta para obtener los profesores filtrados
        $profesores = $query->orderBy('nombre', 'asc')->get();

        // Inicializamos colecciones
        foreach ($profesores as $profesor) {
            $profesor->modulos_collection = collect(); 
            $profesor->alumnos_count = 0;
        }

        // Obtener proyectos activos y los recorrermos
        $proyectos = Proyecto::where('finalizado', 0)->get();

        foreach ($proyectos as $proyecto) {
            $connectionName = $this->setDynamicConnection($proyecto->id_base_de_datos);
            DB::purge($connectionName);

            try {
                foreach ($profesores as $profesor) {
                    // Buscamos los módulos
                    $modulosData = DB::connection($connectionName)
                        ->table('modulos')
                        ->join('profesor_modulo', 'modulos.id_modulo', '=', 'profesor_modulo.modulo_id')
                        ->where('profesor_modulo.profesor_id', $profesor->id_profesor)
                        ->select('modulos.id_modulo', 'modulos.nombre')
                        ->get();

                    foreach($modulosData as $mod) {
                        $profesor->modulos_collection->push($mod);
                    }

                    // Contamos los alumnos
                    if ($modulosData->isNotEmpty()) {
                        $idsModulos = $modulosData->pluck('id_modulo')->toArray();
                        
                        $totalAlumnosEnProyecto = DB::connection($connectionName)
                            ->table('alumno_modulo')
                            ->whereIn('modulo_id', $idsModulos)
                            ->distinct('alumno_id')
                            ->count('alumno_id');
                        
                        $profesor->alumnos_count += $totalAlumnosEnProyecto;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error proyecto {$proyecto->proyecto}: " . $e->getMessage());
            }
        }

        // Asignamos la colección final a los módulos del profesor
        foreach ($profesores as $profesor) {
            $profesor->modulos = $profesor->modulos_collection;
        }

        $this->restoreConnection();

        // Pasamos también la variable $filtro a la vista para mantener el select seleccionado
        return view('gestion.profesores.index', compact('profesores', 'filtro'));
    }

    /**
     * Crea un profesor a partir de un formulario
     */
    public function create(){
        return view('gestion.profesores.create');
    }

    /**
     * Almacena el nuevo profesor en la base de datos y genera un usuario con su información
     */
    public function store(Request $request)
    {
        // 1. Validamos los datos de entrada
         $validated = $request->validate([
            'nombre' => ['required', new ValidarTexto],
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        // 2. Ejecutamos la operación dentro de una transacción
        try {
            DB::connection('mysql')->transaction(function () use ($validated) {                
                // 3. Creamos el registro de Profesor
                $profesor = Profesor::create([
                    'nombre' => $validated['nombre'],
                ]);

                // 4. Crear el registro de Usuario asociado
                // Los campos rolable_id y rolable_type son para la relación polimórfica.
                User::create([
                    'name' => $validated['nombre'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'rol' => 'profesor', // Por defecto para los profesores
                    'rolable_id' => $profesor->id_profesor,
                    'rolable_type' => Profesor::class,
                ]);
            });

            return redirect()->route('gestion.profesores.index')->with('success', 'Profesor ' . $validated['nombre'] . ' y su cuenta de usuario creados con éxito.');

        } catch (\Exception $e) {
            // Logear el error para debugging
            \Illuminate\Support\Facades\Log::error('Error al crear el profesor: ' . $e->getMessage());
            
            // Si la transacción falla, redirigir con un mensaje de error genérico
            return redirect()->back()->withInput()->withErrors(['store_error' => 'Error inesperado al crear el profesor. Revise el log.']);
        }
    }

    /**
     * Modifica los datos de un profesor
     */
    public function update(Request $request, $profesor_id)
    {
        // 1. Buscamos el profesor y su usuario asociado
        $profesor = Profesor::findOrFail($profesor_id);
        $user = $profesor->user; 

        // 2. Validamos los campos
        // Usamos el ID del usuario ($user->id) para ignorarlo en la comprobación de email único
        $userId = $user ? $user->id : 'NULL';

        $validated = $request->validate([
            'nombre'   => ['required', new ValidarTexto], // Validamos que es una cadena de texto
            'email'    => 'required|email|unique:users,email,' . $userId,
            'password' => 'nullable|min:8|confirmed',
        ]);

        try {
            // 3. Transacción
            // Al estar todo en la misma BD, si falla el usuario, se deshace el cambio del profesor también.
            DB::beginTransaction();

            // Actualizamos Profesor
            $profesor->nombre = $validated['nombre'];
            $profesor->save();

            // Actualizar Usuario (Sincronización)
            if ($user) {
                $user->name = $validated['nombre'];
                $user->email = $validated['email'];

                // Solo actualiza la contraseña si se proporcionó un valor nuevo
                if (!empty($validated['password'])) {
                    $user->password = $validated['password'];
                }
                
                $user->save();
            } else {
                // Si por alguna corrupción de datos antigua no tiene usuario, lo logueamos
                Log::warning("Profesor {$profesor->id_profesor} actualizado sin usuario asociado.");
            }

            DB::commit();

            return redirect()->route('gestion.profesores.index')->with('success', 'Datos del profesor ' . $validated['nombre'] . ' actualizados con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Logueamos si hay error
            Log::error('Error update Profesor: ' . $e->getMessage());
            
            return redirect()->back()->withInput()->withErrors('Error al actualizar el profesor: ' . $e->getMessage());
        }
    }

    /**
     * Muestra la ficha detallada del profesor.
     * 1. Módulos donde imparte clase (Docente).
     * 2. Alumnos de los que es responsable (Tutor Docente).
     */
    public function show($profesor_id)
    {
        // 1. Obtenemos el profesor
        $profesor = Profesor::with('user')->findOrFail($profesor_id);

        // 2. Creamos las colecciones para acumular los datos de las distintas BDs
        $modulosDocentes = collect();
        $alumnosTutorizados = collect();

        // 3. Obtenemos los proyectos activos
        $proyectos = Proyecto::where('finalizado', 0)->get();

        foreach ($proyectos as $proyecto) {
            // Configuramos la conexión dinámica para cada proyecto
            $nombreConexion = $this->setDynamicConnection($proyecto->id_base_de_datos);
            \Illuminate\Support\Facades\DB::purge($nombreConexion);// Verificamos que se ha limpiado

            try {
                // A. Buscamos los módulos en la tabla 'profesores_modulos'
                $mods = Modulo::join('profesor_modulo', 'modulos.id_modulo', '=', 'profesor_modulo.modulo_id')
                    ->where('profesor_modulo.profesor_id', $profesor_id)
                    ->select('modulos.*') // Importante: Selecciona solo campos de módulos para evitar conflictos de ID con la pivote
                    ->withCount('alumnos') 
                    ->get();

                foreach ($mods as $m) {
                    // Añadimos los datos del proyecto para usarlos en las rutas de las vista
                    $m->proyecto_nombre = $proyecto->proyecto;
                    $m->proyecto_id = $proyecto->id_base_de_datos;
                    $modulosDocentes->push($m);
                }

                // B. Buscamos los alumnos de los que es Tutor Docente
                $alums = Alumno::where('tutor_docente_id', $profesor_id)
                    ->get();

                foreach ($alums as $a) {
                    // Añadimos los datos del proyecto para usarlos en las rutas de las vista
                    $a->proyecto_nombre = $proyecto->proyecto;
                    $a->proyecto_id = $proyecto->id_base_de_datos;
                    $alumnosTutorizados->push($a);
                }

            } catch (\Exception $e) {
                continue;
            }
        }
        
        $this->restoreConnection();// Quitamos las conexiones dinámicas y volvemos a la general

        return view('gestion.profesores.show', compact('profesor', 'modulosDocentes', 'alumnosTutorizados'));
    }

    /**
     * Método que redirige a la vista para editar los detalles de un profesor
     */
    public function edit($profesor_id)
    {
        // 1. Encontramos al Profesor en la BD Galileo
        $profesor = Profesor::findOrFail($profesor_id);

        // 2. Encontramos el registro de usuario asociado para obtener el email
        $user = User::where('rolable_id', $profesor->id_profesor)
                    ->where('rolable_type', Profesor::class)
                    ->firstOrFail();

        $profesor->email = $user->email;// Añadimos el email

        return view('gestion.profesores.edit', compact('profesor'));
    }

    /**
     * Método que desactiva y activa los datos de un profesor en la base de datos para evitar el riesgo de pérdida de integridad referencial.
     */
    public function toggleActivo($id)
    {
        try {
            DB::beginTransaction();

            $profesor = Profesor::findOrFail($id);
            
            // 1. Invertimos el estado
            $nuevoEstado = !$profesor->activo;
            $profesor->activo = $nuevoEstado;
            $profesor->save();

            // 2. Buscamos el usuario asociado (incluso borrados)
            $user = User::withTrashed()
                ->where('rolable_id', $profesor->id_profesor)
                ->where('rolable_type', Profesor::class)
                ->first();

            $mensaje = '';

            if ($nuevoEstado) {
                // Comprobamos si hay usuario para activarlo
                if ($user) {
                    if ($user->trashed()) {
                        $user->restore(); // Restaurar usuario existente
                        $mensaje = 'Profesor reactivado. Acceso de usuario restaurado.';
                    } else {
                        $mensaje = 'Profesor activado (El usuario ya estaba activo).';
                    }
                } else {
                    // Si no existía o surge un error inesperado creamos usuario nuevo
                    $emailBase = strtolower(str_replace(' ', '.', $profesor->nombre));
                    $email = $emailBase . '@ies.galileo.com';
                    
                    // Evitamos los duplicados de correo por defecto
                    if(User::where('email', $email)->exists()) {
                        $email = $emailBase . rand(10,99) . '@ies.galileo.com';
                    }

                    // Vinculamos el usuario al profesor
                    User::createRolableUser($profesor, [
                        'name' => $profesor->nombre,
                        'email' => $email,
                        'password' => 'password', // Por defecto la contraseña es 'password'
                        'rol' => 'profesor'
                    ]);
                    $mensaje = "Profesor activado. Usuario creado: $email";
                }
            } else {
                // Para desactivar el usuario
                if ($user) {
                    $user->delete(); // Realizamos un Soft Delete
                }
                $mensaje = 'Profesor desactivado. Acceso revocado.';
            }

            DB::commit();
            return redirect()->back()->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error toggleActivo: ' . $e->getMessage());
            return redirect()->back()->withErrors('Error: ' . $e->getMessage());
        }
    }
}