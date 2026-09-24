<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propietarios', function (Blueprint $table) {
            // Holgura sobre los 10 dígitos que exige la validación, por si más
            // adelante se aceptan lada internacional o extensiones.
            $table->string('telefono', 20)->nullable()->after('apellidos');
        });
    }

    public function down(): void
    {
        Schema::table('propietarios', function (Blueprint $table) {
            $table->dropColumn('telefono');
        });
    }
};
