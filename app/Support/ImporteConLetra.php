<?php

namespace App\Support;

/**
 * Importe en pesos con letra, como se escribe en las notas de pago:
 * 180.00 → "CIENTO OCHENTA PESOS 00/100 M.N.".
 */
class ImporteConLetra
{
    private const UNIDADES = [
        '', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
        'VEINTE', 'VEINTIUN', 'VEINTIDOS', 'VEINTITRES', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISEIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE',
    ];

    private const DECENAS = ['', '', '', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];

    private const CENTENAS = [
        '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
        'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
    ];

    public static function pesos(float|string $importe): string
    {
        // Se trabaja en centavos para no arrastrar errores de punto flotante.
        $centavosTotales = (int) round(abs((float) $importe) * 100);
        $enteros = intdiv($centavosTotales, 100);
        $centavos = $centavosTotales % 100;

        $letra = $enteros === 0 ? 'CERO' : self::numero($enteros);
        // "UN MILLON DE PESOS", "DOS MILLONES DE PESOS", pero "UN MILLON CIEN PESOS".
        $de = $enteros >= 1_000_000 && $enteros % 1_000_000 === 0 ? 'DE ' : '';
        $moneda = $enteros === 1 ? 'PESO' : 'PESOS';

        return sprintf('%s %s%s %02d/100 M.N.', $letra, $de, $moneda, $centavos);
    }

    private static function numero(int $n): string
    {
        if ($n >= 1_000_000) {
            $millones = intdiv($n, 1_000_000);
            $resto = $n % 1_000_000;
            $texto = $millones === 1 ? 'UN MILLON' : self::numero($millones).' MILLONES';

            return trim($texto.' '.($resto ? self::numero($resto) : ''));
        }

        if ($n >= 1000) {
            $miles = intdiv($n, 1000);
            $resto = $n % 1000;
            $texto = $miles === 1 ? 'MIL' : self::numero($miles).' MIL';

            return trim($texto.' '.($resto ? self::numero($resto) : ''));
        }

        if ($n === 100) {
            return 'CIEN';
        }

        if ($n > 100) {
            $resto = $n % 100;

            return trim(self::CENTENAS[intdiv($n, 100)].' '.($resto ? self::numero($resto) : ''));
        }

        if ($n < 30) {
            return self::UNIDADES[$n];
        }

        $unidad = $n % 10;

        return self::DECENAS[intdiv($n, 10)].($unidad ? ' Y '.self::UNIDADES[$unidad] : '');
    }
}
