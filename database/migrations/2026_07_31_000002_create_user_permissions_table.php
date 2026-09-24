<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('modulo');
            $table->boolean('ver')->default(false);
            $table->boolean('crear')->default(false);
            $table->boolean('editar')->default(false);
            $table->boolean('eliminar')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'modulo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
