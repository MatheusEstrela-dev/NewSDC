<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trilha da tramitacao do cronograma: de que fase para qual, por quem e com
 * que observacao. Mesmo molde de `pedido_ah_tramites` (AjudaHumanitaria).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_cronograma_tramites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cronograma_id')->constrained('tdap_cronogramas')->cascadeOnDelete();
            $table->string('fase_anterior', 20);
            $table->string('fase_nova', 20);
            $table->string('acao', 20);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacao')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['cronograma_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_cronograma_tramites');
    }
};
