<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Remove todas as tabelas RAT legadas/polimórficas, mantendo apenas rat_ocorrencias.
 * A tabela rat_ocorrencias é a única fonte de verdade do módulo RAT.
 */
return new class extends Migration
{
    /** Tabelas a remover — ordenadas para respeitar foreign keys. */
    private array $tablesToDrop = [
        'rat_ocorrencia_historico',
        'rat_tipos',
        'rat_veiculos',
        'rat_relato_vistoria',
        'rat_relato_envolvidos',
        'rat_dados_gerais',
        'rat_redec',
        'rat_patologia',
        'rat_acionado',
        'rat_encaminhamento',
        'rat_bem_afetado',
        'rat_recursos_componentes_guarnicao',
        'rat_recursos_empregados',
        'rat_relato_recursos',
        'rat_ocorrencia_relatos',
        'rats',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($this->tablesToDrop as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Rollback não recria as tabelas — use os backups ou recriar manualmente se necessário.
    }
};
