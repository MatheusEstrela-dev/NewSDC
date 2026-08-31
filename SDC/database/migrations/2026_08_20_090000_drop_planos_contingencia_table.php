<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * O modulo PlanCon deixou de ter tabela propria.
 *
 * `planos_contingencia` duplicava o que o COMPDEC ja modelava melhor em
 * `compdec_planos_contingencia` (versionamento, plano ativo unico por orgao,
 * aprovacao). Havia dois caminhos de escrita para o mesmo dado, com regras
 * diferentes, e o painel do sidebar mostrava numero divergente do COMPDEC.
 *
 * O PlanCon virou leitura: consulta o plano ATIVO de cada orgao. A migration
 * de criacao foi removida junto, em vez de deixar o par criar/dropar no
 * historico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('planos_contingencia');
    }

    /**
     * Sem down: a tabela nao volta a existir. Recria-la sem o modulo que a
     * consumia deixaria schema morto no banco.
     */
    public function down(): void
    {
    }
};
