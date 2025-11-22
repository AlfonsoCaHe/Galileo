<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Profesor;
use App\Models\Modulo;
use App\Models\Empresa;
use App\Models\TutorLaboral;
use App\Models\Alumno;
use App\Models\Proyecto; // Se refiere a la tabla 'bases_de_datos'
use App\Models\Tarea;
use App\Models\Criterio;
use App\Models\Ras;
use App\Models\User;
use App\Support\ProjectSchemaManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruebaRelacionesSeeder extends Seeder
{
    public function run(): void
    {
        // -----------------------------------------------------------------------
        // --- PREPARACIÓN DE CONEXIONES Y CONSTANTES
        // -----------------------------------------------------------------------
        $conexion_principal = config('database.default');
        
        $proyectos_meta = [
            (object)['nombre' => 'Proyecto_2024_2026', 'conexion' => 'proyecto_2024_2026'],
            (object)['nombre' => 'Proyecto_2025_2027', 'conexion' => 'proyecto_2025_2027'],
        ];
        
        $modelos_locales = [Alumno::class, Modulo::class, Tarea::class, Criterio::class, Ras::class];
        
        // -----------------------------------------------------------------------
        // --- PARTE 1: CREACIÓN DE ENTIDADES CENTRALES (En BD Principal: Galileo)
        // -----------------------------------------------------------------------
        
        DB::connection($conexion_principal)->statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Limpieza y Creación de Entidades Principales
        Profesor::truncate();
        $profesor = Profesor::create(['nombre' => 'Óscar Gómez López']);
        $this->command->info("Profesor '{$profesor->nombre}' creado en BD principal.");

        Empresa::truncate();
        $empresa = Empresa::create(['nombre' => 'TechSolutions S.L.', 'cif_nif' => 'B12345678', 'nombre_gerente' => 'Tomás López Bueso', 'nif_gerente' => '12345678A']);
        $this->command->info("Empresa '{$empresa->nombre}' creada.");
        
        TutorLaboral::truncate();
        $tutor_laboral = TutorLaboral::create([
            'nombre' => 'María Fernández Ruiz', 
            'email' => 'maria.fernandez@tech.com',
            'empresa_id' => $empresa->id_empresa
        ]);
        $this->command->info("Tutor Laboral '{$tutor_laboral->nombre}' creado.");

        User::truncate();
        Proyecto::truncate();
        
        DB::connection($conexion_principal)->statement('SET FOREIGN_KEY_CHECKS=1;');

        // -----------------------------------------------------------------------
        // --- BUCLE PARA CADA PROYECTO (BD DINÁMICA)
        // -----------------------------------------------------------------------
        
        foreach ($proyectos_meta as $proyecto_meta) {
            $conexion_proyecto = $proyecto_meta->conexion;

            // 1. REGISTRO DEL PROYECTO EN BD PRINCIPAL
            Proyecto::create([
                'proyecto' => $proyecto_meta->nombre,
                'conexion' => $conexion_proyecto,
                'finalizado' => false,
            ]);
            $this->command->info("Registro de proyecto '{$proyecto_meta->nombre}' añadido a la tabla 'Proyectos'.");
            
            // 2. CAMBIAR LA CONEXIÓN DE LOS MODELOS LOCALES
            foreach ($modelos_locales as $modelClass) {
                $modelClass::getConnectionResolver()->setDefaultConnection($conexion_proyecto);
            }

            // 3. DEFINICIÓN DEL ESQUEMA Y CREACIÓN DE TABLAS
            $this->command->info("Creando/Reiniciando tablas para '{$conexion_proyecto}'...");
            
            // Eliminamos 'modulo_ras' de la lista, ya que es redundante para la relación 1:N Modulo->RAS
            $tablas_locales = [
                'tareas_criterios', 'profesor_modulo', 
                'alumnos_modulos', 'criterios', 'ras', 
                'tareas', 'alumnos', 'modulos',
            ];
            
            Schema::connection($conexion_proyecto)->disableForeignKeyConstraints();
            ProjectSchemaManager::dropAllTables($tablas_locales);

            // Crear las tablas del esquema local
            ProjectSchemaManager::createModulosTable();
            ProjectSchemaManager::createAlumnosTable();
            ProjectSchemaManager::createRasTable(); // La tabla RAS debe tener modulo_id (1:N)
            ProjectSchemaManager::createCriteriosTable();
            ProjectSchemaManager::createTareasTable();
            ProjectSchemaManager::createTareasCriteriosTable();
            ProjectSchemaManager::createAlumnosModuloTable();
            ProjectSchemaManager::createProfesorModuloTable(); 
            
            Schema::connection($conexion_proyecto)->enableForeignKeyConstraints();
            $this->command->info("Esquema local creado con éxito en '{$conexion_proyecto}'.");

            // 4. CREACIÓN DE DATOS DE PRUEBA Y RELACIONES

            $alumno_nombre = ($proyecto_meta->nombre === 'Proyecto_2025_2027') ? 'Alumno B' : 'Alumno A';
            $modulo_nombre = "Módulo FCT - {$proyecto_meta->nombre}";

            $alumno = Alumno::create([
                'nombre' => $alumno_nombre,
                'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral,
                'tutor_docente_id' => $profesor->id_profesor,
            ]);
            $this->command->info("Alumno '{$alumno->nombre}' creado en BD de Proyecto.");
            
            $modulo = Modulo::create([
                'nombre' => $modulo_nombre,
            ]);
            $this->command->info("Módulo '{$modulo->nombre}' creado.");

            // CREACIÓN DE RAS: Se asigna la FK (modulo_id) aquí (1:N)
            $ras1 = Ras::create([
                'nombre' => "RAS-1: Diseño de Interfaces. ({$proyecto_meta->nombre})",
                'modulo_id' => $modulo->id_modulo, 
            ]);
            $this->command->info("RAS '{$ras1->nombre}' creado.");

            // CREACIÓN DE CRITERIO: Se asigna la FK (ras_id) aquí (1:N)
            $criterio1 = Criterio::create([
                'descripcion' => "Criterio 1.1: Uso de buenas prácticas. ({$proyecto_meta->nombre})",
                'ras_id' => $ras1->id_ras,
            ]);
            $this->command->info("Criterio '{$criterio1->descripcion}' creado.");

            $tarea = Tarea::create([
                'actividad' => "Implementación de frontend en {$proyecto_meta->nombre}.",
                'modulo_id' => $modulo->id_modulo,
                'alumno_id' => $alumno->id_alumno,
                'apto' => false,
            ]);
            $this->command->info("Tarea '{$tarea->actividad}' creada.");

            // --- RELACIONES PIVOTE (N:M) ---
            
            // [IMPORTANTE] Eliminamos el attach() para Modulo <-> RAS porque es 1:N
            // La relación 1:N ya fue creada en Ras::create()

            // Las siguientes DEBEN ser N:M (usando attach)
            $modulo->profesores()->attach($profesor->id_profesor);
            $alumno->modulos()->attach($modulo->id_modulo);
            $tarea->criterios()->attach($criterio1->id_criterio);
            
            $this->command->info("Relaciones Pivote (N:M) para {$proyecto_meta->nombre} creadas.");
            
            // 5. REVERTIR CONEXIONES
            foreach ($modelos_locales as $modelClass) {
                $modelClass::getConnectionResolver()->setDefaultConnection($conexion_principal);
            }
        }

        // -----------------------------------------------------------------------
        // --- PARTE 5: CREACIÓN DE PERFILES DE USUARIO (En BD Principal)
        // -----------------------------------------------------------------------
        
        $this->command->info('Creando perfiles de usuario y vínculos rolable...');

        // 1. Obtener Alumno del primer proyecto (Modelo local, cambiar conexión temporalmente)
        $alumno_2024_2026 = null;
        try {
            // Se debe establecer la conexión al proyecto para buscar el alumno
            Alumno::getConnectionResolver()->setDefaultConnection($proyectos_meta[0]->conexion);
            $alumno_2024_2026 = Alumno::where('nombre', 'Alumno A')->first();
            
            // Revertir la conexión inmediatamente
            Alumno::getConnectionResolver()->setDefaultConnection($conexion_principal);
        } catch (\Exception $e) {
            $this->command->error("No se pudo obtener el Alumno A: " . $e->getMessage());
        }

        // 2. Crear Usuarios y Vincular roles (En la BD Principal)
        
        // --- Perfil: Profesor ---
        User::create([
            'name' => $profesor->nombre,
            'email' => 'profesor@ies.galileo.com',
            'password' => 'profesor',
            'rol' => 'profesor',
            // Asumiendo que el User Model vincula 'rol_id' y 'rol_type'
            'rolable_id' => $profesor->id_profesor,
            'rolable_type' => Profesor::class, 
        ]);
        
        // --- Perfil: Tutor Laboral ---
        User::create([
            'name' => $tutor_laboral->nombre,
            'email' => 'tutor_laboral@ies.galileo.com',
            'password' => 'tutor',
            'rol' => 'tutor_laboral',
            'rolable_id' => $tutor_laboral->id_tutor_laboral,
            'rolable_type' => TutorLaboral::class, 
        ]);
        
        // --- Perfil: Alumno ---
        if ($alumno_2024_2026) {
            User::create([
                'name' => $alumno_2024_2026->nombre,
                'email' => 'alumno@ies.galileo.com',
                'password' => 'alumno',
                'rol' => 'alumno',
                'rolable_id' => $alumno_2024_2026->id_alumno,
                'rolable_type' => Alumno::class, 
            ]);
            $this->command->info('Usuario Alumno Creado y vinculado al Proyecto_2024_2026.');
        } else {
             $this->command->warn('ADVERTENCIA: Usuario Alumno no creado por falta del modelo de rol.');
        }
        
        // --- Perfil: Admin (Sin vínculo rolable) ---
        User::create([
            'name' => 'Admin Proyecto',
            'email' => 'admin@ies.galileo.com',
            'password' => 'root',
            'rol' => 'admin',
        ]);
        
        $this->command->info('Usuarios de prueba y vínculos rolable creados en BD principal.');
        $this->command->info('--- Configuración de Entorno de Pruebas Finalizada ---');
    }
}