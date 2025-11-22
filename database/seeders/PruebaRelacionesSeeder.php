<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Profesor;
use App\Models\Modulo;
use App\Models\Empresa;
use App\Models\TutorLaboral;
use App\Models\Alumno;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\Criterio;
use App\Models\Ras;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruebaRelacionesSeeder extends Seeder
{
    public function run(): void
    {
        // -----------------------------------------------------------------------
        // --- PREPARACIÓN DE CONEXIONES Y CONSTANTES
        // -----------------------------------------------------------------------
        $nombre_proyecto_bd_1 = 'Proyecto_2024_2026';
        $conexion_proyecto_1 = 'proyecto_2024_2026';
        
        $nombre_proyecto_bd_2 = 'Proyecto_2025_2027'; // Nuevo Proyecto
        $conexion_proyecto_2 = 'proyecto_2025_2027'; // Nueva Conexión
        
        $conexion_principal = config('database.default');
        
        // Modelos que usan la conexión dinámica (local)
        $modelos_locales = [Alumno::class, Modulo::class, Tarea::class, Criterio::class, Ras::class];

        // --- PARTE 1: CREACIÓN DE ENTIDADES CENTRALES (En BD Principal) ---
        
        DB::connection($conexion_principal)->statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Truncar y crear Profesor y Empresa (Asumo que estas tablas no están en el mismo Schema)
        // Profesor::truncate(); // Asumo que el modelo Profesor usa la BD principal
        $profesor = Profesor::create(['nombre' => 'Óscar Gómez López']);
        $this->command->info("Profesor '{$profesor->nombre}' creado en BD principal.");

        // Empresa::truncate(); // Asumo que el modelo Empresa usa la BD principal
        $empresa = Empresa::create(['nombre' => 'TechSolutions S.L.', 'cif_nif' => 'B12345678', 'nombre_gerente' => 'Tomás López Bueso', 'nif_gerente' => '12345678A']);
        $this->command->info("Empresa '{$empresa->nombre}' creada.");
        
        // TutorLaboral::truncate(); // Asumo que el modelo TutorLaboral usa la BD principal
        $tutor_laboral = TutorLaboral::create(['nombre' => 'Fernando García', 'email' => 'fernando.garcia@techsolutions.com', 'empresa_id' => $empresa->id_empresa]);
        $this->command->info("Tutor Laboral '{$tutor_laboral->nombre}' creado.");

        // Crear registros de Proyecto
        Proyecto::truncate();
        $base_datos_meta_1 = Proyecto::create(['proyecto' => $nombre_proyecto_bd_1, 'conexion' => $conexion_proyecto_1, 'finalizado' => 0]);
        $base_datos_meta_2 = Proyecto::create(['proyecto' => $nombre_proyecto_bd_2, 'conexion' => $conexion_proyecto_2, 'finalizado' => 0]);
        
        $bases_de_datos_meta = [$base_datos_meta_1, $base_datos_meta_2];
        DB::connection($conexion_principal)->statement('SET FOREIGN_KEY_CHECKS=1;');

        // --- PARTE 2: CREACIÓN DE ENTIDADES LOCALES (En BD de Proyectos) ---
        
        foreach ($bases_de_datos_meta as $proyecto_meta) {
            $conexion_nombre = $proyecto_meta->conexion;

            // 1. Configurar la conexión dinámica para el proyecto actual
            $config_base = config("database.connections.{$conexion_principal}");
            $config_base['database'] = $conexion_nombre;
            config(["database.connections.{$conexion_nombre}" => $config_base]);

            // 2. Truncar las tablas locales (esencial para la limpieza)
            Schema::connection($conexion_nombre)->disableForeignKeyConstraints();
            foreach ($modelos_locales as $modelClass) {
                // Forzar el modelo a usar la conexión dinámica
                $modelClass::getConnectionResolver()->setDefaultConnection($conexion_nombre);
                // Truncar la tabla
                $modelClass::truncate();
            }

            // 3. Crear Alumno
            $alumno = Alumno::create([
                'nombre' => "Alumno de {$proyecto_meta->proyecto}",
                'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral,
                'tutor_docente_id' => $profesor->id_profesor,
                'database_id' => $proyecto_meta->id_base_de_datos,
            ]);

            // LÓGICA DE MÓDULOS, RAS, CRITERIOS Y TAREAS (CORREGIDA)
            // ------------------------------------------------------------------------
            
            // Crear Módulo
            $modulo = Modulo::create([
                'nombre' => "Módulo FCT - {$proyecto_meta->proyecto}",
            ]);

            // 1. Crear RAS y ASIGNAR modulo_id (Relación 1:N)
            $ras1 = Ras::create([
                'nombre' => "RAS 1 - Diseño de Interfaces. {$proyecto_meta->proyecto}",
                'modulo_id' => $modulo->id_modulo, // ¡CORREGIDO!
            ]);

            // 2. Crear Criterio y asignar ras_id (Relación 1:N)
            $criterio1 = Criterio::create([
                'descripcion' => "Criterio 1.1: Uso de buenas prácticas. {$proyecto_meta->proyecto}",
                'ras_id' => $ras1->id_ras,
            ]);
            
            // 3. Crear Tarea (corregido el campo 'nombre' a 'actividad')
            $tarea = Tarea::create([
                'actividad' => "Implementación de frontend - {$proyecto_meta->proyecto}",
                'modulo_id' => $modulo->id_modulo,
                'alumno_id' => $alumno->id_alumno,
            ]);
            
            // 4. Enlazar el Módulo al Alumno (Relación N:M)
            // Esto usa la tabla pivot alumnos_modulos
            $alumno->modulos()->attach($modulo->id_modulo); 
            
            // 5. Enlazar el Criterio a la Tarea (Relación N:M)
            // Esto usa la tabla pivot tareas_criterios
            $tarea->criterios()->attach($criterio1->id_criterio); 

            // ------------------------------------------------------------------------
            
            Schema::connection($conexion_nombre)->enableForeignKeyConstraints();

            $this->command->info("Entidades locales creadas en la BD de Proyecto ({$proyecto_meta->conexion}).");

            // --- PARTE 3: REVERTIR CONEXIONES (LIMPIEZA) ---
            foreach ($modelos_locales as $modelClass) {
                $modelClass::getConnectionResolver()->setDefaultConnection($conexion_principal);
            }
        }

        // --- PARTE 4: CREACIÓN DE PERFILES DE USUARIO (En BD Principal) ---
        $this->command->info('Creando perfiles de usuario...');
        
        // Asumo que tienes una forma de crear usuarios en la BD principal
        // y de enlazar el 'Alumno' (que está en la BD de Proyecto) al 'User' (BD Principal)
        // Usaré los IDs que creaste para el primer proyecto de ejemplo.
        User::createRolableUser($tutor_laboral, [
            'name' => $tutor_laboral->nombre,
            'email' => $tutor_laboral->email,
            'password' => 'tutor',
            'rol' => 'tutor_laboral',
        ]);
        $this->command->info('Usuario Tutor Laboral Creado: tutor_laboral@ies.galileo.com / tutor');

        // Los usuarios Alumno y Profesor no se crean aquí si el modelo User usa BD principal
        // y los modelos de rol están en BD de proyecto. Este código requiere revisar tu lógica
        // de autenticación polimórfica, pero lo mantengo simplificado.

        $this->command->info('Proceso de Seeding finalizado con la nueva estructura de RAS 1:N.');
    }
}