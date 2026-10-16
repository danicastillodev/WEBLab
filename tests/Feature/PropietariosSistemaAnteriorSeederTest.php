<?php

namespace Tests\Feature;

use App\Models\Propietario;
use Database\Seeders\EstadosSeeder;
use Database\Seeders\MunicipiosSeeder;
use Database\Seeders\PropietariosSistemaAnteriorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropietariosSistemaAnteriorSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EstadosSeeder::class);
        MunicipiosSeeder::sembrar();
    }

    public function test_unifica_los_duplicados_con_punto_y_junta_sus_direcciones(): void
    {
        PropietariosSistemaAnteriorSeeder::sembrar();
        PropietariosSistemaAnteriorSeeder::sembrar();

        $this->assertSame(501, Propietario::count());

        // "ANTONIO PONCE AGUIRRE" y "ANTONIO PONCE AGUIRRE." eran el mismo.
        $antonio = Propietario::where('apellidos', 'PONCE AGUIRRE')->sole();
        $this->assertSame('ANTONIO', $antonio->nombre);
        $this->assertCount(2, $antonio->direcciones);
        $this->assertSame('Zapotlanejo', $antonio->direcciones->first()->municipio->nombre);
    }

    public function test_separa_nombre_y_apellidos(): void
    {
        PropietariosSistemaAnteriorSeeder::sembrar();

        $this->assertDatabaseHas('propietarios', ['nombre' => 'FRANCISCO JAVIER', 'apellidos' => 'RAMIREZ DE LA MORA']);
        $this->assertDatabaseHas('propietarios', ['nombre' => 'ANTONIO DE JESUS', 'apellidos' => 'DE LA TORRE RIZO']);
        $this->assertDatabaseHas('propietarios', ['nombre' => 'BOEHRINGER INGELHEIM VETMEDICA S.A DE C.V', 'apellidos' => '']);
        $this->assertDatabaseHas('direcciones', ['calle' => 'CALLE 30', 'numero_exterior' => '2614']);
    }
}
