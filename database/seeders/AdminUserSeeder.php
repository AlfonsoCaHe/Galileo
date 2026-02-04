<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $adminEmail = 'admin@dual.iesgalileoalmeria.es';
        
        // 1. Comprobamos si el usuario admin ya existe
        $user = User::where('email', $adminEmail)->first();

        if ($user) { // Si el usuario ya existe, no hacemos nada y salimos.
            echo "El usuario administrador ({$adminEmail}) ya existe. Saltamos inserción.\n";
            return;
        }

        // 2. Si no existe creamos el usuario admin 
        // Usamos el rol 'admin', será el único usuario que lo tenga, no se pueden crear más
        User::create([
            'name' => 'Admin',
            'email' => $adminEmail,
            'password' => 'root',
            'rol' => 'admin',
            'rol_id' => null,
            'rol_type' => null,
        ]);

        echo "Usuario administrador creado con éxito: Usuario: {$adminEmail} / Contraseña: root\n";
    }
}