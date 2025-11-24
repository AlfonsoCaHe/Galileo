<?php

namespace App\Http\Controllers;

// use App\Models\Modulo;
// use App\Models\Tarea;
use App\Models\Alumno;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
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
        // [Añadir otros modelos locales: Ras::class, Tarea::class, etc.]
        
        return $proyecto;
    }
    
    // Método auxiliar para restaurar la conexión
    private function restoreConnection()
    {
        // Restaurar la conexión predeterminada (Galileo)
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));
        // [Añadir otros modelos locales]
    }

    /**
     * Listamos todos los alumnos de las bases de datos activas
     */
    public function listadoVisibles()
    {
        $proyectos = Proyecto::where('finalizado', 0)->get();

        $alumnos_totales = new Collection(); 
        
        foreach ($proyectos as $proyecto) {
            $conexion = $proyecto->conexion;
            $dbName = $proyecto->proyecto; // El campo 'proyecto' guarda el nombre de la BD
            
            try {
                // Configuramos la conexión dinámica
                $baseConfig = config('database.connections.mysql');
                $newConfig = $baseConfig;
                $newConfig['database'] = $dbName; // Usamos el nombre de la BD del proyecto

                // Sobrescribimos o añadir la conexión dinámica a la configuración
                // Esto permite que 'Alumno::on($conexion)' funcione.
                Config::set("database.connections.{$conexion}", $newConfig); 
                
                $alumnos_proyecto = Alumno::on($conexion)->get();
                
                // Agregamos los resultados a la colección global
                $alumnos_totales = $alumnos_totales->merge($alumnos_proyecto);

            } catch (\Exception $e) {
                // Si la conexión falla (BD no existe/credenciales erróneas), no detenemos la ejecución, sino que ignoramos este proyecto.
            } finally {
                // Hay que limpiar la configuración dinámica después de usarla para no interferir con el resto de la aplicación.
                Config::offsetUnset("database.connections.{$conexion}");
            }
        }

        // Renombramos la variable para el compact
        $alumnos = $alumnos_totales;

        return view('alumno.index', compact('alumnos'));
    }

    /**
     * Listamos todos los alumnos de las bases de datos activas
     */
    public function listadoAlumnosProyecto(Request $request, $proyecto_id)
    {
        $proyecto = Proyecto::where('id_base_de_datos', $proyecto_id)->get()->first();
        
        $conexion = $proyecto->conexion;
        $dbName = $proyecto->proyecto; // El campo 'proyecto' guarda el nombre de la BD
            
        try {
            // Configuramos la conexión dinámica
            $baseConfig = config('database.connections.mysql');
            $newConfig = $baseConfig;
            $newConfig['database'] = $dbName; // Usamos el nombre de la BD del proyecto

            // Sobrescribimos o añadir la conexión dinámica a la configuración
            // Esto permite que 'Alumno::on($conexion)' funcione.
            Config::set("database.connections.{$conexion}", $newConfig); 
                
            $alumnos = Alumno::on($conexion)->get();

        } catch (\Exception $e) {
            // Si la conexión falla (BD no existe/credenciales erróneas), no detenemos la ejecución, sino que ignoramos este proyecto.
        } finally {
            // Hay que limpiar la configuración dinámica después de usarla para no interferir con el resto de la aplicación.
            Config::offsetUnset("database.connections.{$conexion}");
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
        $conexionesConfiguradas = []; // Almacenamos las conexiones que se vamos a ir creando

        try {
            foreach ($proyectos as $proyecto) {
                $conexion = trim($proyecto->conexion); 
                $dbName = $proyecto->proyecto;

                // 1. Configuración dinámica
                $baseConfig = config('database.connections.mysql');
                $newConfig = $baseConfig;
                $newConfig['database'] = $dbName;
                Config::set("database.connections.{$conexion}", $newConfig); 
                $conexionesConfiguradas[] = $conexion; // Una vez establecida, la añadimos a la lista

                DB::purge($conexion);
                DB::connection($conexion);

                // 2. Buscamos al alumno
                $alumno = Alumno::on($conexion)
                    ->where('id_alumno', $alumno_id)
                    ->with(['tutorLaboral', 'tutorDocente']) 
                    ->first();
                
                // 3. Si encontramos al alumno
                if ($alumno) {
                    $conexionEncontrada = $conexion;
                    $alumno->setConnection($conexionEncontrada); 
                    
                    // Cargamos sus Módulos y Tareas (usando la conexión asignada)
                    $alumno->load(['modulos']); 
                    $alumno->load([
                        'tareas' => function ($query) {
                            $query->with('criterios');
                        }
                    ]);
                    
                    break; // Rompemos el bucle. La conexión queda en Config::set().
                }    
            }

        } catch (\Exception $e) {
            // En caso de error inesperado, el finally manejará la limpieza
            // throw $e; // Comentado para no detener la ejecución de la aplicación, si hay algún error descomentar
        } finally {
            // 4. Eliminamos las conexiones que no se usaron y por tanto no necesitaremos.
            foreach ($conexionesConfiguradas as $conn) {
                if ($conn !== $conexionEncontrada) {
                    Config::offsetUnset("database.connections.{$conn}"); 
                    DB::purge($conn);
                }
            }
        }

        if (!$alumno) {
            return redirect()->route('alumno.index')->with('error', 'El alumno solicitado no fue encontrado o el proyecto no está activo.');
        }
        
        // La conexión $conexionEncontrada permanece configurada en la vista.
        return view('alumno.show', compact('alumno', 'conexionEncontrada'));
    }

    /**
     * Muestra el listado consolidado de todos los alumnos
     * de todos los proyectos activos (index para el CRUD de Administrador).
     */
    public function index()
    {
        // El listado de alumnos de las bases de datos activas se manejará aquí.
        // Utilizaremos y adaptaremos la lógica de listadoVisibles().
        
        $proyectos = Proyecto::where('finalizado', 0)->get();

        $alumnos_totales = new Collection(); 
        
        foreach ($proyectos as $proyecto) {
            $conexion = $proyecto->conexion;
            $dbName = $proyecto->proyecto; // El campo 'proyecto' guarda el nombre de la BD
            
            try {
                // 1. Configuramos la conexión dinámica (ya sabes cómo hacerlo)
                $baseConfig = config('database.connections.mysql');
                $newConfig = $baseConfig;
                $newConfig['database'] = $dbName; 
                
                Config::set("database.connections.{$conexion}", $newConfig); 
                
                // 2. Obtenemos los alumnos del proyecto actual, cargando sus tutores
                //    Los tutores docente (Profesor) y laboral (TutorLaboral) están en la BD principal.
                //    El ORM de Laravel debe manejar la conexión cruzada por defecto.
                $alumnos_proyecto = Alumno::on($conexion)->with(['tutorDocente', 'tutorLaboral'])->get();

                // 3. Añadimos el nombre del proyecto a cada alumno para mostrarlo en la tabla.
                $alumnos_proyecto = $alumnos_proyecto->map(function ($alumno) use ($dbName, $proyecto) {
                    // Añadimos el nombre de la BD/Proyecto para la vista
                    $alumno->proyecto_nombre = $dbName; 
                    // Necesitamos el ID del proyecto de GALILEO para las rutas
                    $alumno->proyecto_galileo_id = $proyecto->id_base_de_datos; 
                    return $alumno;
                });
                
                // 4. Agregamos los resultados a la colección global
                $alumnos_totales = $alumnos_totales->merge($alumnos_proyecto);

            } catch (\Exception $e) {
                // Loguear error, pero seguir con el siguiente proyecto
                Log::error("Error al obtener alumnos del proyecto {$dbName}: " . $e->getMessage());
            } finally {
                // Limpiamos la conexión dinámica temporal
                DB::purge($conexion);
                Config::offsetUnset("database.connections.{$conexion}");
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

        // Usamos el id del proyecto para configurar la conexión
        $proyecto = $this->setDynamicConnection($proyecto_id_galileo);

        try {
            DB::connection((new Alumno())->getConnectionName())->transaction(function () use ($validated, $proyecto) {
                
                // 1. Creamos el registro de Alumno en la BD del Proyecto
                $alumno = Alumno::create([
                    'nombre' => $validated['nombre'],
                    'tutor_laboral_id' => $validated['tutor_laboral_id'] ?? null,
                    'tutor_docente_id' => $validated['profesores_id'] ?? null, 
                ]);

                // 2. Crear el registro de Usuario en la BD Principal (Galileo)
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

        $alumno->email = $user->email;

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
            'email' => 'required|email|unique:users,email,' . $user->id,// Evitamos el usuario actual, por si no se modifica
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
     * Método para eliminar un módulo del proyecto. No se puede eliminar si tiene RAs o Tareas asociadas
     * Con los alumnos solo rompe el enlace
     */
    public function destroy($proyecto_id, $alumno_id)
    {
        try {
            // Buscamos el user (BD Galileo)
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
            $user->delete();

            return redirect()->route('gestion.alumnos.index')
                            ->with('success', 'Alumno ' . $alumno->nombre . ' eliminado con éxito.');

        } catch (\Exception $e) {
            // Nos aseguramos de restaurar la conexión a Galileo en caso de fallo.
            $this->restoreConnection(); 

            return redirect()->back()->withErrors('Error al eliminar el alumno: ' . $e->getMessage());
        }
    }
}