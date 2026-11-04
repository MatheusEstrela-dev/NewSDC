<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Capacidade do caminhao e preco do m3 GRAVADOS na viagem no momento da
 * aprovacao.
 *
 * Antes, agua_entregue e vr_total eram recalculados a cada validacao com a
 * capacidade_m3 ATUAL do caminhao e o valor_m3 ATUAL do lote. Corrigir a
 * capacidade ou reajustar o preco reescrevia o que ja tinha sido aprovado e
 * pago -- por isso a edicao estava bloqueada. Com o valor gravado na viagem,
 * o cadastro pode mudar e o efeito vale so para as proximas aprovacoes.
 *
 * Backfill: as viagens ja aprovadas recebem os valores atuais, que sao os que
 * o sistema vinha usando para elas (a edicao estava bloqueada). Caminhao
 * excluido entra no join de proposito: a agua que ele levou nao zera.
 * Pendentes e rejeitadas ficam NULL -- so a aprovacao grava.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdap_crono_viagens', function (Blueprint $table): void {
            $table->decimal('capacidade_m3', 10, 2)->nullable()->after('validado');
            $table->decimal('valor_m3', 10, 2)->nullable()->after('capacidade_m3');
        });

        DB::statement(<<<'SQL'
            UPDATE tdap_crono_viagens v
               SET capacidade_m3 = cam.capacidade_m3,
                   valor_m3      = l.valor_m3
              FROM tdap_crono_caminhoes cc
              JOIN tdap_caminhoes cam ON cam.id = cc.caminhao_id
              JOIN tdap_cronogramas c ON c.id = cc.cronograma_id
              LEFT JOIN tdap_lotes l  ON l.id = c.lote_id
             WHERE cc.id = v.crono_caminhao_id
               AND v.validado = 1
               AND v.capacidade_m3 IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('tdap_crono_viagens', function (Blueprint $table): void {
            $table->dropColumn(['capacidade_m3', 'valor_m3']);
        });
    }
};
