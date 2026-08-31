<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove pmda_compdec_membros, que nunca foi usada.
 *
 * A aba COMPDEC do wizard mostra a equipe do ORGAO do municipio, lida de
 * `compdec_equipes` por PmdaPlanoController::compdecEquipeDoPlano(). Esta tabela
 * era um caminho paralelo: tinha rota (POST /{plano}/membros), controller e
 * service, mas NENHUM caller no frontend -- 1 linha no banco inteiro em 27/08/2026.
 *
 * O schema denuncia a duplicidade: aqui so havia nome/cargo/telefone, enquanto a
 * grade que o usuario ve exibe tambem CPF, celular e e-mail, que so existem em
 * `compdec_equipes`. Mantida, a tabela convidava a gravar equipe no lugar errado
 * e a divergir do que a tela mostra.
 *
 * A migration de criacao (2026_06_15_000005) fica onde esta: ambientes que ja a
 * rodaram precisam deste drop, e o down() recria a estrutura identica caso algum
 * ambiente precise voltar atras.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('pmda_compdec_membros');
    }

    public function down(): void
    {
        Schema::create('pmda_compdec_membros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pmda_plano_id')->constrained('pmda_planos')->cascadeOnDelete();
            $table->string('nome', 110);
            $table->string('cargo', 80)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
