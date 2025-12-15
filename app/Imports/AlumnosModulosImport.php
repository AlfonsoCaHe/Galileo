<?php

namespace App\Imports;

use App\Models\Alumno;
use App\Models\User;
use App\Models\Modulo;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class AlumnosModulosImport implements OnEachRow, WithHeadingRow
{
    protected $proyecto;
    protected $connectionName;

    public function __construct(Proyecto $proyecto)
    {
        $this->proyecto = $proyecto;
        // Configuramos la conexión nada más instanciar la clase
        $this->connectionName = $this->setupDynamicConnection();
    }

    public function headingRow(): int
    {
        return 5; // Ajustado al EXCEL de Séneca
    }

    public function onRow(Row $row)
    {
        $data = $row->toArray(); // Array de claves (formato necesario: snake_case)

        // 1. Validar fila vacía
        if (empty($data['alumnoa'])) return;

        // 2. Preparar datos
        $nombreCompleto = $this->formatearNombre($data['alumnoa']);
        $email = $data['cuenta_googlemicrosoft'] ?? null;

        // ---------------------------------------------------------
        // PARTE A: Crear ALUMNO en BD DINÁMICA
        // ---------------------------------------------------------
        // Alumno usa la conexión por defecto que hemos cambiado en el constructor
        $alumno = Alumno::firstOrCreate(//Usamos firstOrCreate por si ya estuviera creado
            ['nombre' => $nombreCompleto],
        );

        // ---------------------------------------------------------
        // PARTE B: Crear/Vincular USER en BD PRINCIPAL (Galileo)
        // ---------------------------------------------------------
        if ($email) {
            // Buscamos el usuario explícitamente en la conexión mysql principal. Puede estar matriculado con anterioridad o ser repetidor completo.
            $user = User::on('mysql')->where('email', $email)->first();

            // Si no hay un usuario lo creamos
            if (!$user) {
                User::on('mysql')->create([
                    'name' => $nombreCompleto,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'rol' => 'alumno',
                    // Enlace Polimórfico
                    'rolable_id' => $alumno->id_alumno, // ID generado del alumno
                    'rolable_type' => Alumno::class,
                ]);
            } else {
                // Si ya existe el usuario, solo actualizamos su vínculo al nuevo proyecto
                $user->update([
                    'rolable_id'   => $alumno->id_alumno,
                    'rolable_type' => Alumno::class
                ]);
            }
        }

        // ---------------------------------------------------------
        // PARTE C: Módulos y Matrícula (BD DINÁMICA)
        // ---------------------------------------------------------
        foreach ($data as $key => $valor) {
            // Ignoramos columnas de datos personales
            if (in_array($key, ['alumnoa', 'cuenta_googlemicrosoft', ''])) continue;

            // Si hay valor (MATR, APRO, etc.), procesamos
            if (!empty($valor)) {
                $this->procesarMatricula($alumno, $key);
            }
        }
    }

    private function procesarMatricula($alumno, $key)
    {
        // Volvemos a poner el nombre humanizado (sin snake_case)
        $nombreModulo = Str::title(str_replace('_', ' ', $key));

        // Creamos el Módulo (BD Dinámica)
        $modulo = Modulo::firstOrCreate(// Si es la primera vez crea el módulo, si no devuelve el que ya existe
            ['nombre' => $nombreModulo],
            ['proyecto_id' => $this->proyecto->id_base_de_datos]
        );

        // Añadimos al alumno (Tabla pivote 'alumno_modulo' en BD Dinámica)
        $yaExiste = $alumno->modulos()
                           ->where('modulo_id', $modulo->id_modulo)
                           ->exists();

        if (!$yaExiste) {
            $alumno->modulos()->attach($modulo->id_modulo);
        }
    }

    /**
     * Método para convertir la cadena "apellidos, nombre" en "nombre apellidos"
     */
    private function formatearNombre($rawName)
    {
        if (str_contains($rawName, ',')) {
            $partes = explode(',', $rawName);
            return count($partes) >= 2 ? trim($partes[1]) . ' ' . trim($partes[0]) : $rawName;
        }
        return trim($rawName);
    }

    /**
     * Configura la conexión dinámica para que los Modelos Alumno/Modulo apunten a la base de datos correcta.
     */
    private function setupDynamicConnection()
    {
        $connectionName = 'dynamic_import_' . $this->proyecto->id_base_de_datos;
        $config = Config::get('database.connections.mysql');
        
        $config['database'] = $this->proyecto->conexion;
        Config::set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);
        Config::set('database.default', $connectionName);

        Alumno::getConnectionResolver()->setDefaultConnection($connectionName);
        Modulo::getConnectionResolver()->setDefaultConnection($connectionName);

        return $connectionName;
    }
}