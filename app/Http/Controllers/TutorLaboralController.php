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
    /**
     * Método auxiliar para configurar la conexión dinámica
     */
    private function setDynamicConnection($proyecto_id)
    {
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $conexion_nombre = 'proyecto_temp_' . $proyecto->id_base_de_datos;
        $config_base = config('database.connections.' . config('database.default'));
        
        $config_base['database'] = $proyecto->conexion;
        config(["database.connections.{$conexion_nombre}" => $config_base]);

        // Forzamos el modelo Alumno a usar esta conexión
        Alumno::getConnectionResolver()->setDefaultConnection($conexion_nombre);

        return $conexion_nombre;
    }

    /**
     * Método para restaurar la conexión
     */
    private function restoreConnection()
    {
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));
    }

    public function indexTutoresLaborales()
    {
        // El modelo TutorLaboral usa la conexión Galileo por defecto
        $tutores = TutorLaboral::all(); 

        return view('tutores.index', compact('tutores'));
    }

    /**
     * Método que redirige a la vista del listado de alumnos
     */
    public function mostrarAlumnos()
    {
        // Obtenemos el tutor laboral logueado de la BD Galileo
        $tutorLaboral_id = Auth::id();
        $tutor = TutorLaboral::findOrFail($tutorLaboral_id);
        
        $alumnosTotales = collect();
        
        // Obtenemos todas las bases de datos de proyecto activas
        $proyectos = Proyecto::where('finalizado', 0)->get();

        // La recorremos y almacenamos los alumnos que son suyos
        foreach ($proyectos as $proyecto) {
            $this->setDynamicConnection($proyecto->id_base_de_datos);
            $alumnos = Alumno::query()->where('tutor_laboral_id', $tutorLaboral_id)->get();
            $alumnosTotales = $alumnosTotales->merge($alumnos);
        }

        $this->restoreConnection();

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
        // Validamos los datos (Sin cambios)
        $request->validate([
            'nombre' => 'required|max:255',
            'dni' => 'required|string|max:9',
            'email' => 'required|email|max:255|unique:users,email', 
            'password' => 'required|string|min:8', 
        ]);

        try {
            DB::transaction(function () use ($request, $empresa_id) {
                
                // Creamos el registro en la tabla tutores_laborales
                $tutor = TutorLaboral::create([
                    'nombre' => $request->nombre,
                    'dni' => $request->dni,
                    'email' => $request->email,
                    'empresa_id' => $empresa_id,
                ]);

                // Creamos el Usuario que realizará la relación polimórfica en el modelo User
                User::createRolableUser($tutor, [
                    'name' => $request->nombre,
                    'email' => $request->email,
                    'rol' => 'tutor_laboral',// Por defecto para los tutores laborales
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
        // Buscamos el Tutor Laboral y su Usuario
        $tutor = TutorLaboral::findOrFail($tutor_id);
        $user = $tutor->user;

        // Validamos el email, que debe ser único en la tabla 'usuarios', pero ignorando al usuario actual ($user->id).
        $request->validate([
            'nombre' => 'required|string|max:255',
            'dni' => 'required|string|max:9',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed', 
        ]);

        try {
            DB::connection(config('database.default'))->transaction(function () use ($request, $tutor, $user) {
                
                // Actualizamos el perfil TutorLaboral
                $tutor->update([
                    'nombre' => $request->nombre,
                    'dni' => $request->dni,
                    'email' => $request->email,
                ]);

                // Actualizar el registro en la tabla usuarios
                $userData = [
                    'name' => $request->nombre,
                    'email' => $request->email,
                ];
                
                // Solo si el campo 'password' fue rellenado, lo incluimos
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
        // Encontramos el Tutor Laboral desde la BD Galileo
        $tutor = TutorLaboral::findOrFail($tutor_id);
        $tutor_laboral_id = $tutor->id_tutor_laboral;
        
        // Obtenemos todas las bases de datos de proyecto registradas
        $proyectos = Proyecto::where('finalizado', 0)->get(); 

        // Recorremos y buscamos los alumnos del tutor, si hay alguno en algún proyecto, no se puede eliminar
        foreach ($proyectos as $proyecto) {
            // Realizamos una conexión por proyecto
            $this->setDynamicConnection($proyecto->id_base_de_datos);
            
            // Buscamos alumnos asociados al tutor en la BD actual
            $alumnos = Alumno::query()
                ->where('tutor_laboral_id', $tutor_laboral_id)
                ->get();
            
            if ($alumnos->isNotEmpty()) {
                // Si hay algún alumno, devolvemos la conexión de Alumno y salimos
                $this->restoreConnection();

                return redirect()->back()->withErrors("No se puede eliminar al tutor **{$tutor->nombre}**. Tiene **{$alumnos->count()} alumno(s)** asociado(s) en el proyecto **{$proyecto->proyecto}**.");
            }
        }
        
        // Si no había alumnos
        // Devolvemos la conexión de Alumno
        $this->restoreConnection();
        // Buscamos su usuario
        $user = $tutor->user;

        try {
            DB::connection(config('database.default'))->transaction(function () use ($tutor, $user) {
                
                // Eliminamos el Tutor Laboral (perfil)
                $tutor->delete();

                // Eliminamos el registro de Usuario (cuenta de acceso)
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
        // Buscamos el perfil de Tutor Laboral en la BD Galileo
        $tutor = TutorLaboral::findOrFail($tutor_id);
        
        // Obtenemos el usuario asociado a través de la relación polimórfica
        $user = $tutor->user; 
        
        // Retornamos la vista con el perfil del tutor y su usuario
        return view('gestion.tutores.edit', ['tutor' => $tutor, 'user' => $user,]);
    }
}