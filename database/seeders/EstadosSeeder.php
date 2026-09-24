<?php

namespace Database\Seeders;

use App\Models\Estado;
use Illuminate\Database\Seeder;

class EstadosSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            ['nombre' => 'Aguascalientes',       'clave' => 'AGS'],
            ['nombre' => 'Baja California',       'clave' => 'BC'],
            ['nombre' => 'Baja California Sur',   'clave' => 'BCS'],
            ['nombre' => 'Campeche',              'clave' => 'CAMP'],
            ['nombre' => 'Chiapas',               'clave' => 'CHIS'],
            ['nombre' => 'Chihuahua',             'clave' => 'CHIH'],
            ['nombre' => 'Ciudad de México',      'clave' => 'CDMX'],
            ['nombre' => 'Coahuila',              'clave' => 'COAH'],
            ['nombre' => 'Colima',                'clave' => 'COL'],
            ['nombre' => 'Durango',               'clave' => 'DGO'],
            ['nombre' => 'Estado de México',      'clave' => 'MEX'],
            ['nombre' => 'Guanajuato',            'clave' => 'GTO'],
            ['nombre' => 'Guerrero',              'clave' => 'GRO'],
            ['nombre' => 'Hidalgo',               'clave' => 'HGO'],
            ['nombre' => 'Jalisco',               'clave' => 'JAL'],
            ['nombre' => 'Michoacán',             'clave' => 'MICH'],
            ['nombre' => 'Morelos',               'clave' => 'MOR'],
            ['nombre' => 'Nayarit',               'clave' => 'NAY'],
            ['nombre' => 'Nuevo León',            'clave' => 'NL'],
            ['nombre' => 'Oaxaca',                'clave' => 'OAX'],
            ['nombre' => 'Puebla',                'clave' => 'PUE'],
            ['nombre' => 'Querétaro',             'clave' => 'QRO'],
            ['nombre' => 'Quintana Roo',          'clave' => 'QROO'],
            ['nombre' => 'San Luis Potosí',       'clave' => 'SLP'],
            ['nombre' => 'Sinaloa',               'clave' => 'SIN'],
            ['nombre' => 'Sonora',                'clave' => 'SON'],
            ['nombre' => 'Tabasco',               'clave' => 'TAB'],
            ['nombre' => 'Tamaulipas',            'clave' => 'TAMPS'],
            ['nombre' => 'Tlaxcala',              'clave' => 'TLAX'],
            ['nombre' => 'Veracruz',              'clave' => 'VER'],
            ['nombre' => 'Yucatán',               'clave' => 'YUC'],
            ['nombre' => 'Zacatecas',             'clave' => 'ZAC'],
        ];

        foreach ($estados as $estado) {
            Estado::firstOrCreate(['clave' => $estado['clave']], $estado);
        }
    }
}
