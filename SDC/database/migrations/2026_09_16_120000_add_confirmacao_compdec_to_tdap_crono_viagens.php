<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Confirmacao do municipio, separada da validacao do estado.
 *
 * Sao dois atos distintos, de responsaveis distintos: o COMPDEC atesta que a
 * agua CHEGOU, a CEDEC aceita a viagem para PAGAMENTO. Espremer os dois na
 * coluna `validado` faria a confirmacao municipal liberar pagamento sozinha.
 *
 * Aditivo de proposito: `validado` (NULL/1/0) e `user_validacao_id` mantem o
 * significado que ja tinham, e as 3.808 viagens da base seguem intactas. O
 * fluxo passa a ser registrada -> confirmada pelo municipio -> validada pelo
 * estado, e cada etapa le a sua propria coluna.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdap_crono_viagens', function (Blueprint $table): void {
            $table->timestamp('confirmado_em')->nullable()->after('data_aprovacao');

            $table->foreignId('confirmado_por')
                ->nullable()
                ->after('confirmado_em')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('obs_confirmacao')->nullable()->after('obs_aprovacao');

            // A fila do COMPDEC filtra por "ainda nao confirmada".
            $table->index('confirmado_em');
        });
    }

    public function down(): void
    {
        Schema::table('tdap_crono_viagens', function (Blueprint $table): void {
            $table->dropIndex(['confirmado_em']);
            $table->dropConstrainedForeignId('confirmado_por');
            $table->dropColumn(['confirmado_em', 'obs_confirmacao']);
        });
    }
};
