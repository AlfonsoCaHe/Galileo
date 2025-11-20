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
        // 1. Crear la instancia del User
        User::create([
            'name' => 'Admin Proyecto',
            'email' => 'admin@ies.galileo.com',
            'password' => 'root',
            'rol' => 'admin',
        ]);

        $this->command->info('Usuario Administrador Creado: admin@ies.galileo.com / root');
    }
}