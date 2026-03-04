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
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Row;

class AlumnosModulosImport implements OnEachRow, WithHeadingRow
{
    protected $proyecto;
    protected $connectionName;

    // Array para almacenar los nombres de los alumnos que ya existen
    protected $alumnosRepetidos = [];

    public function __construct(Proyecto $proyecto)
    {
        $this->proyecto = $proyecto;
        // Configuramos la conexión nada más instanciar la clase
        $this->connectionName = $this->setupDynamicConnection();
    }

    public function headingRow(): int
    {
        return 5; // Ajustado al EXCEL de Séneca (la información relevante comienza en la fila 5)
    }

    /**
     * Método que extrae la información de cada tupla en base a la información de Séneca y la inserta en sus tablas
     */
    public function onRow(Row $row)
    {
        $data = $row->toArray(); 

        if (empty($data['alumnoa'])) return;

        $nombreCompleto = $this->formatearNombre($data['alumnoa']);
        $email = $data['cuenta_googlemicrosoft'] ?? null;

        $unidad = $data['unidad'] ?? null;
        
        $alumno = null;
        $usuarioExiste = false;

        // 1. Primero verificamos si el USUARIO ya existe en la BD Principal (Galileo)
        // Esto es crucial para obtener el UUID original si existe.
        if ($email) {
            $user = User::on('mysql')->where('email', $email)->first();
            
            if ($user) {
                $usuarioExiste = true;
                
                // CASO A: El usuario existe.
                // Recuperamos su UUID actual (rolable_id) para REUTILIZARLO.
                $uuidOriginal = $user->rolable_id;

                // Buscamos en la BD Dinámica si ya existe un alumno con ese UUID.
                // Si no existe, lo instanciamos con ese ID forzado.
                $alumno = Alumno::findOrNew($uuidOriginal);
                
                // Aseguramos que el ID sea el del usuario (crucial si es new)
                $alumno->id_alumno = $uuidOriginal; 
                $alumno->nombre = $nombreCompleto;
                
                // Guardamos. Si no existía, se insertará con el UUID del usuario.
                // Si existía, solo actualizará el nombre.
                $alumno->save();

                // Añadimos a la lista de avisos porque es un alumno repetido
                $this->alumnosRepetidos[] = $nombreCompleto . " ({$email})";
            }
        }

        // 2. Si no encontramos usuario previo (o no tiene email), seguimos el flujo normal
        if (!$alumno) {
            // CASO B: Usuario no existe o alumno sin email.
            // Buscamos por nombre o creamos uno nuevo (generará un NUEVO UUID automáticamente)
            $alumno = Alumno::firstOrCreate(
                ['nombre' => $nombreCompleto]
            );
        }

        // 3. Crear/Vincular usuario si era nuevo
        if ($email && !$usuarioExiste) {
            // Solo entramos aquí si el usuario NO existía previamente
            User::on('mysql')->create([
                'name' => $nombreCompleto,
                'email' => $email,
                'password' => Hash::make('password'),
                'rol' => 'alumno',
                'rolable_id' => $alumno->id_alumno,
                'rolable_type' => Alumno::class,
            ]);
        }


        // Módulos y Matrícula (Igual que antes)
        foreach ($data as $key => $valor) {
            if (in_array($key, ['alumnoa', 'cuenta_googlemicrosoft', '', 'unidad'])) continue;

            if (!empty($valor)) {
                $this->procesarMatricula($alumno, $key, $unidad);
            }
        }
    }

    /**
     * Registra los eventos del ciclo de vida de la importación. Usamos AfterImport para flashear el mensaje a la sesión.
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => function(AfterImport $event) {
                if (count($this->alumnosRepetidos) > 0) {
                    // Creamos un mensaje HTML o texto plano
                    $lista = implode(', ', $this->alumnosRepetidos);
                    $mensaje = "Importación completada. ATENCIÓN: Los siguientes alumnos ya tenían usuario y solo verán su primer proyecto activo: " . $lista;
                    
                    // Usamos la sesión flash de Laravel para mostrarlo en la vista
                    session()->flash('warning_import', $mensaje);
                }
            },
        ];
    }

    private function procesarMatricula($alumno, $modulo, $unidad)
    {
        // Volvemos a poner el nombre humanizado (sin snake_case)
        $nombreModulo = Str::title(str_replace('_', ' ', $modulo));
        $nombreUnidad = Str::title(str_replace('_', ' ', $unidad));

        // Creamos el Módulo (BD Dinámica)
        $modulo = Modulo::firstOrCreate(// Si es la primera vez crea el módulo, si no devuelve el que ya existe
            ['nombre' => $nombreModulo],
            ['unidad' => $nombreUnidad],
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