<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('clave', 10)->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('estados')->insert([
            ['nombre' => 'Aguascalientes',       'clave' => 'AGS',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Baja California',       'clave' => 'BC',    'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Baja California Sur',   'clave' => 'BCS',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Campeche',              'clave' => 'CAMP',  'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Chiapas',               'clave' => 'CHIS',  'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Chihuahua',             'clave' => 'CHIH',  'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Ciudad de México',      'clave' => 'CDMX',  'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Coahuila',              'clave' => 'COAH',  'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Colima',                'clave' => 'COL',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Durango',               'clave' => 'DGO',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Estado de México',      'clave' => 'MEX',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Guanajuato',            'clave' => 'GTO',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Guerrero',              'clave' => 'GRO',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Hidalgo',               'clave' => 'HGO',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Jalisco',               'clave' => 'JAL',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Michoacán',             'clave' => 'MICH',  'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Morelos',               'clave' => 'MOR',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Nayarit',               'clave' => 'NAY',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Nuevo León',            'clave' => 'NL',    'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Oaxaca',                'clave' => 'OAX',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Puebla',                'clave' => 'PUE',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Querétaro',             'clave' => 'QRO',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Quintana Roo',          'clave' => 'QROO',  'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'San Luis Potosí',       'clave' => 'SLP',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Sinaloa',               'clave' => 'SIN',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Sonora',                'clave' => 'SON',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Tabasco',               'clave' => 'TAB',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Tamaulipas',            'clave' => 'TAMPS', 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Tlaxcala',              'clave' => 'TLAX',  'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Veracruz',              'clave' => 'VER',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Yucatán',               'clave' => 'YUC',   'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Zacatecas',             'clave' => 'ZAC',   'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('estados');
    }
};
