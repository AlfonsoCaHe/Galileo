<?php

namespace Database\Factories;

use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str; // Asegúrate de importar Str

class ProyectoFactory extends Factory
{
    protected $model = Proyecto::class;

    public function definition(): array
    {
        return [
            // 'proyecto' solo admite 19 caracteres
            'proyecto' => Str::random(10), 
            // 'conexion' solo admite 45 caracteres
            'conexion' => $this->faker->unique()->domainName, 
        ];
    }
}