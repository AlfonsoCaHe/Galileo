<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Proyecto;
use App\Models\Modulo;
use App\Models\Profesor;
use App\Models\User;
use App\Models\Alumno;
use App\Models\Tarea;
use App\Models\TutorLaboral;

class ProfesoradoDocenteController extends Controller
{
    /**
     * Muestra el listado de módulos asignados al profesor de todas las bases de datos activas.
     */
    public function indexModulos()
    {
        $profesor = Auth::user()->rolable;// Profesor logeado
        
        // 1. Obtener todos los proyectos activos (no finalizados) de la base de datos 'mysql' (Galileo)
        $proyectos = Proyecto::where('finalizado', false)->get();

        $modulos = collect(); // Colección para acumular los módulos encontrados

        // 2. Iteramos sobre cada proyecto para extraer los módulos del profesor
        foreach ($proyectos as $proyecto) {
            $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
            $config = config('database.connections.mysql');
            $config['database'] = $proyecto->conexion;
            Config::set("database.connections.{$connectionName}", $config);
            DB::purge($connectionName);
            try {
                // B. Obtenemos los módulos donde el profesor está asignado
                // Usamos la conexión 'mysql' que acabamos de configurar
                $modulosData = DB::connection($connectionName)
                    ->table('modulos')
                    ->join('profesor_modulo', 'modulos.id_modulo', '=', 'profesor_modulo.modulo_id')
                    ->where('profesor_modulo.profesor_id', $profesor->id_profesor)
                    ->select('modulos.id_modulo', 'modulos.nombre')
                    ->select([
                        'modulos.id_modulo', 
                        'modulos.nombre',
                        // AÑADIDO: Subconsulta para contar alumnos en la tabla pivote
                        // Asumimos que la tabla pivote es 'alumno_modulo' y verificamos que no estén borrados (soft delete)
                        DB::raw('(SELECT COUNT(*) FROM alumno_modulo WHERE alumno_modulo.modulo_id = modulos.id_modulo AND alumno_modulo.deleted_at IS NULL) as alumnos_count')
                    ])
                    ->get();

                // C. Enriquecer los objetos para la vista
                // Agregamos datos del proyecto al objeto módulo para usarlo en la tabla y rutas
                foreach($modulosData as $mod) {
                    $mod->nombre_proyecto = $proyecto->proyecto; // Para mostrar en tabla
                    $mod->id_proyecto_galileo = $proyecto->id_base_de_datos; // Para generar rutas
                    $modulos->push($mod);
                }

            } catch (\Exception $e) {dd($e);
                // Si una BD falla, logueamos y continuamos con el siguiente proyecto
                // Esto evita que un error en un tenant rompa todo el panel
                error_log("Error conectando a proyecto {$proyecto->proyecto}: " . $e->getMessage());
                continue;
            }
        }
        return view('profesores.modulos', compact('modulos'));
    }

    /**
     * Método que redirige a la vista ver Alumnos de un profesor
     */
    public function verAlumnos($proyecto_id, $modulo_id){
        // 1. Configuramos la conexión dinámica
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
        
        $config = config('database.connections.mysql');
        $config['database'] = $proyecto->conexion;
        Config::set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);

        // 2. Obtenemos el módulo para mostrar el título
        $modulo = DB::connection($connectionName)
                    ->table('modulos')
                    ->where('id_modulo', $modulo_id)
                    ->first();

        if (!$modulo) {// Por si alguien intenta acceder erróneamente
            abort(404, 'Módulo no encontrado en este proyecto.');
        }

        // 3. Obtenemos alumnos y contamos sus tareas/entregas
        // Usamos las tablas 'alumnos', pivote 'alumno_modulo' y tabla 'tareas'
        $alumnos = DB::connection($connectionName)
            ->table('alumnos')
            ->join('alumno_modulo', 'alumnos.id_alumno', '=', 'alumno_modulo.alumno_id')
            ->where('alumno_modulo.modulo_id', $modulo_id)
            ->whereNull('alumno_modulo.deleted_at')
            ->select([
                'alumnos.id_alumno', 
                'alumnos.nombre',
                DB::raw("(SELECT COUNT(*) FROM tareas WHERE tareas.alumno_id = alumnos.id_alumno AND tareas.modulo_id = '{$modulo_id}') as tareas_count")
            ])
            ->get();
        
