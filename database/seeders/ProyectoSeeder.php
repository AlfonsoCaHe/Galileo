<?php

namespace Database\Seeders;

use App\Models\Proyecto; // Importa el modelo
use Illuminate\Database\Seeder;

class ProyectoSeeder extends Seeder
{
    public function run(): void
    {
        // Crea 5 registros de prueba usando la Factory
        Proyecto::factory()->count(5)->create();
        
        // Crea un registro específico
        Proyecto::create([
            'proyecto' => 'ProyectoPrueba1',
            'conexion' => 'mi.conexion.unica.net',
        ]);
        
        $this->command->info('Tabla "bases_de_datos" (Proyecto) sembrada.');
    }
}
