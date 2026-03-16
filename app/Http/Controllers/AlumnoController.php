<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Proyecto;
use App\Models\Modulo;
use App\Models\Profesor;
use App\Models\Empresa;
use App\Models\TutorLaboral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Models\User;

use App\Rules\ValidarTexto;

class AlumnoController extends Controller
{
    // Método auxiliar para configurar la conexión dinámica
    private function setDynamicConnection($proyecto_id)
    {
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $conexion_nombre = 'proyecto_temp_' . $proyecto->id_base_de_datos;
        $config_base = config('database.connections.' . config('database.default'));
        
        $config_base['database'] = $proyecto->conexion;
        config(["database.connections.{$conexion_nombre}" => $config_base]);

        // Forzamos a los modelos dinámicos a usar esta conexión
        Alumno::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        
        return $proyecto;
    }
    
    // Método auxiliar para restaurar la conexión
    private function restoreConnection()
    {
        // Restaurar la conexión predeterminada (Galileo)
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));
    }

    /**
     * Listamos todos los alumnos de los proyectos activos
     */
    public function listadoVisibles()
    {
        $proyectos = Proyecto::where('finalizado', 0)->get();
        $alumnos = new Collection(); 
        
        foreach ($proyectos as $proyecto) {
            try {
                $this->setDynamicConnection($proyecto->id_base_de_datos);
                
                $alumnos_proyecto = Alumno::get();
                $alumnos = $alumnos->merge($alumnos_proyecto);

            } catch (\Exception $e) {
                // Si la conexión falla (BD no existe/credenciales erróneas), no detenemos la ejecución, sino que ignoramos este proyecto.
            } finally {
                $this->restoreConnection();
            }
        }

        return view('alumno.index', compact('alumnos'));
    }

    /**
     * Listamos todos los alumnos de las bases de datos activas
     */
    public function listadoAlumnosProyecto(Request $request, $proyecto_id)
    {
        try {
            $this->setDynamicConnection($proyecto_id);
            $alumnos = Alumno::get();
        } catch (\Exception $e) {
            $alumnos = new Collection();
        } finally {
            $this->restoreConnection();
        }

        return view('alumno.index', compact('alumnos'));
    }

    /**
     * Obtenemos la información del alumno que pasamos por parámetro y redirigimos a la vista
     */
    public function showAlumno(Request $request, $alumno_id)
    {
        $proyectos = Proyecto::where('finalizado', 0)->get(); 
        $alumno = null;
        $conexionEncontrada = null;

        foreach ($proyectos as $proyecto) {
            try {
                $this->setDynamicConnection($proyecto->id_base_de_datos);
                $conexion = Alumno::getActualConnectionName();

                $alumno = Alumno::where('id_alumno', $alumno_id)
                    ->with(['tutorLaboral', 'tutorDocente']) 
                    ->first();
                
                if ($alumno) {
                    $conexionEncontrada = $conexion;
                    $alumno->load(['modulos', 'tareas.criterios']); 
                    break; 
                }
                
                $this->restoreConnection();

            } catch (\Exception $e) {
                $this->restoreConnection();
            }
        }

        if (!$alumno) {
            return redirect()->route('alumno.index')->with('error', 'El alumno solicitado no fue encontrado o el proyecto no está activo.');
        }
        
        // La conexión $conexionEncontrada permanece configurada en la vista.
        return view('alumno.show', compact('alumno', 'conexionEncontrada'));
    }

    /**
     * Muestra el listado consolidado de todos los alumnos de todos los proyectos activos (index para el CRUD de Administrador).
     */
    public function index()
    {
        // El listado de alumnos de las bases de datos activas se manejará aquí.
        $proyectos = Proyecto::where('finalizado', 0)->get();
        $alumnos_totales = new Collection(); 
        
        foreach ($proyectos as $proyecto) {
            try {
                // 1. Configuramos la conexión dinámica
                $this->setDynamicConnection($proyecto->id_base_de_datos);
                
                // 2. Obtenemos los alumnos del proyecto actual, cargando sus tutores
                // Los tutores docente (Profesor) y laboral (TutorLaboral) están en la BD principal, por tantoLaravel debe manejar la conexión dinámica y estática a la vez
                $alumnos_proyecto = Alumno::with(['tutorDocente', 'tutorLaboral'])->get();

                // 3. Añadimos el nombre del proyecto a cada alumno para mostrarlo en la tabla.
                $alumnos_proyecto = $alumnos_proyecto->map(function ($alumno) use ($proyecto) {
                    // Añadimos el nombre de la BD/Proyecto para la vista
                    $alumno->proyecto_nombre = $proyecto->proyecto; 
                    // Necesitamos el ID del proyecto de GALILEO para las rutas
                    $alumno->proyecto_galileo_id = $proyecto->id_base_de_datos; 
                    return $alumno;
                });
                
                // 4. Agregamos los resultados a la colección global
                $alumnos_totales = $alumnos_totales->merge($alumnos_proyecto);

            } catch (\Exception $e) {
                // Si falla logueamos el error y seguimos con el siguiente proyecto
                Log::error("Error al obtener alumnos del proyecto {$proyecto->proyecto}: " . $e->getMessage());
            } finally {
                $this->restoreConnection();
            }
        }
        // Retornamos a la vista
        return view('gestion.alumnos.index', compact('alumnos_totales'));
    }

    /**
     * Método que redirige a la vista que contiene el formulario de creación de alumnos
     */
    public function create(){
        $proyectos = Proyecto::where('finalizado', 0)->get();// Cargamos los proyectos activos para incorporar alumnos

        return view('gestion.alumnos.create', compact('proyectos'));
    }

    /**
     * Método que inserta el alumno en la base de datos
     */
    public function store(Request $request)
    {
        $proyecto_id_galileo = $request->input('database_id');
        
        // 2. Validación de todos los campos necesarios. 
        $validated = $request->validate([
            'nombre' => ['required', new ValidarTexto],
            'database_id' => 'required|uuid',
            'profesores_id' => 'nullable|uuid', 
            'tutor_laboral_id' => 'nullable|uuid',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        // Usamos el id del proyecto para configurar la conexión dinámica
        $proyecto = $this->setDynamicConnection($proyecto_id_galileo);

        try {
            DB::connection(Alumno::getActualConnectionName())->transaction(function () use ($validated, $proyecto) {
                
                // 1. Creamos el registro de Alumno en la BD del Proyecto
                $alumno = Alumno::create([
                    'nombre' => $validated['nombre'],
                    'tutor_laboral_id' => $validated['tutor_laboral_id'] ?? null,
                    'tutor_docente_id' => $validated['profesores_id'] ?? null, 
                ]);

                // 2. Creamos el registro de Usuario en la BD Principal (Galileo)
                $user = User::createRolableUser($alumno, [
                    'name' => $validated['nombre'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'rol' => 'alumno', //Por defecto para los alumnos
                ]);
            });

            $this->restoreConnection(); // Restaura la conexión a Galileo

            return redirect()->route('gestion.alumnos.index')->with('success', 'Alumno creado con éxito.');

        } catch (\Exception $e) {
            $this->restoreConnection(); // Restaura la conexión incluso si falla
            return redirect()->back()->withInput()->withErrors('Error al crear el alumno: ' . $e->getMessage());
        }
    }

    /**
     * Método que redirige a la vista de edición del alumno en cuestión
     */
    public function edit($proyecto_id, $alumno_id)
    {
        $proyecto = $this->setDynamicConnection($proyecto_id);
        
        $alumno = Alumno::findOrFail($alumno_id);

        $this->restoreConnection();

        $user = User::where('rolable_id',$alumno_id)->first();

        $alumno->email = $user->email ?? null;// Si el usuario no tiene cuenta activa, no aparecerá un correo electrónico

        return view('gestion.alumnos.edit', compact('proyecto', 'alumno'));
    }

    /**
     * Método para almacenar los cambios en el módulo
     */
    public function update(Request $request, $proyecto_id, $alumno_id)
    {
        // 1. Restauramos temporalmente la conexión para encontrar el User (en BD Galileo)
        // Esto es necesario para la validación 'unique' y para obtener el ID del usuario
        $this->restoreConnection();
        $user = User::where('rolable_id', $alumno_id)->firstOrFail();

        // 2. Validamos los campos del formulario
        $validated = $request->validate([
            'nombre' => ['required', new ValidarTexto],
            'email' => 'required|email|unique:users,email,' . $user->id,// Evitamos que revise el usuario actual, por si no se modifica
            'password' => 'nullable|min:8|confirmed',
        ]);

        try {
            // 3. Establecemos la conexión dinámica
            $proyecto = $this->setDynamicConnection($proyecto_id);
            $alumno = Alumno::findOrFail($alumno_id); // Buscamos al alumno en la BD dinámica

            // Iniciamos transacción en la BD del proyecto
            DB::connection($alumno->getConnectionName())->transaction(function () use ($validated, $alumno) {
                
                // Actualizamos los datos del alumno
                $alumno->update([
                    'nombre' => $validated['nombre'],
                ]);
            });
            
            $this->restoreConnection();
            
            // 4. Actualizamos el usuario
            $user_updates = [
                'name' => $validated['nombre'],
                'email' => $validated['email'],
            ];

            // Solo actualiza la contraseña si se proporcionó un valor nuevo
            if (!empty($validated['password'])) {
                $user_updates['password'] = $validated['password']; 
            }

            $user->update($user_updates);

            return redirect()->route('gestion.alumnos.index')->with('success', 'Datos del alumno ' . $validated['nombre'] . ' actualizados con éxito.');

        } catch (\Exception $e) {
            $this->restoreConnection(); 
            return redirect()->back()->withInput()->withErrors('Error al actualizar el alumno: ' . $e->getMessage());
        }
    }

    /**
     * Método para eliminar un módulo del proyecto. No se puede eliminar si tiene RAs o Tareas asociadas. Con los alumnos solo rompe el enlace
     */
    public function destroy($proyecto_id, $alumno_id)
    {
        try {
            // Buscamos el user en la BD Galileo
            // Aseguramos la conexión principal para encontrar el User antes de cambiarla.
            $this->restoreConnection(); 
            $user = User::where('rolable_id', $alumno_id)->firstOrFail();

            // 2. Configuramos la conexión dinámica
            $proyecto = $this->setDynamicConnection($proyecto_id);
            $alumno = Alumno::findOrFail($alumno_id);

            // 3. Eliminamos al alumno
            DB::connection($alumno->getConnectionName())->transaction(function () use ($alumno) {
                $alumno->delete();
            });

            // 4. Restauramos la conexión y eliminamos user una vez hemos eliminado el alumno (BD Galileo)
            $this->restoreConnection();
            $user->delete();// Recordamos que en usuarios es soft delete

            return redirect()->route('gestion.alumnos.index')->with('success', 'Alumno ' . $alumno->nombre . ' eliminado con éxito.');

        } catch (\Exception $e) {
            // Nos aseguramos de restaurar la conexión a Galileo en caso de fallo.
            $this->restoreConnection(); 

            return redirect()->back()->withErrors('Error al eliminar el alumno: ' . $e->getMessage());
        }
    }

    /**
     * Método para mostrar un alumno para el administrador
     */
    public function show($proyecto_id, $alumno_id)
    {
        // 1. Conexión Dinámica
        $proyecto = $this->setDynamicConnection($proyecto_id);
        
        // 2. Cargamos el Alumno con sus relaciones actuales
        $alumno = Alumno::with(['modulos', 'tutorDocente', 'tutorLaboral.empresa', 'user', 'modulosBorrados'])->findOrFail($alumno_id);

        // 3. Cargamos listas para los selectores (BD Principal)
        $this->restoreConnection();
        $profesores = Profesor::where('activo', true)->orderBy('nombre')->get();
        $empresas = Empresa::orderBy('nombre')->get();

        // C. Módulos Disponibles (Para el modal de matriculación, solo módulos activos en el proyecto del alumno)
        $this->setDynamicConnection($proyecto_id);
        $idsActuales = $alumno->modulos->pluck('id_modulo');
        $idsBorrados = $alumno->modulosBorrados->pluck('id_modulo');

        // Unimos ambas colecciones y convertimos a array
        $idsAExcluir = $idsActuales->merge($idsBorrados)->unique()->toArray();
        $modulosDisponibles = Modulo::where('proyecto_id', $proyecto_id)
                                ->whereNotIn('id_modulo', $idsAExcluir)
                                ->get();

        return view('gestion.alumnos.show', compact('proyecto', 'alumno', 'profesores', 'empresas', 'modulosDisponibles', 'proyecto_id'));
    }

    /**
     * AJAX: Actualiza el Tutor Docente
     */
    public function updateTutorDocente(Request $request, $proyecto_id, $alumno_id)
    {
        $this->setDynamicConnection($proyecto_id); 
        $alumno = Alumno::findOrFail($alumno_id);
        $alumno->tutor_docente_id = $request->tutor_id;
        $alumno->save();
        return response()->json(['success' => true]);
    }

    /**
     * AJAX: Actualiza el Tutor Laboral
     */
    public function updateTutorLaboral(Request $request, $proyecto_id, $alumno_id)
    {
        $this->setDynamicConnection($proyecto_id);
        $alumno = Alumno::findOrFail($alumno_id);
        $alumno->tutor_laboral_id = $request->tutor_id;
        $alumno->save();
        return response()->json(['success' => true]);
    }

    /**
     * AJAX: Obtiene los tutores de una empresa (para el desplegable)
     */
    public function getTutoresPorEmpresa($proyecto_id, $empresa_id)
    {
        // Los tutores están en la BD Galileo, no necesitamos conexión dinámica aquí, solo la conexión por defecto
        $tutores = TutorLaboral::where('empresa_id', $empresa_id)->get(['id_tutor_laboral', 'nombre']);
        return response()->json($tutores);
    }

    /**
     * AJAX: Actualiza el periodo del alumno
     */
    public function updatePeriodo(Request $request)
    {
        $request->validate([
            'id_alumno' => 'required',
            'proyecto_id' => 'required',
            'periodo' => 'nullable|in:Periodo 1,Periodo 2'
        ]);

        try {
            $this->setDynamicConnection($request->proyecto_id);

            $alumno = Alumno::findOrFail($request->id_alumno);
            $alumno->periodo = $request->periodo;
            $alumno->save();

            return response()->json(['status' => 'success', 'message' => 'Periodo actualizado en la base de datos del proyecto']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error al conectar o actualizar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Modal de matriculación del alumno en nuevos módulos
     */
    public function matricular(Request $request, $proyecto_id, $alumno_id)
    {
        $this->setDynamicConnection($proyecto_id);
        $alumno = Alumno::findOrFail($alumno_id);
        
        if ($request->has('modulos')) {
            // attach añade sin borrar los anteriores
            $alumno->modulos()->attach($request->modulos);
        }

        return redirect()->back()->with('success', 'Matriculación actualizada correctamente.');
    }

    /**
     * Función para quitar a un alumno de un módulo (se realiza un softdelete, por lo que se puede deshacer el proceso)
     */
    public function desmatricular($proyecto_id, $alumno_id, $modulo_id)
    {
        $this->setDynamicConnection($proyecto_id);

        try {
            DB::transaction(function () use ($alumno_id, $modulo_id) {
                
                // 1. Soft Delete de TAREAS
                // Al tener el trait SoftDeletes, esto ya no borra la fila, solo pone la fecha
                \App\Models\Tarea::where('alumno_id', $alumno_id)
                    ->where('modulo_id', $modulo_id)
                    ->delete();

                // 2. Soft Detach del MÓDULO
                // En lugar de usar detach() directamente, solo actualizamos la tupla de la tabla pivote
                // updateExistingPivot marca como borrado con soft delete
                $alumno = Alumno::findOrFail($alumno_id);
                $alumno->modulos()->updateExistingPivot($modulo_id, [
                    'deleted_at' => now()
                ]);

            });

            return redirect()->back()->with('success', 'Alumno dado de baja del módulo (Puede deshacerse si contacta con soporte).');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al desmatricular: ' . $e->getMessage());
        }
    }

    /**
     * Restaura la matrícula de un alumno en un módulo y recupera sus tareas/notas.
     */
    public function restaurarMatricula($proyecto_id, $alumno_id, $modulo_id)
    {
        // 1. Configuramos y obtenemos el nombre de la conexión dinámica
        $this->setDynamicConnection($proyecto_id);
        $nombreConexion = Modulo::getConnectionResolver()->getDefaultConnection();

        try {
            // Iniciamos una transacción por seguridad
            DB::connection($nombreConexion)->transaction(function () use ($nombreConexion, $alumno_id, $modulo_id) {
                
                // 1. Restauramos el módulo de la tabla pivote
                // Usamos DB::table directo para evitar que eloquent filtre los borrados (soft deletes) y no los encuentre
                DB::connection($nombreConexion)->table('alumno_modulo')
                       ->where('alumno_id', $alumno_id)
                       ->where('modulo_id', $modulo_id)
                       ->update(['deleted_at' => null]); // Ponemos NULL para restaurar

                // 2. Restaurar las tareas asociadas
                // Aquí sí podemos usar el Modelo porque withTrashed() funciona bien en Modelos normales
                // Aseguramos que el modelo use la conexión correcta
                \App\Models\Tarea::withTrashed() // Importante: incluir las borradas
                    ->where('alumno_id', $alumno_id)
                    ->where('modulo_id', $modulo_id)
                    ->restore(); // Método propio de SoftDeletes

            });

            return redirect()->back()->with('success', 'Matrícula y datos restaurados correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al restaurar: ' . $e->getMessage());
        } finally {
            $this->restoreConnection();
        }
    }
}