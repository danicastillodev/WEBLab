<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Vincula cada Prueba con su Producto de facturación cuando el nombre y
     * la técnica dejan claro que son el mismo análisis (p. ej. "DVB ELISA"
     * ↔ "DIARREA VIRAL BOVINA", o "IBR ELISA" ↔ "RINOTRAQUEITIS INFECCIOSA",
     * su mismo nombre en inglés/español). Donde el catálogo de Productos no
     * tiene una contraparte clara, o hay varias variantes del producto por
     * técnica/tipo de muestra (p. ej. Influenza Aviar AGID vs. HI vs. PCR)
     * y no hay forma de saber cuál corresponde sin adivinar, la prueba queda
     * sin vincular: un admin la liga a mano desde el catálogo de Pruebas.
     *
     * La clave es el nombre exacto de la Prueba; cuando el mismo nombre se
     * repite para varias especies (Leptospirosis MAT, Brucelosis tarjeta...)
     * el update alcanza a todas esas filas, porque el producto facturado es
     * el mismo sin importar la especie de origen de la muestra.
     */
    public function up(): void
    {
        $mapa = [
            'Brucelosis tarjeta (Rosa de Bengala)' => 'BRUCELA TARJETA',
            'DVB ELISA' => 'DIARREA VIRAL BOVINA',
            'IBR ELISA' => 'RINOTRAQUEITIS INFECCIOSA',
            'Influenza Porcina HI' => 'INFLUENZA PORCINA',
            'Leptospirosis MAT' => 'LEPTOSPIRA',
            'Leucosis Bovina AGID' => 'LEUCOSIS BOVINA',
            'Leucosis Bovina ELISA' => 'LEUCOSIS BOVINA',
            'Mycoplasma gallisepticum ELISA' => 'MYCOPLASMA',
            'Mycoplasma synoviae ELISA' => 'MYCOPLASMA',
            'Newcastle ELISA' => 'NEWCASTLE',
            'Newcastle HI' => 'NEWCASTLE',
            'Paratuberculosis ELISA' => 'PARATUBERCULOSIS',
            'PRRS ELISA' => 'PRRS',
            'Salmonella aglutinación' => 'SALMONELLA',
        ];

        foreach ($mapa as $nombrePrueba => $nombreProducto) {
            $productoId = DB::table('productos')->where('nombre', $nombreProducto)->value('id');

            if ($productoId) {
                DB::table('pruebas')->where('nombre', $nombrePrueba)->update(['producto_id' => $productoId]);
            }
        }
    }

    public function down(): void
    {
        $nombres = [
            'Brucelosis tarjeta (Rosa de Bengala)',
            'DVB ELISA',
            'IBR ELISA',
            'Influenza Porcina HI',
            'Leptospirosis MAT',
            'Leucosis Bovina AGID',
            'Leucosis Bovina ELISA',
            'Mycoplasma gallisepticum ELISA',
            'Mycoplasma synoviae ELISA',
            'Newcastle ELISA',
            'Newcastle HI',
            'Paratuberculosis ELISA',
            'PRRS ELISA',
            'Salmonella aglutinación',
        ];

        DB::table('pruebas')->whereIn('nombre', $nombres)->update(['producto_id' => null]);
    }
};
