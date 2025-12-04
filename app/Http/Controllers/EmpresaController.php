<?php

namespace App\Http\Controllers;

use App\Models\TutorLaboral;
use App\Models\Empresa;
use App\Models\Alumno;
use App\Models\Proyecto;
use App\Models\User;
use App\Models\CupoEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmpresaController extends Controller
{
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

                // Creamos los cupos, necesario para poder modificarlos luego. A 0 como valor inicial
                CupoEmpresa::create([
                    'empresa_id' => $empresa->id_empresa,
                    'periodo' => '1',
                    'plazas' => 0
                ]);
                
                CupoEmpresa::create([
                    'empresa_id' => $empresa->id_empresa,
                    'periodo' => '2',
                    'plazas' => 0
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
        $empresa = Empresa::with(['tutores', 'cupos'])->findOrFail($empresa_id);

        return view('gestion.empresas.edit', compact('empresa'));
    }

    /**
     * Almacena la actualización de los datos de la empresa.
     */
    public function updateEmpresa(Request $request, $empresa_id)
    {
        $empresa = Empresa::findOrFail($empresa_id);

        $request->validate([
            'cif_nif' => 'required|max:20|unique:empresas,cif_nif,' . $empresa_id . ',id_empresa',
            'nombre' => 'required|string|max:255',
            'nombre_gerente' => ['nullable', 'max:255'],
            'nif_gerente' => ['nullable', 'max:15'],
            'plazas' => 'array', // Validamos que llegue el array de plazas
        ]);

        // 1. Actualizar datos de la empresa
        $empresa->update([
            'cif_nif' => $request->cif_nif,
            'nombre' => $request->nombre,
            'nombre_gerente' => $request->nombre_gerente,
            'nif_gerente' => $request->nif_gerente,
        ]);

        // 2. Actualizar Cupos (Periodos 1 y 2) desde el formulario principal
        if ($request->has('plazas')) {
            foreach ($request->plazas as $periodo => $cantidad) {
                \App\Models\CupoEmpresa::updateOrCreate(
                    [
                        'empresa_id' => $empresa->id_empresa,
                        'periodo' => $periodo
                    ],
                    [
                        'plazas' => $cantidad
                    ]
                );
            }
        }

        return redirect()->route('gestion.empresas.index')->with('success', 'Datos de la empresa y cupos actualizados.');
    }

    /**
     * Método para modificar los alumnos por periodo de forma activa
     */
    public function updateCupo(Request $request, $empresa_id)
    {
        // Validamos que llegue el periodo (1 o 2) y las plazas
        $request->validate([
            'periodo' => 'required|in:1,2',
            'plazas' => 'required|integer|min:0'
        ]);

        try {
            // Buscamos o creamos el registro en la tabla 'cupos_empresas'
            CupoEmpresa::updateOrCreate(
                [
                    'empresa_id' => $empresa_id,
                    'periodo'    => $request->periodo
                ],
                [
                    'plazas'     => $request->plazas
                ]
            );

            return response()->json(['success' => true, 'message' => 'Plazas actualizadas correctamente.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina el perfil de Empresa, pero solo si ningún tutor asociado 
     * tiene alumnos en CUALQUIER base de datos de proyecto (finalizada o no).
     */
    public function destroyEmpresa($empresa_id)
    {
        // 1. Encontrar la Empresa (BD Galileo)
        $empresa = Empresa::findOrFail($empresa_id);
        
        // 2. Obtener todos los tutores asociados a esta empresa
        $tutores = $empresa->tutores;

        // --- 3. VALIDACIÓN DE INTEGRIDAD REFERENCIAL ---
        
        // Si hay tutores, debemos verificar que ninguno tenga alumnos asociados
        if ($tutores->isNotEmpty()) {
            
            // Obtener TODOS los proyectos (finalizados o no) para la verificación completa
            $proyectos = Proyecto::all(); 
            $config_base = config('database.connections.' . config('database.default'));
            
            $tutorConAlumno = null; 
            $proyectoConAlumno = null;

            foreach ($tutores as $tutor) {
                $tutor_id = $tutor->id_tutor_laboral;

                foreach ($proyectos as $proyecto) {
                    $conexion_proyecto_nombre = 'proyecto_temp_' . $proyecto->id_base_de_datos; 
                    
                    // Configurar la conexión dinámica temporal
                    $config_base['database'] = $proyecto->conexion;
                    config(["database.connections.{$conexion_proyecto_nombre}" => $config_base]);

                    // Forzar el modelo Alumno a usar la conexión dinámica actual
                    Alumno::getConnectionResolver()->setDefaultConnection($conexion_proyecto_nombre);
                    
                    // Buscar alumnos asociados en la BD del proyecto actual
                    $alumnosCount = Alumno::query()
                        ->where('tutor_laboral_id', $tutor_id)
                        ->count();
                    
                    if ($alumnosCount > 0) {
                        $tutorConAlumno = $tutor;
                        $proyectoConAlumno = $proyecto;
                        
                        // Restaurar conexión de Alumno a la principal antes de salir
                        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));
                        
                        // Salir de ambos bucles
                        break 2; 
                    }
                }
            }

            // Si se encontró un tutor con alumnos, retornar error
            if ($tutorConAlumno) {
                $nombre_tutor = $tutorConAlumno->nombre;
                $nombre_proyecto = $proyectoConAlumno->proyecto;
                
                return redirect()->back()->withErrors("No se puede eliminar la empresa **{$empresa->nombre}**. El tutor **{$nombre_tutor}** aún tiene alumno(s) asociado(s) en el proyecto **{$nombre_proyecto}**. Desvincula o elimina al alumno primero.");
            }
        }
        
        // --- 4. ELIMINACIÓN SEGURA ---
        try {
            DB::connection(config('database.default'))->transaction(function () use ($empresa, $tutores) {
                
                // 4a. Eliminar los usuarios asociados a los tutores (MorphOne no tiene CASCADE)
                foreach ($tutores as $tutor) {
                    $user = $tutor->user;
                    if ($user) {
                        $user->delete();
                    }
                }
                
                // 4b. Eliminar los perfiles de Tutor Laboral
                // Esto borrará todos los tutores
                $empresa->tutores()->delete();
                
                // 4c. Eliminar la Empresa
                $empresa->delete();
            });

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error crítico al eliminar la empresa: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Empresa, Tutores Laborales asociados y Usuarios eliminados con éxito.');
    }
}