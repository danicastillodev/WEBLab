<?php

namespace Tests\Unit;

use App\Support\ImporteConLetra;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImporteConLetraTest extends TestCase
{
    public static function importes(): array
    {
        return [
            [180, 'CIENTO OCHENTA PESOS 00/100 M.N.'],
            [0, 'CERO PESOS 00/100 M.N.'],
            [1, 'UN PESO 00/100 M.N.'],
            [21.5, 'VEINTIUN PESOS 50/100 M.N.'],
            [100, 'CIEN PESOS 00/100 M.N.'],
            [155.17, 'CIENTO CINCUENTA Y CINCO PESOS 17/100 M.N.'],
            [1000, 'MIL PESOS 00/100 M.N.'],
            [2516.99, 'DOS MIL QUINIENTOS DIECISEIS PESOS 99/100 M.N.'],
            [21000, 'VEINTIUN MIL PESOS 00/100 M.N.'],
            [1000000, 'UN MILLON DE PESOS 00/100 M.N.'],
            [3250100, 'TRES MILLONES DOSCIENTOS CINCUENTA MIL CIEN PESOS 00/100 M.N.'],
            ['232.00', 'DOSCIENTOS TREINTA Y DOS PESOS 00/100 M.N.'],
            // 0.1 + 0.2 no es exacto en punto flotante: no debe dar 29 centavos.
            [0.1 + 0.2, 'CERO PESOS 30/100 M.N.'],
        ];
    }

    #[DataProvider('importes')]
    public function test_convierte_el_importe_a_letra(float|string $importe, string $esperado): void
    {
        $this->assertSame($esperado, ImporteConLetra::pesos($importe));
    }
}
