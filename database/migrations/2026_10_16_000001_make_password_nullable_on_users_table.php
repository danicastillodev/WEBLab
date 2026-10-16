<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un usuario sin contraseña (recién creado, o al que se le borró desde
     * "Modificar usuario") la crea él mismo en su próximo inicio de sesión.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Los usuarios sin contraseña no podrían cumplir NOT NULL: se les deja
        // una cadena vacía, que ninguna contraseña tecleada puede igualar.
        DB::table('users')->whereNull('password')->update(['password' => '']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
