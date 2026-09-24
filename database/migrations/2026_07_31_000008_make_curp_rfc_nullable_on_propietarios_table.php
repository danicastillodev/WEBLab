<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propietarios', function (Blueprint $table) {
            // El índice UNIQUE se conserva: MariaDB permite varios NULL en un
            // índice único, así que pueden coexistir propietarios sin CURP/RFC.
            $table->string('curp', 18)->nullable()->change();
            $table->string('rfc', 13)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('propietarios', function (Blueprint $table) {
            $table->string('curp', 18)->nullable(false)->change();
            $table->string('rfc', 13)->nullable(false)->change();
        });
    }
};
