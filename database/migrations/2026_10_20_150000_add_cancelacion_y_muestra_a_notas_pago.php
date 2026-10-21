<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Como en el sistema anterior (facturacionfacturas.Cancelado), una nota
     * cancelada no se borra: conserva su folio y queda registrado el motivo.
     *
     * muestra_id en cada concepto permite que, al editar la nota, cada
     * concepto recupere la cantidad que el usuario había ajustado.
     */
    public function up(): void
    {
        Schema::table('notas_pago', function (Blueprint $table) {
            $table->boolean('cancelada')->default(false)->after('pagada');
            $table->text('motivo_cancelacion')->nullable()->after('cancelada');
            $table->timestamp('cancelada_at')->nullable()->after('motivo_cancelacion');
            $table->foreignId('cancelada_por')->nullable()->after('cancelada_at')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('nota_pago_conceptos', function (Blueprint $table) {
            $table->foreignId('muestra_id')->nullable()->after('nota_pago_id')
                ->constrained('muestras')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nota_pago_conceptos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('muestra_id');
        });

        Schema::table('notas_pago', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelada_por');
            $table->dropColumn(['cancelada', 'motivo_cancelacion', 'cancelada_at']);
        });
    }
};
