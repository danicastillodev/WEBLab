<?php

namespace Tests\Feature;

use App\Models\Explotacion;
use App\Models\Propietario;
use Database\Seeders\EstadosSeeder;
use Database\Seeders\ExplotacionesSistemaAnteriorSeeder;
use Database\Seeders\MunicipiosSeeder;
use Database\Seeders\PropietariosSistemaAnteriorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExplotacionesSistemaAnteriorSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EstadosSeeder::class);
        MunicipiosSeeder::sembrar();
        PropietariosSistemaAnteriorSeeder::sembrar();
    }

    public function test_reemplaza_las_explotaciones_por_las_del_sistema_anterior(): void
    {
        $ejemplo = Explotacion::create([
            'propietario_id' => Propietario::first()->id,
            'nombre' => 'Rancho de ejemplo',
        ]);

        ExplotacionesSistemaAnteriorSeeder::sembrar();
        ExplotacionesSistemaAnteriorSeeder::sembrar();

        $this->assertModelMissing($ejemplo);
        $this->assertSame(1504, Explotacion::count());

        // Las tres capturas de "MARY CLEMEN" del mismo dueño y municipio son una.
        $mary = Explotacion::where('nombre', 'MARY CLEMEN')->sole();
        $this->assertSame('BOEHRINGER INGELHEIM VETMEDICA S.A DE C.V', $mary->propietario->nombre);
        $this->assertSame('Pénjamo', $mary->municipio->nombre);

        // "NR" queda vacío.
        $this->assertNull(Explotacion::where('nombre', 'MARY CLEMENT 4TO. MUESTREO')->value('caseta'));
    }
}
