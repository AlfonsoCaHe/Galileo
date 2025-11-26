<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use App\Models\Profesor;
use App\Models\Empresa;
use App\Models\TutorLaboral;
use App\Models\User;
use App\Models\Alumno;
use Illuminate\Support\Str;

class PruebaRelacionesSeeder extends Seeder
{
    /**
     * Ejecuta la siembra de la base de datos con datos de prueba.
     */
    public function run(): void
    {
        // 1. Asegurar que la BD principal (Galileo) exista y esté migrada.
        $this->command->info("--- 1. Preparando BD principal (Galileo) y Administrador ---");
        
        // Llama a db:crear-galileo (Creación BD, Migración, AdminUserSeeder)
        Artisan::call('db:crear-galileo', [], $this->command->getOutput()); 

        // 2. Crear Profesores, Empresas y Tutores (en BD Galileo)
        $this->command->info("\n--- 2. Creando Profesores, Empresas y Tutores Laborales ---");
        $this->crearDatosGalileo();
        
        // 3. Crear Proyectos Dinámicos y Alumnos (en BDs dinámicas)
        $this->command->info("\n--- 3. Creando Proyectos y Datos de Alumnos ---");
        $this->crearProyectos();

        $this->command->info("\nSiembra de datos de prueba completada.");
    }

    /**
     * Crea 2 Profesores y 2 Empresas con 2 Tutores Laborales cada una.
     */
    private function crearDatosGalileo(): void
    {
        // Limpiar para evitar duplicados si el seeder de esta sección se llama dos veces
        // Profesor::truncate();
        // Empresa::truncate();
        // TutorLaboral::truncate();
        $indice_profesor = 1;
        $indice_empresa = 1;
        $indice_tutor = 1;
        // Crear 2 Profesores
        for ($i = 1; $i <= 2; $i++) {
            $profesor = Profesor::create([
                'nombre' => "Profesor Demo {$indice_profesor}",
            ]);
            User::create([
                'name' => $profesor->nombre, 'email' => 'profesor'.$indice_profesor.'@ies.galileo.com', 'password' => 'password',
                'rol' => 'profesor', 'rolable_id' => $profesor->id_profesor, 'rolable_type' => Profesor::class, 
            ]);
            $indice_profesor++;
        }
        $this->command->info("-> Se crearon 2 profesores y sus usuarios.");

        // Crear 2 Empresas con 2 Tutores cada una
        for ($i = 1; $i <= 2; $i++) {
            $empresa = Empresa::create([
                'nombre' => "Empresa Demo {$indice_empresa}",
                'cif_nif' => "A1234560{$indice_empresa}",
                'nombre_gerente' => "Gerente {$indice_empresa}",
                'nif_gerente' => "{$indice_empresa}1234567A"
            ]);
            
            for ($j = 1; $j <= 2; $j++) {
                $tutor = TutorLaboral::create([
                    'empresa_id' => $empresa->id_empresa,
                    'nombre' => "Tutor {$indice_tutor} - {$empresa->nombre}",
                    'email' => "tutor{$i}{$indice_tutor}@demo.com",
                ]);
                User::create([
                    'name' => $tutor->nombre, 'email' => $tutor->email, 'password' => 'password',
                    'rol' => 'tutor_laboral', 'rolable_id' => $tutor->id_tutor_laboral, 'rolable_type' => TutorLaboral::class, 
                ]);
                $indice_tutor++;
            }
            $indice_empresa++;
        }
        $this->command->info("-> Se crearon 2 empresas y 4 tutores laborales con sus usuarios.");
    }

    /**
     * Crea 2 proyectos dinámicos (usando db:crear-proyecto) y luego siembra datos de prueba en ellos.
     */
    private function crearProyectos(): void
    {
        // Crear 2 Proyectos (ej. año actual y año siguiente)
        $currentYear = now()->year;
        $projectYears = [$currentYear, $currentYear + 1];
        
        $indice_alumno = 1;
        $letra_alumno = ['A', 'B', 'C', 'D', 'E', 'F'];

        foreach ($projectYears as $year) {
            $this->command->info("\n*** Creando Proyecto para el año: {$year} ***");

            // Llama a db:crear-proyecto (Creación BD Dinámica y Esquema)
            Artisan::call('db:crear-proyecto', ['year_start' => $year], $this->command->getOutput());
            
            // Llama a PruebaRelacionesSeeder para poblar el esquema del proyecto (Módulos, Alumnos, Tareas)
            // Asumo que este seeder ya contiene la lógica de poblar los 4 alumnos y las demás relaciones.
            Artisan::call('db:seed', [
                '--class' => PruebaRelacionesSeeder::class,
                // Opcional: si tu PruebaRelacionesSeeder usa una conexión específica, puedes pasarla aquí.
            ], $this->command->getOutput()); 
            
            for($i = 1; $i < 2; $i++){
                $alumno = Alumno::create([
                    'nombre' => 'Alumno '.$letra_alumno[$indice_alumno],
                ]);
                $indice_alumno++;
            }

            $this->command->info("*** Proyecto {$year} sembrado con datos de prueba (4 Alumnos, Módulos, Tareas). ***");
        }
    }
}