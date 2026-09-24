<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El número de caso es consecutivo dentro de un ejercicio (el año de la
     * fecha de recepción), no global: cada año la numeración vuelve a empezar.
     * Por eso el ejercicio se guarda en la tabla y la unicidad pasa a ser del
     * par (ejercicio, numero_caso).
     */
    public function up(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->unsignedSmallInteger('ejercicio')->nullable()->after('numero_caso');
        });

        // Los registros existentes toman su ejercicio de la fecha de recepción,
        // que es obligatoria desde la creación de la tabla.
        DB::table('historias_clinicas')->update([
            'ejercicio' => DB::raw($this->anioDeFechaRecepcion()),
        ]);

        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->unsignedSmallInteger('ejercicio')->nullable(false)->change();
            $table->dropUnique(['numero_caso']);
            $table->unique(['ejercicio', 'numero_caso']);
        });
    }

    public function down(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->dropUnique(['ejercicio', 'numero_caso']);
            $table->unique(['numero_caso']);
            $table->dropColumn('ejercicio');
        });
    }

    private function anioDeFechaRecepcion(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', fecha_recepcion) AS INTEGER)"
            : 'YEAR(fecha_recepcion)';
    }
};
