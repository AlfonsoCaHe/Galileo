<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Profesor;
use App\Models\Modulo;
use App\Models\Empresa;
use App\Models\TutorLaboral;
use App\Models\Alumno;
use App\Models\Proyecto; // Importar el modelo Proyecto
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruebaRelacionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // El nombre del proyecto (BD externa) donde irán los alumnos
        $nombre_proyecto_bd = 'proyecto_2025_2027'; 

        // --- 1. CONFIGURACIÓN DE METADATOS (Base de datos principal) ---

        // Desactivar la comprobación de claves foráneas temporalmente en la BD principal
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Limpiar las tablas de metadatos (opcional, pero ayuda en la prueba)
        DB::table('profesor_modulo')->truncate();
        Profesor::truncate();
        Modulo::truncate();
        Empresa::truncate();
        TutorLaboral::truncate();

        // 1.a. Crear Profesor
        $profesor = Profesor::create([
            'nombre' => 'Óscar Gómez López'
        ]);
        $this->command->info("Profesor '{$profesor->nombre}' creado en BD principal.");

        // 1.b. Crear Módulo
        $modulo = Modulo::create([
            'nombre' => 'Desarrollo Web Entorno Cliente'
        ]);
        $this->command->info("Módulo '{$modulo->nombre}' creado en BD principal.");

        // 1.c. Asignar Profesor a Módulo (Tabla Pivote: profesor_modulo)
        $profesor->modulos()->attach($modulo->id_modulo);
        $this->command->info("Relación Profesor-Módulo creada en BD principal.");

        // 1.d. Crear Empresa y Tutor Laboral
        $empresa = Empresa::create([
            'nombre' => 'TechSolutions S.L.',
            'cif_nif' => 'B12345678',
            'nombre_gerente' => 'Tomás López Bueso',
            'nif_gerente' => '12345678A'
        ]);

        $tutor_laboral = TutorLaboral::create([
            'nombre' => 'Laura García',
            'empresa_id' => $empresa->id_empresa
        ]);
        $this->command->info("Empresa/Tutor Laboral creados en BD principal.");
        
        // Reactivar la comprobación de FKs en la BD principal
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        // --- 2. CONFIGURACIÓN DE CONEXIÓN DINÁMICA (BD del Proyecto) ---

        $base_datos_meta = Proyecto::where('proyecto', $nombre_proyecto_bd)->first();

        if (!$base_datos_meta) {
            $this->command->error("ERROR: El proyecto '{$nombre_proyecto_bd}' no fue encontrado. Abortando.");
            return;
        }

        $conexion_alumnos = 'proyecto_dinamico_seed';
        $config_base = config('database.connections.' . config('database.default'));
        $config_base['database'] = $base_datos_meta->conexion;

        config(["database.connections.{$conexion_alumnos}" => $config_base]);
        
        
        // --- 3. INSERCIÓN DE DATOS DE ALUMNO (Base de datos del Proyecto) ---

        // Usamos withoutEvents para simplificar y setConnection para forzar la BD
        Alumno::withoutEvents(function () use ($conexion_alumnos, $profesor, $tutor_laboral, $modulo) {
            
            // Asignar la conexión temporal al modelo Alumno
            Alumno::setConnection($conexion_alumnos);
            
            // Limpiar las tablas de la BD externa
            Schema::connection($conexion_alumnos)->disableForeignKeyConstraints();
            DB::connection($conexion_alumnos)->table('alumnos')->truncate(); 
            DB::connection($conexion_alumnos)->table('alumnos_modulos')->truncate();
            Schema::connection($conexion_alumnos)->enableForeignKeyConstraints();

            // 3.a. Crear Alumnos
            
            // Alumno 1: Tendrá el Profesor como tutor docente
            $alumno1 = Alumno::create([
                'nombre' => 'Alumno Test 1 (Tutorizado)',
                'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral, 
                'tutor_docente_id' => $profesor->id_profesor, // Asignación directa
            ]);

            // Alumno 2 y 3: Sin tutor docente asignado (tutor_docente_id es nullable)
            $alumno2 = Alumno::create([
                'nombre' => 'Alumno Test 2',
                'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral,
                // tutor_docente_id se omite
            ]);
            
            $alumno3 = Alumno::create([
                'nombre' => 'Alumno Test 3', 
                'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral
                // tutor_docente_id se omite
            ]);

            // 3.b. Asignar los 3 alumnos al Módulo (Tabla Pivote: alumnos_modulos)
            // Ya que Alumno tiene la conexión dinámica, el método attach() usará esa conexión
            $modulo->alumnos()->for($conexion_alumnos)->attach([
                $alumno1->id_alumno, 
                $alumno2->id_alumno, 
                $alumno3->id_alumno
            ]);
            
            $this->command->info("3 Alumnos creados y asignados al módulo en la BD externa: {$conexion_alumnos}");

            // Es fundamental devolver la conexión al estado por defecto
            Alumno::setConnection(config('database.default'));
        });


        $this->command->info("\n--- Prueba de Relaciones Completa ---");
        $this->command->info("Verificación: Alumno 1 tiene asignado al Tutor Docente: {$profesor->nombre}");
    }
}
