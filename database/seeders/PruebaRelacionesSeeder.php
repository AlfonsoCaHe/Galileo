<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Profesor;
use App\Models\Empresa;
use App\Models\TutorLaboral;
use App\Models\Proyecto;
use App\Models\Modulo;
use App\Models\Ras;
use App\Models\Criterio;
use App\Models\Alumno;
use App\Models\Tarea;

class PruebaRelacionesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Iniciando...");

        // ==========================================
        // 1. BD PRINCIPAL (GALILEO)
        // ==========================================
        
        // A. Profesor
        $profesor = Profesor::firstOrCreate(
            ['nombre' => 'Juan Profesor'],
            ['activo' => true]
        );

        $emailProfesor = 'profesor@galileo.com';
        if (!User::where('email', $emailProfesor)->exists()) {
            User::createRolableUser($profesor, [
                'name' => $profesor->nombre,
                'email' => $emailProfesor,
                'password' => 'password',
                'rol' => 'profesor'
            ]);
        }

        // B. Empresa (Solo campos de la migración)
        $empresa = Empresa::firstOrCreate(
            ['cif_nif' => 'B12345678'],
            [
                'nombre' => 'Tech Solutions S.L.',
                'nombre_gerente' => 'Elon Musk',
                'nif_gerente' => '12345678Z'
            ]
        );

        // C. Tutor Laboral (Solo campos de la migración)
        $tutorLaboral = TutorLaboral::firstOrCreate(
            ['dni' => '87654321X'],
            [
                'nombre' => 'Ana Tutora',
                'email' => 'tutor@techsolutions.com',
                'empresa_id' => $empresa->id_empresa
            ]
        );

        if (!User::where('email', $tutorLaboral->email)->exists()) {
            User::createRolableUser($tutorLaboral, [
                'name' => $tutorLaboral->nombre,
                'email' => $tutorLaboral->email,
                'password' => 'password',
                'rol' => 'tutor_laboral'
            ]);
        }

        // ==========================================
        // 2. BD DINÁMICA (PROYECTOS)
        // ==========================================
        
        $proyectos = Proyecto::all();

        if ($proyectos->isEmpty()) {
            $this->command->error("AVISO: No hay proyectos. Ejecuta 'php artisan app:instalar' primero.");
            return;
        }

        foreach ($proyectos as $proyecto) {
            $this->poblarProyecto($proyecto, $profesor, $tutorLaboral);
        }
    }

    private function poblarProyecto(Proyecto $proyecto, Profesor $profesor, TutorLaboral $tutorLaboral)
    {
        $this->command->info("INFO: Poblando Proyecto: {$proyecto->proyecto}");

        // Configurar conexión dinámica
        $nombreConexion = 'temp_seeder_' . $proyecto->id_base_de_datos;
        Config::set("database.connections.{$nombreConexion}", array_merge(
            config('database.connections.mysql'),
            ['database' => $proyecto->conexion]
        ));
        DB::purge($nombreConexion);

        // 1. Módulos
        $moduloDWES = Modulo::on($nombreConexion)->create([
            'nombre' => 'Desarrollo Web (DWES)',
            'proyecto_id' => $proyecto->id_base_de_datos
        ]);

        // 2. Asignar Profesor a Módulo
        // Tabla: profesor_modulo (según ProjectSchemaManager línea 102)
        DB::connection($nombreConexion)->table('profesor_modulo')->insertOrIgnore([
            ['profesor_id' => $profesor->id_profesor, 'modulo_id' => $moduloDWES->id_modulo],
        ]);

        // 3. RAs y Criterios
        $ra1 = Ras::on($nombreConexion)->create([
            'modulo_id' => $moduloDWES->id_modulo,
            'codigo' => 'RA1',
            'descripcion' => 'Desarrolla aplicaciones web.'
        ]);

        $criterioA = Criterio::on($nombreConexion)->create([
            'ras_id' => $ra1->id_ras,
            'ce' => 'a)',
            'descripcion' => 'Elección de herramientas.'
        ]);

        // 4. Alumno
        // Tabla alumnos: id_alumno, nombre, periodo, tutor_laboral_id, tutor_docente_id
        // NO TIENE EMAIL NI DNI
        $alumno1 = Alumno::on($nombreConexion)->create([
            'nombre' => 'Carlos Estudiante',
            'periodo' => 'Septiembre',
            'tutor_docente_id' => $profesor->id_profesor,
            'tutor_laboral_id' => $tutorLaboral->id_tutor_laboral
        ]);

        // Crear usuario en BD Principal para el alumno
        // Usamos create() normal porque la relación polimórfica cruza bases de datos
        if (!User::where('email', 'alumno1@galileo.com')->exists()) {
             User::create([
                 'name' => $alumno1->nombre,
                 'email' => 'alumno1@galileo.com',
                 'password' => Hash::make('password'),
                 'rol' => 'alumno',
                 'rolable_id' => $alumno1->id_alumno,
                 'rolable_type' => Alumno::class, 
             ]);
        }

        // 5. Matriculación
        // Tabla: alumno_modulo (según ProjectSchemaManager línea 92 - PLURAL)
        DB::connection($nombreConexion)->table('alumno_modulo')->insertOrIgnore([
            [
                'alumno_id' => $alumno1->id_alumno, 
                'modulo_id' => $moduloDWES->id_modulo
            ]
        ]);

        // 6. Tareas
        // Campos: id_tarea, nombre, tarea, descripcion, bloqueado, notas_alumno, fecha, duracion, modulo_id, alumno_id, apto
        // SIN SOFT DELETES
        $tarea1 = Tarea::on($nombreConexion)->create([
            'nombre' => 'Práctica 1',
            'tarea' => 'Práctica',
            'descripcion' => 'Instalación entorno.',
            'modulo_id' => $moduloDWES->id_modulo,
            'alumno_id' => $alumno1->id_alumno,
            'apto' => false,
            'bloqueado' => false,
        ]);

        // Tarea Calificada
        $tarea2 = Tarea::on($nombreConexion)->create([
            'nombre' => 'Examen PHP',
            'tarea' => 'Examen',
            'descripcion' => 'Examen teórico.',
            'notas_alumno' => 'Buen trabajo.',
            'fecha' => now(),
            'duracion' => '02:00',
            'modulo_id' => $moduloDWES->id_modulo,
            'alumno_id' => $alumno1->id_alumno,
            'apto' => true,
            'bloqueado' => true,
        ]);

        // 7. Relación Tarea-Criterio
        // Tabla: tareas_criterios
        DB::connection($nombreConexion)->table('tareas_criterios')->insertOrIgnore([
            ['tarea_id' => $tarea2->id_tarea, 'criterio_id' => $criterioA->id_criterio]
        ]);
        
        $this->command->info("      INFO: Datos insertados en {$proyecto->proyecto}");
    }
}