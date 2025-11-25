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
    public function index(Request $request)
    {
        // 1. Determinar el estado del filtro
        $estado_filtro = $request->get('estado', 'activos'); // Por defecto, solo activos

        // 2. Construir la consulta base (Aplicar filtro de Activo/Inactivo)
        $query = Profesor::with('user'); 

        if ($estado_filtro === 'activos') {//Si no es activo ni inactivo es todos
            $query->where('activo', 1);
        } elseif ($estado_filtro === 'inactivos') {
            $query->where('activo', 0);
        } 
        
        // Obtenemos la lista de profesores filtrada
        $profesores = $query->get();

        // 3. Obtenemos solo los proyectos activos de la BD principal (Galileo)
        $proyectosActivos = Proyecto::where('finalizado', false)->get();
        
        $stats = [];
        
        // 4. Itera sobre cada profesor filtrado y calcula sus estadísticas
        foreach ($profesores as $profesor) {
            $totalModulos = 0;
            $totalAlumnos = 0;
            $esTutorDocente = false;
            $proyectosConAlumnos = [];
            
            // 5. Iterar sobre cada proyecto activo
            foreach ($proyectosActivos as $proyecto) {
                $conexion_nombre = 'proyecto_temp_' . $proyecto->id_base_de_datos;
                
                // Configurar la conexión dinámica para el proyecto actual
                $this->setDynamicConnection($proyecto, $conexion_nombre);
                
                // A. Módulos impartidos por este profesor en este proyecto
                $modulosImpartidos = ProfesorModulo::where('profesor_id', $profesor->id_profesor)->get();
                $countModulosProyecto = $modulosImpartidos->count();
                $totalModulos += $countModulosProyecto;
                
                // B. Sumar alumnos únicos de los módulos impartidos en este proyecto, así no contamos doble
                $alumnosUnicosIds = [];
                if ($countModulosProyecto > 0) {
                    foreach ($modulosImpartidos as $pm) {
                        // Modulo::find() usa la conexión dinámica
                        $modulo = Modulo::find($pm->modulo_id); 
                        if ($modulo) {
                            // $modulo->alumnos() usa la conexión dinámica
                            $alumnosUnicosIds = array_merge($alumnosUnicosIds, $modulo->alumnos()->pluck('id_alumno')->toArray());
                        }
                    }
                    $countAlumnosProyecto = count(array_unique($alumnosUnicosIds));
                    $totalAlumnos += $countAlumnosProyecto;
                    
                    if ($countAlumnosProyecto > 0) {
                        $proyectosConAlumnos[] = [
                            'nombre' => $proyecto->proyecto,
                            'alumnos' => $countAlumnosProyecto
                        ];
                    }
                }
                
                // C. Verificar si es Tutor Docente en ESTE proyecto
                // Alumno::where() usa la conexión dinámica
                if (Alumno::where('tutor_docente_id', $profesor->id_profesor)->exists()) {
                    $esTutorDocente = true;
                }
                
                // Restaurar la conexión después de cada proyecto
                $this->restoreConnection();
            }
            
            // 6. Almacenar resultados
            $stats[$profesor->id_profesor] = [
                'modulos_total' => $totalModulos,
                'alumnos_total' => $totalAlumnos,
                'es_tutor_docente' => $esTutorDocente,
                'proyectos_detalle' => $proyectosConAlumnos
            ];
        }

        // 7. Pasar los datos a la vista
        return view('gestion.profesores.index', compact('profesores', 'stats', 'estado_filtro'));
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
    public function show($profesor_id){

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
    public function toggleActivo($profesor_id)
    {
        try {
            DB::beginTransaction(); // Importante para mantener integridad entre las dos tablas

            $profesor = Profesor::findOrFail($profesor_id);

            // 1. Invertimos el estado del profesor
            $nuevoEstado = !$profesor->activo;
            $profesor->activo = $nuevoEstado;
            $profesor->save();

            // Buscamos si ya existe un usuario asociado (incluso si está borrado lógicamente)
            // Usamos where porque la relación polimórfica podría no traerlo si está soft-deleted
            $user = User::withTrashed()
                ->where('rolable_id', $profesor->id_profesor)
                ->where('rolable_type', Profesor::class)
                ->first();

            $mensaje = '';

            if ($nuevoEstado === true) {
                // --- CASO: ACTIVAR PROFESOR ---
                
                if ($user) {
                    if ($user->trashed()) {
                        // El usuario existía pero estaba "borrado", lo RESTAURAMOS.
                        // Esto mantiene la integridad referencial y evita el error de "Email duplicado".
                        $user->restore();
                        $mensaje = 'Profesor y usuario reactivados correctamente.';
                    } else {
                        // El usuario existe y ya estaba activo (caso raro, corrección de datos)
                        $mensaje = 'El profesor se ha activado. El usuario ya estaba activo.';
                    }
                } else {
                    // No existe usuario previo, lo CREAMOS.
                    // Aquí va tu lógica de generación de email/pass simplificada como pediste
                    $nombreLimpio = strtolower(str_replace(' ', '.', $profesor->nombre));
                    // Aseguramos unicidad básica añadiendo algo de aleatoriedad o ID si quieres, 
                    // pero sigo tu lógica actual:
                    $emailGenerado = $nombreLimpio . '@ies.galileo.com';
                    
                    User::createRolableUser($profesor, [
                        'name' => $profesor->nombre,
                        'email' => $emailGenerado,
                        'password' => 'password', // Tu default
                        'rol' => 'profesor'
                    ]);
                    
                    $mensaje = 'Profesor activado y nuevo usuario creado.';
                }

            } else {
                // --- CASO: DESACTIVAR PROFESOR ---
                if ($user) {
                    $user->delete(); // Soft Delete (se va a la papelera, no se borra físico)
                }
                $mensaje = 'Profesor desactivado y acceso revocado.';
            }

            DB::commit();
            return redirect()->back()->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error toggleActivo: ' . $e->getMessage());
            return redirect()->back()->withErrors('Error al cambiar el estado: ' . $e->getMessage());
        }
    }
}