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
use Illuminate\Support\Collection;

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
     * Obtiene el listado de tutores laborales y sus empresas para el index de DataTables.
     */
    public function indexEmpresas()
    {
        // Obtenemos todas las empresas con sus tutores laborales
        $empresas = Empresa::with('tutores')->get(); 

        return view('gestion.empresas.index', compact('empresas'));
    }

    /**
     * Muestra el formulario para crear una nueva Empresa y su Tutor Laboral Principal.
     */
    public function createEmpresa()
    {
        return view('gestion.empresas.create'); 
    }

    /**
     * Almacena la nueva Empresa y crea el Tutor Laboral Principal asociado.
     */
    public function storeEmpresa(Request $request)
    {
        // 1. Validación de datos: Empresa y Tutor/Usuario en un solo bloque
        $request->validate([
            'cif_nif' => 'required|max:9|unique:empresas,cif_nif',
            'nombre' => 'required|max:255',
            'nombre_gerente' => 'required|max:255',
            'nif_gerente' => 'required|max:15',
            
            // Datos del Tutor Principal (y del User)
            'tutor_nombre' => 'required|max:255',
            'tutor_email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        try {
            DB::transaction(function () use ($request) {
                
                // Creamos la Empresa
                $empresa = Empresa::create([
                    'cif_nif' => $request->cif_nif,
                    'nombre' => $request->nombre,
                    'nombre_gerente' => $request->nombre_gerente,
                    'nif_gerente' => $request->nif_gerente,
                ]);

                // Creamos el perfil Tutor Laboral y lo asociamos a la empresa
                $tutor = TutorLaboral::create([
                    'nombre' => $request->tutor_nombre,
                    'email' => $request->tutor_email,
                    'empresa_id' => $empresa->id_empresa, // Usamos la PK de la empresa creada
                ]);

                // Creamos el Usuario asociado al Tutor mediante la relación polimórfica
                User::createRolableUser($tutor, [
                    'name' => $request->tutor_nombre,
                    'email' => $request->tutor_email,
                    'password' => $request->password,
                ]);
                
            });

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors('Error al crear la empresa y el tutor: ' . $e->getMessage());
        }

        return redirect()->route('gestion.empresas.index')->with('success', 'Empresa y Tutor Principal creados con éxito.');
    }

    /**
     * Muestra el formulario de edición para una empresa específica y sus tutores.
     */
    public function editEmpresa($empresa_id)
    {
        // Obtenemos la empresa y cargamos la relación de tutores
        $empresa = Empresa::with('tutores')->findOrFail($empresa_id);

        return view('gestion.empresas.edit', compact('empresa'));
    }

    /**
     * Almacena la actualización de los datos de la empresa.
     */
    public function updateEmpresa(Request $request, $empresa_id)
    {
        // 1. Validación de datos
        $request->validate([
            'nombre' => ['required', 'max:255'],
            'nombre_gerente' => ['nullable', 'max:255'],
            'nif_gerente' => ['nullable', 'max:15'],
        ]);

        try {
            DB::transaction(function () use ($request, $empresa_id) {
                
                $empresa = Empresa::findOrFail($empresa_id);

                // 2. Actualización de la Empresa
                $empresa->update([
                    'nombre' => $request->nombre,
                    'nombre_gerente' => $request->nombre_gerente,
                    'nif_gerente' => $request->nif_gerente,
                ]);

                // Nota: La gestión de tutores (añadir/editar/eliminar) se manejaría 
                // con llamadas AJAX o métodos separados, ya que es una tabla anidada.
            });

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors('Error al actualizar la empresa: ' . $e->getMessage());
        }

        return redirect()->route('empresas.index')->with('success', 'Empresa actualizada con éxito.');
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

        return redirect()->back()->with('success', 'Tutor Laboral y Usuario creados con éxito.');
    }

    /**
     * Actualiza los datos del Tutor Laboral y su registro de Usuario asociado.
     */
    public function updateTutor(Request $request, $tutor_id)
    {
        $tutor = TutorLaboral::with('user')->findOrFail($tutor_id);
        $user = $tutor->user;

        // 1. Validación de datos
        $request->validate([
            'nombre' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'nullable|string|min:8|confirmed', 
        ]);

        try {
            DB::transaction(function () use ($request, $tutor, $user) {
                
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
                
                // Solo si el campo 'password' fue rellenado, lo incluimos. 
                if ($request->filled('password')) {
                    $userData['password'] = $request->password; 
                }

                $user->update($userData);

            });

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors('Error al actualizar el tutor/usuario: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Tutor Laboral y Usuario actualizados con éxito.');
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
}