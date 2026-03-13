<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Remove as 14 tabelas legadas da arquitetura relacional do RAT.
 *
 * O modelo atual usa a tabela `rats` com colunas JSON para todas as sub-entidades.
 * As tabelas abaixo foram abandonadas e nunca são utilizadas pela aplicação.
 * Ordem de remoção segue dependências de chave estrangeira (dependentes primeiro).
 */
return new class extends Migration
{
    private const LEGACY_TABLES = [
        'rat_recursos_componentes_guarnicao',
        'rat_recursos_empregados',
        'rat_relato_recursos',
        'rat_ocorrencia_relatos',
        'rat_ocorrencias',
        'rat_relato_envolvidos',
        'rat_relato_vistoria',
        'rat_bem_afetado',
        'rat_encaminhamento',
        'rat_orgao_acionado',
        'rat_patologia',
        'rat_redec',
        'rat_dados_gerais',
        'rat_veiculos',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (self::LEGACY_TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Tabelas legadas removidas intencionalmente.
        // Caso necessário, restaurar a partir de um backup do banco de dados.
    }
};
