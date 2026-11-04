<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Troca ON DELETE CASCADE por ON DELETE RESTRICT nas duas FKs da cadeia
 * cronograma -> alocacao -> viagem (criadas em 2026_10_20_100000).
 *
 * Com CASCADE, um DELETE manual em `tdap_cronogramas` levava junto as
 * alocacoes e, delas, as viagens -- inclusive viagens APROVADAS, que tem
 * efeito financeiro. Foi exatamente esse tipo de DELETE fora da aplicacao que
 * produziu as 206 viagens aprovadas orfas arquivadas em 2026_10_20_100000.
 * Com RESTRICT o banco recusa o DELETE do pai enquanto houver filho (inclusive
 * filho soft-deleted): quem precisar apagar de verdade apaga os filhos antes,
 * de proposito.
 *
 * A aplicacao nao depende do cascade: Cronograma, CronoCaminhao e CronoViagem
 * usam SoftDeletes e os services so chamam ->delete() (UPDATE deleted_at), que
 * nao dispara FK. Nenhum forceDelete, truncate ou DELETE fisico dessas tabelas
 * em app/, database/seeders ou app/Console (grep em 2026-11-04). Os pais de
 * `tdap_cronogramas` (atas, lotes, municipios, prestadores) ja sao RESTRICT,
 * entao nenhum cascade de cima chega ate aqui.
 *
 * ON UPDATE CASCADE fica como estava. DROP + ADD na mesma transacao da
 * migration: nao ha janela sem FK. Nao precisa de NOT VALID: a FK anterior ja
 * garantia as mesmas linhas, a validacao so relê a tabela.
 */
return new class extends Migration
{
    private const FKS = [
        'tdap_crono_caminhoes_cronograma_id_foreign' => [
            'tabela' => 'tdap_crono_caminhoes',
            'coluna' => 'cronograma_id',
            'pai' => 'tdap_cronogramas',
        ],
        'tdap_crono_viagens_crono_caminhao_id_foreign' => [
            'tabela' => 'tdap_crono_viagens',
            'coluna' => 'crono_caminhao_id',
            'pai' => 'tdap_crono_caminhoes',
        ],
    ];

    public function up(): void
    {
        $this->recriar('RESTRICT');
    }

    public function down(): void
    {
        $this->recriar('CASCADE');
    }

    private function recriar(string $aoApagar): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::FKS as $fk => $ref) {
            DB::statement("ALTER TABLE {$ref['tabela']} DROP CONSTRAINT IF EXISTS {$fk}");
            DB::statement(
                "ALTER TABLE {$ref['tabela']} ADD CONSTRAINT {$fk}".
                " FOREIGN KEY ({$ref['coluna']}) REFERENCES {$ref['pai']} (id)".
                " ON UPDATE CASCADE ON DELETE {$aoApagar}"
            );
        }
    }
};
