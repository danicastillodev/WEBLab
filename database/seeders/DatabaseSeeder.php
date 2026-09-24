<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Administrador inicial: omite el control de funciones y de horario,
        // de forma que siempre haya una vía de entrada al sistema.
        //
        // Solo fuera de producción. La factory usa la contraseña 'password', que
        // no tiene nada que hacer en un servidor público: en producción el
        // administrador se crea con `php artisan netlab:crear-admin`, que exige
        // una contraseña real o genera una al azar.
        if (! app()->isProduction()) {
            User::factory()->create([
                'name' => 'Test User',
                'username' => 'admin',
                'email' => 'test@example.com',
                'es_admin' => true,
            ]);
        }

        $this->call(EstadosSeeder::class);
        $this->call(MunicipiosSeeder::class);
        $this->call(EspeciesSeeder::class);
        $this->call(RazasSeeder::class);
        $this->call(FuncionesZootecnicasSeeder::class);
        $this->call(PruebasSeeder::class);
    }
}
