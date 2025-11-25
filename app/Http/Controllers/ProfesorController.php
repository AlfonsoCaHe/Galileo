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
    public function indexProfesores()
    {
        // El modelo Profesor usa la conexión principal (Galileo) por defecto
        $profesores = Profesor::all(); 

        return view('profesor.index', compact('profesores'));
    }

    public function mostrarAlumnos(Request $request, $profesor_id)
    {
        // 1. Obtenemos el profesor central (BD Galileo)
        $profesor = Profesor::findOrFail($profesor_id);
        $filtro = $request->input('filtro', 'docente');
        $alumnosGlobal = collect();
        
        $config_base = config('database.connections.' . config('database.default'));
        
        // 2. Obtenemos todas las bases de datos de proyecto registradas y visibles
        // $proyectos = Proyecto::all(); //Obtenemos todas las bases de datos de proyecto incluidos los finalizados
        $proyectos = Proyecto::where('finalizado', 0)->get();

        foreach ($proyectos as $proyecto) {
            // Aseguramos que id_base_de_datos es el nombre correcto de la PK de Proyecto
            // Aunque ya lo comprobamos al crear la base de datos, se trata de una comprobación adicional
            $conexion_proyecto_nombre = 'proyecto_temp_' . $proyecto->id_base_de_datos; 
            
            // 2.a. Configuramos la conexión dinámica para esta BD de proyecto
            $config_base['database'] = $proyecto->conexion;
            config(["database.connections.{$conexion_proyecto_nombre}" => $config_base]);

            // 2.b. Forzamos el modelo Alumno a usar la BD del proyecto actual
            Alumno::getConnectionResolver()->setDefaultConnection($conexion_proyecto_nombre); 
            
            // 3. Consulta de Alumnos en la BD actual
            $alumnosQuery = Alumno::query();

            if ($filtro === 'todos') {
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

        // 5. Devolvemos la conexión de Alumno a la principal (limpieza de la conexión)
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));

        return view('profesor.alumnos', [
            'profesor' => $profesor, 
            'alumnos' => $alumnosGlobal, 
            'filtro' => $filtro
        ]);
    }
    
    //Métodos para las conexiones dinámicas necesarias para las consultas
    private function setDynamicConnection($proyecto, $conexion_nombre)
    {
        $config_base = config('database.connections.' . config('database.default'));
        $config_base['database'] = $proyecto->conexion;
        config(["database.connections.{$conexion_nombre}" => $config_base]);

        // Forzamos a los modelos dinámicos a usar esta conexión
        Modulo::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        Alumno::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        ProfesorModulo::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        Ras::getConnectionResolver()->setDefaultConnection($conexion_nombre); 
        Tarea::getConnectionResolver()->setDefaultConnection($conexion_nombre); 
        Criterio::getConnectionResolver()->setDefaultConnection($conexion_nombre); 
    }
    
    private function restoreConnection()
    {
        $default = config('database.default');
        
        Modulo::getConnectionResolver()->setDefaultConnection($default);
        Alumno::getConnectionResolver()->setDefaultConnection($default);
        ProfesorModulo::getConnectionResolver()->setDefaultConnection($default);
        Ras::getConnectionResolver()->setDefaultConnection($default); 
        Tarea::getConnectionResolver()->setDefaultConnection($default); 
        Criterio::getConnectionResolver()->setDefaultConnection($default); 
    }

    /**
     * Método que redirige a la vista de gestión de profesores
     */
    public function index()
    {
        // 1. Obtener todos los profesores de la BD Central (Galileo)
        $profesores = Profesor::all();

        // Inicializamos las propiedades para que la vista no falle si no hay proyectos
        foreach ($profesores as $profesor) {
            $profesor->modulos_collection = collect(); 
            $profesor->alumnos_count = 0;
        }

        // 2. Obtener proyectos activos (finalizado = 0)
        // Aseguramos que solo buscamos en proyectos vivos
        $proyectos = Proyecto::where('finalizado', 0)->get();
        
        $defaultConnection = config('database.default');

        // 3. Bucle Multi-Tenant: Recorrer cada proyecto para buscar datos
        foreach ($proyectos as $proyecto) {
            
            // Configurar conexión dinámica
            $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
            $config = config('database.connections.mysql');
            $config['database'] = $proyecto->conexion; // Nombre BD del proyecto
            Config::set("database.connections.{$connectionName}", $config);
            DB::purge($connectionName);

            try {
                // Para cada profesor, buscamos sus datos en ESTE proyecto
                foreach ($profesores as $profesor) {
                    
                    // A. Buscar MÓDULOS (Tabla 'profesor_modulo' en la BD dinámica)
                    // Usamos Query Builder porque estamos cruzando conexiones
                    $modulosData = DB::connection($connectionName)
                        ->table('modulos')
                        ->join('profesor_modulo', 'modulos.id_modulo', '=', 'profesor_modulo.modulo_id')
                        ->where('profesor_modulo.profesor_id', $profesor->id_profesor)
                        ->select('modulos.id_modulo', 'modulos.nombre')
                        ->get();

                    // Agregamos a la colección temporal del profesor
                    foreach($modulosData as $mod) {
                        $profesor->modulos_collection->push($mod);
                    }

                    // B. Contar ALUMNOS (Tabla 'alumnos_modulos')
                    if ($modulosData->isNotEmpty()) {
                        $idsModulos = $modulosData->pluck('id_modulo')->toArray();
                        
                        // Contamos alumnos únicos matriculados en los módulos de este profe
                        $totalAlumnosEnProyecto = DB::connection($connectionName)
                            ->table('alumnos_modulos')
                            ->whereIn('modulo_id', $idsModulos)
                            ->distinct('alumno_id')
                            ->count('alumno_id');
                        
                        $profesor->alumnos_count += $totalAlumnosEnProyecto;
                    }
                }

            } catch (\Exception $e) {
                // Si un proyecto falla, lo ignoramos para no romper toda la web, pero guardamos log
                Log::error("Error conectando al proyecto {$proyecto->proyecto}: " . $e->getMessage());
            }
        }

        // 4. Preparar datos finales para la vista
        foreach ($profesores as $profesor) {
            // Pasamos la colección temporal a la propiedad 'modulos' que usa el Blade
            $profesor->modulos = $profesor->modulos_collection;
        }

        // Restaurar conexión original
        DB::setDefaultConnection($defaultConnection);

        return view('gestion.profesores.index', compact('profesores'));
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
        // 1. Validación de los datos de entrada
         $validated = $request->validate([
            'nombre' => ['required', new ValidarTexto],
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        // 2. Ejecutar la operación dentro de una transacción
        try {
            DB::connection('mysql')->transaction(function () use ($validated) {                
                // 3. Crear el registro de Profesor
                $profesor = Profesor::create([
                    'nombre' => $validated['nombre'],
                ]);

                // 4. Crear el registro de Usuario asociado
                // Los campos rolable_id y rolable_type son para la relación polimórfica.
                User::create([
                    'name' => $validated['nombre'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'rol' => 'profesor',
                    'rolable_id' => $profesor->id_profesor,
                    'rolable_type' => Profesor::class,
                ]);
            });

            return redirect()->route('gestion.profesores.index')
                             ->with('success', 'Profesor ' . $validated['nombre'] . ' y su cuenta de usuario creados con éxito.');

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
    public function update(Request $request){
        
    }

    /**
     * Método que redirige a la vista para ver los detalles de un profesor
     */
    public function show($id)
    {
        $profesor = Profesor::findOrFail($id);
        // Aquí podrías añadir lógica similar al index si quieres ver detalles de sus alumnos
        return view('profesor.show', compact('profesor')); // Asegúrate de tener esta vista o redirigir
    }

    /**
     * Método que redirige a la vista para editar los detalles de un profesor
     */
    public function edit($profesor_id)
    {
        // 1. Encontrar al Profesor en la BD principal (Galileo)
        $profesor = Profesor::findOrFail($profesor_id);

        // 2. Encontrar el registro de usuario asociado (para obtener el email)
        $user = User::where('rolable_id', $profesor->id_profesor)
                    ->where('rolable_type', Profesor::class)
                    ->firstOrFail();

        $profesor->email = $user->email;

        return view('gestion.profesores.edit', compact('profesor'));
    }

    /**
     * Método que desactiva y activa los datos de un profesor en la base de datos para evitar el riesgo de pérdida de integridad referencial.
     */
    // Asegúrate de importar esto arriba
    public function toggleActivo($id)
    {
        try {
            DB::beginTransaction();

            $profesor = Profesor::findOrFail($id);
            
            // 1. Invertir estado
            $nuevoEstado = !$profesor->activo;
            $profesor->activo = $nuevoEstado;
            $profesor->save();

            // 2. Buscar usuario asociado (incluso borrados)
            $user = User::withTrashed()
                ->where('rolable_id', $profesor->id_profesor)
                ->where('rolable_type', Profesor::class)
                ->first();

            $mensaje = '';

            if ($nuevoEstado) {
                // --- ACTIVAR ---
                if ($user) {
                    if ($user->trashed()) {
                        $user->restore(); // Restaurar usuario existente
                        $mensaje = 'Profesor reactivado. Acceso de usuario restaurado.';
                    } else {
                        $mensaje = 'Profesor activado (El usuario ya estaba activo).';
                    }
                } else {
                    // Crear Usuario Nuevo (Si no existía)
                    $emailBase = strtolower(str_replace(' ', '.', $profesor->nombre));
                    $email = $emailBase . '@ies.galileo.com';
                    
                    // Evitar duplicados simples
                    if(User::where('email', $email)->exists()) {
                        $email = $emailBase . rand(10,99) . '@ies.galileo.com';
                    }

                    User::createRolableUser($profesor, [
                        'name' => $profesor->nombre,
                        'email' => $email,
                        'password' => 'password', // Default
                        'rol' => 'profesor'
                    ]);
                    $mensaje = "Profesor activado. Usuario creado: $email";
                }
            } else {
                // --- DESACTIVAR ---
                if ($user) {
                    $user->delete(); // Soft Delete
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