        foreach($alumnos as $alumno){//Buscamos el correo para cada alumno y lo añadimos
            $user = User::where('rolable_id', $alumno->id_alumno)
                    ->where('rolable_type', Alumno::class)
                    ->firstOrFail();

            $alumno->email = $user->email;
        }
        // Pasamos proyecto_id y modulo_id para mantener el contexto en los botones de la vista
        return view('profesores.alumnos', compact('alumnos', 'modulo', 'proyecto'));
    }

    /**
     * Método para mostrar un listado de las tareas del alumno en un determinado módulo
     */
    public function verTareasAlumno($proyecto_id, $modulo_id, $alumno_id)
    {
        // 1. Configurar conexión dinámica
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
        
        $config = config('database.connections.mysql');
        $config['database'] = $proyecto->conexion;
        Config::set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);

        // 2. Obtenemos los datos del alumno
        $alumno = DB::connection($connectionName)
            ->table('alumnos')
            ->where('id_alumno', $alumno_id)
            ->first();

        if (!$alumno) {
            abort(404, 'Alumno no encontrado.');
        }

        // 3. Obtenemos el módulo
        $modulo = DB::connection($connectionName)
            ->table('modulos')
            ->where('id_modulo', $modulo_id)
            ->first();

        // 4. Obtenemos las tareas
        $tareas = DB::connection($connectionName)
            ->table('tareas')
            ->where('alumno_id', $alumno_id)
            ->where('modulo_id', $modulo_id)
            ->orderBy('created_at', 'desc') // Ordenamos por las más recientes
            ->get();

        return view('profesores.alumnos_tareas', compact('alumno', 'modulo', 'tareas', 'proyecto'));
    }

    /**
     * Método que redirige a la vista para la creación de una nueva tarea
     */
    public function crearTarea($proyecto_id, $modulo_id){
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
        
        $config = config('database.connections.mysql');
        $config['database'] = $proyecto->conexion;
        Config::set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);

        $modulo = DB::connection($connectionName)
            ->table('modulos')
            ->where('id_modulo', $modulo_id)
            ->with('ras')
            ->first();

        return view('gestion.tareas.create', compact('proyecto_id', 'modulo'));
    }

    /**
     * Método para redirigir a la vista de alumnos de los que el profesor es tutor docente
     */
    public function tutorizados()
    {
        $profesor = Auth::user()->rolable->id_profesor;
        
        // Obtenemos proyectos activos
        $proyectos = Proyecto::where('finalizado', false)->get();
        
        $alumnosTutorizados = collect();

        foreach ($proyectos as $proyecto) {
            // --- 1. Configuramos la conexión dinámica
            $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
            $config = config('database.connections.mysql');
            $config['database'] = $proyecto->conexion;
            Config::set("database.connections.{$connectionName}", $config);
            DB::purge($connectionName);

            try {
                // --- PASO 1 y 2: Obtener Alumnos + Módulos + Tutor Laboral (BD Proyecto) ---
                // Usamos GROUP_CONCAT para traer todos los módulos en una sola fila por alumno
                $alumnos = Alumno::on($connectionName) // Define la conexión en el modelo
                    ->with('modulos') // ¡Ahora sí funciona! Carga la relación definida en el modelo
                    ->where('tutor_docente_id', $profesor) // Asumiendo que $profesor es el ID
                    ->whereHas('modulos', function($q) {
                        // Opcional: Esto filtra para asegurar que solo traiga alumnos 
                        // que tengan al menos un módulo activo (similar a tu join)
                        $q->whereNull('alumno_modulo.deleted_at');
                    })
                    ->get();

                // --- PASO 3: Inyectar Email desde BD Principal (Galileo) ---
                foreach ($alumnos as $alumno) {
                    // 1. Buscamos el USUARIO en la BD Principal
                    $user = User::find($alumno->usuario_id);
                    
                    // 2. Buscamos el TUTOR LABORAL en la BD Principal
                    $tutor = TutorLaboral::find($alumno->tutor_laboral_id);
                    
                    // 3. Asignamos los datos al alumno
                    $alumno->alumno_email = $user ? $user->email : 'Sin email asociado';
                    
                    // Pasamos el objeto completo del tutor (o null si no tiene)
                    // Esto te permitirá acceder en la vista a $alumno->tutor_laboral->nombre, ->email, etc.
                    $alumno->tutor_laboral = $tutor; 
                    
                    // 4. Metadatos del proyecto (para las rutas y el badge de la vista)
                    $alumno->proyecto_id = $proyecto->id_base_de_datos;
                    $alumno->proyecto_nombre = $proyecto->proyecto;

                    // 5. Añadimos a la colección final
                    $alumnosTutorizados->push($alumno);
                }

            } catch (\Exception $e) {
                Log::error("Error obteniendo tutorizados en {$proyecto->conexion}: " . $e->getMessage());
            }
        }

        return view('profesores.tutorizados', compact('alumnosTutorizados'));
    }

    /**
     * Método que redirige a la vista de las tareas de un alumno tutorizado
     */
    public function tareasAlumnoTutorizado($proyecto_id, $alumno_id){
        // 1. Conexión Dinámica
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
        $config = config('database.connections.mysql');
        $config['database'] = $proyecto->conexion;
        Config::set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);

        // 2. Obtener datos del Alumno (para la cabecera)
        $alumno = DB::connection($connectionName)
                    ->table('alumnos')
                    ->where('id_alumno', $alumno_id)
                    ->first();

        if (!$alumno) abort(404, 'Alumno no encontrado');

        // 3. Obtener TODAS las tareas del alumno (sin filtrar por módulo)
        $tareas = DB::connection($connectionName)
                    ->table('tareas')
                    ->join('modulos', 'tareas.modulo_id', '=', 'modulos.id_modulo') // Join cosmético para ver el nombre del módulo
                    ->where('tareas.alumno_id', $alumno_id)
                    ->select(
                        'tareas.*', 
                        'modulos.nombre as nombre_modulo', // Seleccionamos el nombre para mostrarlo en la tabla
                        'modulos.id_modulo'
                    )
                    ->orderBy('tareas.created_at', 'desc')
                    ->get();

        // Reutilizamos la vista de tareas, pero le pasamos null en $modulo porque ya no es uno específico
        return view('profesores.tareas_docente', compact('alumno', 'tareas', 'proyecto'))->with('modulo', null);
    }

    /**
     * Método que redirige a la vista de editar los datos del profesor para el profesor
     */
    public function editar($profesor_id)
    {
        // 1. Encontrar al Profesor en la BD principal (Galileo)
        $profesor = Profesor::findOrFail($profesor_id);

        // 2. Encontrar el registro de usuario asociado (para obtener el email)
        $user = User::where('rolable_id', $profesor->id_profesor)
                    ->where('rolable_type', Profesor::class)
                    ->firstOrFail();

        $profesor->email = $user->email;

        return view('profesores.edit', compact('profesor'));
    }

    /**
     * Método para modificar la contraseña del profesor por él mismo.
     */
    public function update(Request $request, $profesor_id){
        // 1. Buscamos el profesor y su usuario asociado
        // No necesitamos cambiar de conexión, ya estamos en la principal
        $profesor = Profesor::findOrFail($profesor_id);
        $user = $profesor->user;

        // 2. Validamos los campos
        $validated = $request->validate([
            'password' => 'nullable|min:8|confirmed',
        ]);

        try {
            // 3. Transacción
            DB::beginTransaction();

            if ($user) {
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

            return redirect()->route('profesores.panel')->with('success', 'Datos del profesor ' . $profesor['nombre'] . ' actualizados con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Logueamos si hay error
            Log::error('Error al cambiar contraseña del Profesor: ' . $e->getMessage());
            
            return redirect()->back()->withInput()->withErrors('Error al actualizar el profesor: ' . $e->getMessage());
        }
    }
}