<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Profesor;
use App\Models\Modulo;
use App\Models\Empresa;
use App\Models\TutorLaboral;
use App\Models\Alumno;
use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruebaRelacionesSeeder extends Seeder
{
    public function run(): void
    {
        $nombre_proyecto_bd = 'Proyecto_2024_2026';
        $conexion_alumnos = 'proyecto_2024_2026';
        $conexion_principal = config('database.default');

        // --- PARTE 1: CREACIÓN DE ENTIDADES CENTRALES (Profesor en BD Principal) ---
        
        DB::connection($conexion_principal)->statement('SET FOREIGN_KEY_CHECKS=0;');
        Profesor::truncate();
        $profesor = Profesor::create(['nombre' => 'Óscar Gómez López']);
        DB::connection($conexion_principal)->statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->command->info("Profesor '{$profesor->nombre}' creado en BD principal.");

        // Asegurar que existe el metadato del proyecto
        $base_datos_meta = Proyecto::firstOrCreate(['proyecto' => $nombre_proyecto_bd], ['conexion' => 'Proyecto_2024_2026']);


        // --- PARTE 2: CONFIGURACIÓN Y ASIGNACIÓN DE CONEXIÓN DINÁMICA ---

        $config_base = config("database.connections.{$conexion_principal}");
        $config_base['database'] = $base_datos_meta->conexion;
        config(["database.connections.{$conexion_alumnos}" => $config_base]);

        // Asignar conexión dinámica a todos los modelos locales temporalmente
        $modelos_locales = [Modulo::class, Empresa::class, TutorLaboral::class, Alumno::class];
        
        foreach ($modelos_locales as $modelClass) {
            // CORRECCIÓN: Llamar al resolvedor estáticamente para cambiar la conexión por defecto de la clase
            $modelClass::getConnectionResolver()->setDefaultConnection($conexion_alumnos);
        }

        // --- PARTE 3: INSERCIÓN DE ENTIDADES LOCALES (BD del Proyecto) ---

        // Limpieza de la BD externa
        Schema::connection($conexion_alumnos)->disableForeignKeyConstraints();
        DB::connection($conexion_alumnos)->table('modulos')->truncate();
        DB::connection($conexion_alumnos)->table('empresas')->truncate();
        DB::connection($conexion_alumnos)->table('tutores_laborales')->truncate();
        DB::connection($conexion_alumnos)->table('profesor_modulo')->truncate();
        DB::connection($conexion_alumnos)->table('alumnos')->truncate(); 
        DB::connection($conexion_alumnos)->table('alumnos_modulos')->truncate();
        
        
        // Crear Módulo, Empresa, Tutor Laboral (Usando la conexión dinámica)
        $modulo = Modulo::create(['nombre' => 'Desarrollo Web Entorno Cliente']);
        $empresa = Empresa::create(['nombre' => 'TechSolutions S.L.', 'cif_nif' => 'B12345678', 'nombre_gerente' => 'Tomás López Bueso', 'nif_gerente' => '12345678A']);
        $tutor_laboral = TutorLaboral::create(['nombre' => 'Laura García', 'email' => 'laura@gmail.com', 'empresa_id' => $empresa->id_empresa]);

        // Relación Profesor (Central) a Módulo (Local) -> Usa el ID central
        DB::connection($conexion_alumnos)->table('profesor_modulo')->insert([
            'profesor_id' => $profesor->id_profesor,
            'modulo_id' => $modulo->id_modulo
        ]);

        // Crear Alumnos (Usando la conexión dinámica, referenciando IDs centrales y locales)
        $alumno1 = Alumno::create(['nombre' => 'Alumno Test 1', 'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral, 'tutor_docente_id' => $profesor->id_profesor]);
        $alumno2 = Alumno::create(['nombre' => 'Alumno Test 2', 'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral]);
        $alumno3 = Alumno::create(['nombre' => 'Alumno Test 3', 'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral]);

        // Asignar Alumnos a Módulo (Tabla Pivote: alumnos_modulos)
        DB::connection($conexion_alumnos)->table('alumnos_modulos')->insert([
            ['alumno_id' => $alumno1->id_alumno, 'modulo_id' => $modulo->id_modulo],
            ['alumno_id' => $alumno2->id_alumno, 'modulo_id' => $modulo->id_modulo],
            ['alumno_id' => $alumno3->id_alumno, 'modulo_id' => $modulo->id_modulo],
        ]);

        Schema::connection($conexion_alumnos)->enableForeignKeyConstraints();

        $this->command->info("Entidades locales creadas en la BD de Proyecto ({$base_datos_meta->conexion}).");

        // --- PARTE 4: REVERTIR CONEXIONES ---
        foreach ($modelos_locales as $modelClass) {
            $modelClass::getConnectionResolver()->setDefaultConnection($conexion_principal);
        }

        $this->command->info("\n--- Prueba de Arquitectura Híbrida Completa ---");
    }
}