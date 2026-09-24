<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_id')->constrained('estados')->cascadeOnDelete();
            $table->string('nombre');
            $table->timestamps();

            $table->unique(['estado_id', 'nombre']);
        });

        $jalisco = DB::table('estados')->where('clave', 'JAL')->first();

        if (! $jalisco) {
            return;
        }

        $now = now();
        $id = $jalisco->id;

        $municipios = [
            'Acatic', 'Acatlán de Juárez', 'Ahualulco de Mercado', 'Amacueca', 'Amatitán',
            'Ameca', 'San Juanito de Escobedo', 'Arandas', 'El Arenal', 'Atemajac de Brizuela',
            'Atengo', 'Atenguillo', 'Atotonilco el Alto', 'Atoyac', 'Autlán de Navarro',
            'Ayotlán', 'Ayutla', 'La Barca', 'Bolaños', 'Cabo Corrientes',
            'Casimiro Castillo', 'Cihuatlán', 'Zapotlán el Grande', 'Cocula', 'Colotlán',
            'Concepción de Buenos Aires', 'Cuautitlán de García Barragán', 'Cuautla', 'Cuquío', 'Chapala',
            'Chimaltitán', 'Chiquilistlán', 'Degollado', 'Ejutla', 'Encarnación de Díaz',
            'Etzatlán', 'El Grullo', 'Guachinango', 'Guadalajara', 'Hostotipaquillo',
            'Huejúcar', 'Huejuquilla el Alto', 'La Huerta', 'Ixtlahuacán de los Membrillos', 'Ixtlahuacán del Río',
            'Jalostotitlán', 'Jamay', 'Jesús María', 'Jilotlán de los Dolores', 'Jocotepec',
            'Juanacatlán', 'Juchitlán', 'Lagos de Moreno', 'El Limón', 'Magdalena',
            'Santa María del Oro', 'La Manzanilla de la Paz', 'Mascota', 'Mazamitla', 'Mexticacán',
            'Mezquitic', 'Mixtlán', 'Ocotlán', 'Ojuelos de Jalisco', 'Pihuamo',
            'Poncitlán', 'Puerto Vallarta', 'Quitupan', 'El Salto', 'San Cristóbal de la Barranca',
            'San Diego de Alejandría', 'San Juan de los Lagos', 'San Julián', 'San Marcos', 'San Martín de Bolaños',
            'San Martín Hidalgo', 'San Miguel el Alto', 'Gómez Farías', 'San Sebastián del Oeste', 'Santa María de los Ángeles',
            'Sayula', 'Tala', 'Talpa de Allende', 'Tamazula de Gordiano', 'Tapalpa',
            'Tecalitlán', 'Tecolotlán', 'Techaluta de Montenegro', 'Tenamaxtlán', 'Teocaltiche',
            'Teocuitatlán de Corona', 'Tepatitlán de Morelos', 'Tequila', 'Teuchitlán', 'Tizapán el Alto',
            'Tlajomulco de Zúñiga', 'San Pedro Tlaquepaque', 'Tolimán', 'Tomatlán', 'Tonalá',
            'Tonaya', 'Tonila', 'Totatiche', 'Tototlán', 'Tuxcacuesco',
            'Tuxcueca', 'Tuxpan', 'Unión de San Antonio', 'Unión de Tula', 'Valle de Guadalupe',
            'Valle de Juárez', 'San Gabriel', 'Villa Corona', 'Villa Guerrero', 'Villa Hidalgo',
            'Cañadas de Obregón', 'Yahualica de González Gallo', 'Zacoalco de Torres', 'Zapopan', 'Zapotiltic',
            'Zapotitlán de Vadillo', 'Zapotlán del Rey', 'Zapotlanejo', 'San Ignacio Cerro Gordo',
        ];

        $rows = array_map(fn ($nombre) => [
            'estado_id' => $id,
            'nombre' => $nombre,
            'created_at' => $now,
            'updated_at' => $now,
        ], $municipios);

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('municipios')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
