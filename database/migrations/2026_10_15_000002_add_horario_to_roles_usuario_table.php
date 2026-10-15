<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El rol también lleva la ventana de acceso (mismo formato que en users),
     * que se copia al usuario junto con los permisos al asignárselo. En blanco
     * = sin restricción, que es como quedan los roles predefinidos.
     */
    public function up(): void
    {
        Schema::table('roles_usuario', function (Blueprint $table) {
            $table->time('hora_inicio')->nullable()->after('descripcion');
            $table->time('hora_fin')->nullable()->after('hora_inicio');
            $table->json('dias_permitidos')->nullable()->after('hora_fin');
        });
    }

    public function down(): void
    {
        Schema::table('roles_usuario', function (Blueprint $table) {
            $table->dropColumn(['hora_inicio', 'hora_fin', 'dias_permitidos']);
        });
    }
};
