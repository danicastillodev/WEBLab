<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('es_admin')->default(false)->after('password');

            // Null en hora_inicio/hora_fin = sin restricción de horario.
            $table->time('hora_inicio')->nullable()->after('es_admin');
            $table->time('hora_fin')->nullable()->after('hora_inicio');

            // Días ISO-8601 permitidos (1 = lunes ... 7 = domingo).
            // Null = todos los días.
            $table->json('dias_permitidos')->nullable()->after('hora_fin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['es_admin', 'hora_inicio', 'hora_fin', 'dias_permitidos']);
        });
    }
};
