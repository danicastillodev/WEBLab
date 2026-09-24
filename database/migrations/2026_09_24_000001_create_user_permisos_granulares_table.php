<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permisos_granulares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('clave');
            $table->timestamps();

            $table->unique(['user_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permisos_granulares');
    }
};
