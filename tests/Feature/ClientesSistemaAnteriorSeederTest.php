<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Database\Seeders\ClientesSistemaAnteriorSeeder;
use Database\Seeders\EstadosSeeder;
use Database\Seeders\MunicipiosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientesSistemaAnteriorSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_agrega_los_clientes_depurados(): void
    {
        $this->seed(EstadosSeeder::class);
        MunicipiosSeeder::sembrar();

        ClientesSistemaAnteriorSeeder::sembrar();
        ClientesSistemaAnteriorSeeder::sembrar();

        $this->assertSame(100, Cliente::count());

        // El RFC genérico queda vacío.
        $this->assertSame(0, Cliente::where('rfc', 'like', 'XAX%')->count());

        // "ROCIO BIBIANA PEREZ ORANDAY." y "ROSIO VIVIANA PEREZ ORANDAY" tenían el mismo RFC.
        $this->assertSame(1, Cliente::where('rfc', 'PEOR730308NF1')->count());

        $this->assertDatabaseHas('clientes', [
            'rfc' => 'DEVA7703156D6',
            'calle' => 'JAVIER MINA',
            'numero_exterior' => '3599',
            'colonia' => 'LA AURORA',
            'codigo_postal' => '44790',
        ]);
    }
}
