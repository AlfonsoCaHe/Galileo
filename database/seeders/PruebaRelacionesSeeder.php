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
        $nombre_proyecto_bd = 'Proyecto_2024_2026';
        $conexion_proyecto = 'proyecto_2024_2026';
        $conexion_principal = config('database.default');

        // --- PARTE 1: CREACIÓN DE ENTIDADES CENTRALES (Profesor en BD Principal) ---
        
        DB::connection($conexion_principal)->statement('SET FOREIGN_KEY_CHECKS=0;');
        Profesor::truncate();
        $profesor = Profesor::create(['nombre' => 'Óscar Gómez López']);
        $this->command->info("Profesor '{$profesor->nombre}' creado en BD principal.");

        Empresa::truncate();
        $empresa = Empresa::create(['nombre' => 'TechSolutions S.L.', 'cif_nif' => 'B12345678', 'nombre_gerente' => 'Tomás López Bueso', 'nif_gerente' => '12345678A']);
        $this->command->info("Empresa '{$empresa->nombre}' creado en BD principal.");

        TutorLaboral::truncate();
        $tutor_laboral = TutorLaboral::create(['nombre' => 'Laura García', 'email' => 'laura@gmail.com', 'empresa_id' => $empresa->id_empresa]);
        $this->command->info("TutorLaboral '{$tutor_laboral->nombre}' creado en BD principal.");

        DB::connection($conexion_principal)->statement('SET FOREIGN_KEY_CHECKS=1;');
        // Asegurar que existe el metadato del proyecto
        $base_datos_meta = Proyecto::firstOrCreate(['proyecto' => $nombre_proyecto_bd], ['conexion' => 'Proyecto_2024_2026']);


        // --- PARTE 2: CONFIGURACIÓN Y ASIGNACIÓN DE CONEXIÓN DINÁMICA ---

        $config_base = config("database.connections.{$conexion_principal}");
        $config_base['database'] = $base_datos_meta->conexion;
        config(["database.connections.{$conexion_proyecto}" => $config_base]);

        // Asignar conexión dinámica a todos los modelos locales temporalmente
        $modelos_locales = [Modulo::class, Alumno::class, Tarea::class, Criterio::class, Ras::class];
        
        foreach ($modelos_locales as $modelClass) {
            // Llama al resolvedor estáticamente para cambiar la conexión por defecto de la clase
            $modelClass::getConnectionResolver()->setDefaultConnection($conexion_proyecto);
        }

        // --- PARTE 3: INSERCIÓN DE ENTIDADES LOCALES (BD del Proyecto) ---

        // Limpieza de la BD externa
        Schema::connection($conexion_proyecto)->disableForeignKeyConstraints();
        DB::connection($conexion_proyecto)->table('modulos')->truncate();
        DB::connection($conexion_proyecto)->table('profesor_modulo')->truncate();
        DB::connection($conexion_proyecto)->table('alumnos')->truncate(); 
        DB::connection($conexion_proyecto)->table('alumnos_modulos')->truncate();
        DB::connection($conexion_proyecto)->table('tareas')->truncate(); 
        DB::connection($conexion_proyecto)->table('ras')->truncate(); 
        DB::connection($conexion_proyecto)->table('criterios')->truncate();
        DB::connection($conexion_proyecto)->table('tareas_criterios')->truncate(); 
        
        
        // Crear Módulo
        $modulo = Modulo::create(['nombre' => 'Desarrollo Entorno Cliente']);
        

        // Relación Profesor (Central) a Módulo (Local) -> Usa el ID central
        DB::connection($conexion_proyecto)->table('profesor_modulo')->insert([
            'profesor_id' => $profesor->id_profesor,
            'modulo_id' => $modulo->id_modulo
        ]);

        // Crear Alumnos (Usando la conexión dinámica, referenciando IDs centrales y locales)
        $alumno1 = Alumno::create(['nombre' => 'Alumno Test 1', 'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral, 'tutor_docente_id' => $profesor->id_profesor]);
        $alumno2 = Alumno::create(['nombre' => 'Alumno Test 2', 'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral]);
        $alumno3 = Alumno::create(['nombre' => 'Alumno Test 3', 'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral]);

        // Asignar Alumnos a Módulo (Tabla Pivote: alumnos_modulos)
        DB::connection($conexion_proyecto)->table('alumnos_modulos')->insert([
            ['alumno_id' => $alumno1->id_alumno, 'modulo_id' => $modulo->id_modulo],
            ['alumno_id' => $alumno2->id_alumno, 'modulo_id' => $modulo->id_modulo],
            ['alumno_id' => $alumno3->id_alumno, 'modulo_id' => $modulo->id_modulo],
        ]);
        
        //Crear ras
        $ra1 = Ras::create([
            'nombre' => 'RA1. Descripción del proyecto'
        ]);

        //Crear criterios
        $cr1 = Criterio::create(['nombre' => 'cr1. creación de la base de datos', 
            'descripcion' => 'realizar los pasos necesarios para crear la BD', 
            'ras_id' => $ra1->id_ras]);
        $cr2 = Criterio::create(['nombre' => 'cr2. creación de las tablas', 
            'descripcion' => 'realizar los pasos necesarios para crear la BD', 
            'ras_id' => $ra1->id_ras]);

        //Creación de una tarea
        $tarea1 = Tarea::create(['actividad' => 'Crear una base de datos',
            'modulo_id' => $modulo->id_modulo,
            'alumno_id' => $alumno3->id_alumno,
            'apto' => false]);

        // Asignar Criterio a Tarea (Tabla Pivote: tareas_criterios)
        DB::connection($conexion_proyecto)->table('tareas_criterios')->insert([
            ['tarea_id' => $tarea1->id_tarea, 'criterio_id' => $cr1->id_criterio],
            ['tarea_id' => $tarea1->id_tarea, 'criterio_id' => $cr2->id_criterio]
        ]);

        Schema::connection($conexion_proyecto)->enableForeignKeyConstraints();

        $this->command->info("Entidades locales creadas en la BD de Proyecto ({$base_datos_meta->conexion}).");

        // --- PARTE 4: REVERTIR CONEXIONES ---
        foreach ($modelos_locales as $modelClass) {
            $modelClass::getConnectionResolver()->setDefaultConnection($conexion_principal);
        }


        //-----------------------------------Base de datos 2025_2027------------------------------------------

        $nombre_proyecto_bd = 'Proyecto_2025_2027';
        $conexion_proyecto = 'proyecto_2025_2027';

        //Creamos la empresa del segundo proyecto y su tutor laboral correspondiente
        $empresa = Empresa::create(['nombre' => 'Interfaces S.L.', 'cif_nif' => 'B87654321', 'nombre_gerente' => 'Carolina Moreno Montoya', 'nif_gerente' => '87654321A']);
        $tutor_laboral = TutorLaboral::create(['nombre' => 'Carolina Moreno Montoya', 'email' => 'carolina@gmail.com', 'empresa_id' => $empresa->id_empresa]);

        // Asegurar que existe el metadato del proyecto
        $base_datos_meta = Proyecto::firstOrCreate(['proyecto' => $nombre_proyecto_bd], ['conexion' => 'Proyecto_2025_2027']);

        // --- PARTE 2: CONFIGURACIÓN Y ASIGNACIÓN DE CONEXIÓN DINÁMICA ---

        $config_base = config("database.connections.{$conexion_principal}");
        $config_base['database'] = $base_datos_meta->conexion;
        config(["database.connections.{$conexion_proyecto}" => $config_base]);

        // Asignar conexión dinámica a todos los modelos locales temporalmente
        $modelos_locales = [Modulo::class, Alumno::class, Tarea::class, Criterio::class, Ras::class];
        
        foreach ($modelos_locales as $modelClass) {
            // CORRECCIÓN: Llamar al resolvedor estáticamente para cambiar la conexión por defecto de la clase
            $modelClass::getConnectionResolver()->setDefaultConnection($conexion_proyecto);
        }

        // --- PARTE 3: INSERCIÓN DE ENTIDADES LOCALES (BD del Proyecto) ---

        // Limpieza de la BD externa
        Schema::connection($conexion_proyecto)->disableForeignKeyConstraints();
        DB::connection($conexion_proyecto)->table('modulos')->truncate();
        DB::connection($conexion_proyecto)->table('profesor_modulo')->truncate();
        DB::connection($conexion_proyecto)->table('alumnos')->truncate(); 
        DB::connection($conexion_proyecto)->table('alumnos_modulos')->truncate();
        DB::connection($conexion_proyecto)->table('tareas')->truncate(); 
        DB::connection($conexion_proyecto)->table('ras')->truncate(); 
        DB::connection($conexion_proyecto)->table('criterios')->truncate();
        DB::connection($conexion_proyecto)->table('tareas_criterios')->truncate();
        
        $alumno2 = Alumno::create(['nombre' => 'Alumno Test 5', 'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral]);
        
        // Crear Módulo (Usando la conexión dinámica) y profesor nuevo (Usando la conexión principal)
        $modulo = Modulo::create(['nombre' => 'Diseño de Interfaces Web']);
        $profesor = Profesor::create(['nombre' => 'Gustavo Santamaría Olalla']);
        // Relación Profesor (Central) a Módulo (Local) -> Usa el ID central
        DB::connection($conexion_proyecto)->table('profesor_modulo')->insert([
            'profesor_id' => $profesor->id_profesor,
            'modulo_id' => $modulo->id_modulo
        ]);

        // Crear Alumnos (Usando la conexión dinámica, referenciando IDs centrales y locales)
        $alumno1 = Alumno::create(['nombre' => 'Alumno Test 4', 'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral, 'tutor_docente_id' => $profesor->id_profesor]);
        
        $alumno3 = Alumno::create(['nombre' => 'Alumno Test 6', 'tutor_laboral_id' => $tutor_laboral->id_tutor_laboral]);

        // Asignar Alumnos a Módulo (Tabla Pivote: alumnos_modulos)
        DB::connection($conexion_proyecto)->table('alumnos_modulos')->insert([
            ['alumno_id' => $alumno1->id_alumno, 'modulo_id' => $modulo->id_modulo],
            ['alumno_id' => $alumno2->id_alumno, 'modulo_id' => $modulo->id_modulo],
            ['alumno_id' => $alumno3->id_alumno, 'modulo_id' => $modulo->id_modulo],
        ]);

        Schema::connection($conexion_proyecto)->enableForeignKeyConstraints();

        $this->command->info("Entidades locales creadas en la BD de Proyecto ({$base_datos_meta->conexion}).");

        // --- PARTE 4: REVERTIR CONEXIONES ---
        foreach ($modelos_locales as $modelClass) {
            $modelClass::getConnectionResolver()->setDefaultConnection($conexion_principal);
        }

        // --- PARTE 5: CREACIÓN DE PERFILES DE USUARIO ---
        $this->command->info('Creando perfiles de usuario...');
        User::create([
            'name' => 'Alumno Proyecto',
            'email' => 'alumno@ies.galileo.com',
            'password' => 'alumno',
            'rol' => 'alumno',
        ]);

        $this->command->info('Usuario Alumno Creado: alumno@ies.galileo.com / alumno');

        User::create([
            'name' => 'Profesor Proyecto',
            'email' => 'profesor@ies.galileo.com',
            'password' => 'profesor',
            'rol' => 'profesor',
        ]);

        $this->command->info('Usuario Profesor Creado: profesor@ies.galileo.com / profesor');

        User::create([
            'name' => 'Tutor Laboral Proyecto',
            'email' => 'tutor_laboral@ies.galileo.com',
            'password' => 'tutor',
            'rol' => 'tutor_laboral',
        ]);

        $this->command->info('Usuario Tutor Laboral Creado: tutor_laboral@ies.galileo.com / tutor');

        $this->command->info("\n--- Prueba de Arquitectura Híbrida Completa ---");
    }
}