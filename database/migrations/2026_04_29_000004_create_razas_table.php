<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('razas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        $now = now();
        DB::table('razas')->insert(array_map(fn($nombre) => [
            'nombre'     => $nombre,
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            'Labrador Retriever', 'Golden Retriever', 'Pastor Alemán', 'Bulldog',
            'Poodle', 'Chihuahua', 'Rottweiler', 'Yorkshire Terrier', 'Beagle',
            'Dálmata', 'Boxer', 'Schnauzer', 'Shih Tzu', 'Pitbull',
            'Siamés', 'Persa', 'Maine Coon', 'Bengalí', 'Ragdoll',
            'Criolla / Mestiza', 'Otra',
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('razas');
    }
};
