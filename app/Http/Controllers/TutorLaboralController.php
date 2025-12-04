<?php

namespace App\Http\Controllers;

use App\Models\TutorLaboral;
use App\Models\Empresa;
use App\Models\Alumno;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TutorLaboralController extends Controller
{
    public function indexTutoresLaborales()
    {
        // El modelo TutorLaboral usa la conexión principal (Galileo) por defecto
        $tutores = TutorLaboral::all(); 

        return view('tutores.index', compact('tutores'));
    }

    public function mostrarAlumnos()
    {
        // 1. Obtenemos el tutor laboral central (BD Galileo)
        $tutorLaboral_id = Auth::id();

        $tutor = TutorLaboral::findOrFail($tutorLaboral_id);
        
        $alumnosTotales = collect();
        
        $config_base = config('database.connections.' . config('database.default'));
        
        // 2. Obtenemos todas las bases de datos de proyecto registradas y visibles
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
            $alumnos = Alumno::query()
                ->where('tutor_laboral_id', $tutorLaboral_id)->get();
            
            // 4. Agregamos los resultados a la colección global
            $alumnosTotales = $alumnosTotales->merge($alumnos);
        }

        // 5. Devolvemos la conexión de Alumno a la principal (limpieza de la conexión)
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));

        return view('alumno.index', [
            'tutor' => $tutor, 
            'alumnos' => $alumnosTotales
        ]);
    }

    /**
     * Obtenemos el listado de alumnos del tutor laboral
     */
    public function indexAlumnosTutorizados()
    {
        // 1. Obtener el ID del tutor laboral actualmente autenticado.
        $tutorLaboral_id = Auth::id();

        // 2. Obtenemos el objeto TutorLaboral para pasarlo a la vista si es necesario
        $tutor = TutorLaboral::findOrFail($tutorLaboral_id);

        $alumnosTotales = collect();
        
        // Configuraciones base para las conexiones dinámicas
        $config_base = config('database.connections.' . config('database.default'));
        
        // 3. Obtenemos todas las bases de datos de proyecto registradas
        $proyectos = Proyecto::where('finalizado', 0)->get(); //

        foreach ($proyectos as $proyecto) {
            $conexion_proyecto_nombre = 'proyecto_temp_' . $proyecto->id_base_de_datos; 
            
            // 4. Configurar y forzar la conexión dinámica para el proyecto actual
            $config_base['database'] = $proyecto->conexion;
            config(["database.connections.{$conexion_proyecto_nombre}" => $config_base]);

            Alumno::getConnectionResolver()->setDefaultConnection($conexion_proyecto_nombre); //
            
            // 5. Filtramos los alumnos por el ID del tutor laboral logueado
            // La consulta se ejecuta en la base de datos del proyecto actual
            $alumnos = Alumno::query()
                ->where('tutor_laboral_id', $tutorLaboral_id)
                ->get();
            
            // 6. Agregamos los resultados a la colección global
            $alumnosTotales = $alumnosTotales->merge($alumnos); //
        }

        // 7. Devolver la conexión de Alumno a la principal (limpieza)
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));

        // 8. Retornar la vista con los alumnos filtrados
        return view('alumno.index', [
            'tutor' => $tutor, 
            'alumnos' => $alumnosTotales
        ]);
    }

    /**
     * Almacena un nuevo Tutor Laboral y crea su Usuario asociado polimórficamente.
     */
    public function storeTutor(Request $request, $empresa_id)
    {
        // 1. Validación de datos (Sin cambios)
        $request->validate([
            'nombre' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users,email', 
            'password' => 'required|string|min:8', 
        ]);

        try {
            DB::transaction(function () use ($request, $empresa_id) {
                
                // Creamos el registro en la tabla tutores_laborales
                $tutor = TutorLaboral::create([
                    'nombre' => $request->nombre,
                    'email' => $request->email,
                    'empresa_id' => $empresa_id,
                ]);

                // Creamos el Usuario que realizará la relación polimórfica en el modelo User
                User::createRolableUser($tutor, [
                    'name' => $request->nombre,
                    'email' => $request->email,
                    'password' => $request->password,
                ]);
                
            });

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors('Error al crear el tutor/usuario: ' . $e->getMessage());
        }

        return redirect('/gestion/empresas')->with('success', 'Tutor Laboral y Usuario creados con éxito.');
    }

    /**
     * Actualiza los datos del Tutor Laboral y su registro de Usuario asociado.
     */
    public function updateTutor(Request $request, $tutor_id)
    {
        // 1. Buscar el perfil de Tutor Laboral y su Usuario
        $tutor = TutorLaboral::findOrFail($tutor_id);
        $user = $tutor->user;

        // Reglas de Validación: El email debe ser único en la tabla 'users', pero ignorando al usuario actual ($user->id).
        $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed', 
        ]);

        try {
            DB::connection(config('database.default'))->transaction(function () use ($request, $tutor, $user) {
                
                // 2. Actualizar el perfil TutorLaboral
                $tutor->update([
                    'nombre' => $request->nombre,
                    'email' => $request->email,
                ]);

                // 3. Actualizar el registro en la tabla USERS
                $userData = [
                    'name' => $request->nombre,
                    'email' => $request->email,
                ];
                
                // Solo si el campo 'password' fue rellenado, lo incluimos (Laravel lo hashea automáticamente si se usa 'update')
                if ($request->filled('password')) {
                    $userData['password'] = $request->password;
                }

                $user->update($userData);

            });

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors('Error al actualizar el tutor/usuario: ' . $e->getMessage());
        }

        return redirect()->route('gestion.empresas.edit', ['empresa_id' => $tutor->empresa_id])->with('success', 'Tutor Laboral y Usuario actualizados con éxito.');
    }

    /**
     * Muestra el formulario para crear un nuevo Tutor Laboral para una Empresa específica.
     */
    public function createTutor($empresa_id)
    {
        // Obtiene la empresa o falla si no existe
        $empresa = Empresa::findOrFail($empresa_id);
        
        return view('gestion.tutores.create', compact('empresa'));
    }

    /**
     * Elimina el perfil de Tutor Laboral y su Usuario asociado, pero solo si no tiene Alumnos o Tareas asociados en NINGÚN proyecto.
     */
    public function destroyTutor($tutor_id)
    {
        // 1. Encontrar el perfil del Tutor Laboral (BD Galileo)
        $tutor = TutorLaboral::findOrFail($tutor_id);
        $tutor_laboral_id = $tutor->id_tutor_laboral;
        
        $alumnosTotales = collect();
        $config_base = config('database.connections.' . config('database.default'));
        
        // --- 2. VALIDACIÓN DE REFERENCIAS CRUZADAS EN PROYECTOS ---
        
        // Obtenemos todas las bases de datos de proyecto registradas
        $proyectos = Proyecto::where('finalizado', 0)->get(); 

        foreach ($proyectos as $proyecto) {
            $conexion_proyecto_nombre = 'proyecto_temp_' . $proyecto->id_base_de_datos; 
            
            // Configurar y forzar la conexión dinámica
            $config_base['database'] = $proyecto->conexion;
            config(["database.connections.{$conexion_proyecto_nombre}" => $config_base]);

            // Establecer temporalmente la conexión para los modelos locales
            Alumno::getConnectionResolver()->setDefaultConnection($conexion_proyecto_nombre);
            
            // 2a. Buscar alumnos asociados en la BD actual
            $alumnos = Alumno::query()
                ->where('tutor_laboral_id', $tutor_laboral_id)
                ->get();
            
            if ($alumnos->isNotEmpty()) {
                // Devolver la conexión de Alumno a la principal (limpieza)
                Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));

                return redirect()->back()->withErrors("No se puede eliminar al tutor **{$tutor->nombre}**. Tiene **{$alumnos->count()} alumno(s)** asociado(s) en el proyecto **{$proyecto->proyecto}**.");
            }
            
            // 2b. Opcional: Podrías añadir una comprobación de Tareas aquí si el modelo Tarea tiene una FK directa a TutorLaboral.
            // Actualmente, Tarea solo tiene FK a Alumno (y Modulo/Criterio), por lo que si el alumno está bien, la tarea también lo está.
        }
        
        // Devolver la conexión de Alumno a la principal (limpieza)
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));

        // --- 3. ELIMINACIÓN SEGURA (Si pasa la validación) ---
        
        $user = $tutor->user;

        try {
            DB::connection(config('database.default'))->transaction(function () use ($tutor, $user) {
                
                // Eliminar el Tutor Laboral (perfil)
                $tutor->delete();

                // Eliminar el registro de Usuario (cuenta de acceso)
                if ($user) {
                    $user->delete();
                }
            });

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al eliminar el tutor y su usuario: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Tutor Laboral y Usuario eliminados con éxito.');
    }

    
    /**
     * Muestra el formulario para editar un Tutor Laboral existente.
     */
    public function editTutor($tutor_id)
    {
        // 1. Busca el perfil de Tutor Laboral (BD Galileo)
        $tutor = TutorLaboral::findOrFail($tutor_id);
        
        // 2. Obtiene el usuario asociado a través de la relación polimórfica inversa
        $user = $tutor->user; 
        
        // Retornamos la vista con el perfil del tutor y su usuario
        return view('gestion.tutores.edit', ['tutor' => $tutor, 'user' => $user,]);
    }

    
}