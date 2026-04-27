<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CreateAdminUser extends Command
{
    protected $signature = 'user:create-admin';
    protected $description = 'Crea un usuario administrador';

    public function handle()
    {
        $user = User::create([
            'first_name' => $this->ask('Nombre'),
            'last_name'  => $this->ask('Apellido'),
            'email'      => $this->ask('Email'),
            'password'   => bcrypt($this->secret('Contraseña')),
            'position'   => $this->ask('Posición', 'Administrador'),
            'status'     => 'active',
            'role_id'    => $this->ask('Role ID', 1),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("✅ Usuario {$user->email} creado correctamente!");
    }
}