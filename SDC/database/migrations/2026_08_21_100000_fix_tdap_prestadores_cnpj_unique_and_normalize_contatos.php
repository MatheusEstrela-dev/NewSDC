<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dois defeitos do cadastro de Prestador, no mesmo lugar onde nasceram.
 *
 * 1) UNICIDADE x SOFT DELETE
 *    `cnpj` tinha UNIQUE simples, mas o model usa SoftDeletes e a validacao usa
 *    `Rule::unique(...)->whereNull('deleted_at')`. Recadastrar um prestador
 *    excluido passava na validacao e estourava 23505 no INSERT -- erro 500 em
 *    vez de mensagem de negocio. O indice passa a ser PARCIAL, casando com a
 *    regra da aplicacao: o CNPJ e unico entre os prestadores VIVOS.
 *
 * 2) CONTATOS MASCARADOS NO ACERVO LEGADO
 *    O import gravou telefone e CEP com mascara -- e alguns truncados, um deles
 *    com o placeholder do front vazado ('(38)34899-88_'). O PrestadorDTO grava
 *    somente digitos, entao a coluna tinha dois formatos ao mesmo tempo e a
 *    busca por telefone nao achava metade da base. Aqui a base inteira vira
 *    digitos, que e o contrato de armazenamento (ver Tdap\Support\Documento).
 *
 *    Numero que sobra fora de 10/11 digitos NAO e descartado nem "consertado":
 *    fica em digitos, incompleto, para o cadastro corrigir na fonte. Inventar
 *    digito em telefone de prestador seria pior que exibi-lo curto.
 *
 * Idempotente: pode rodar duas vezes sem efeito colateral.
 */
return new class extends Migration
{
    private const CNPJ_UNIQUE_LEGADO = 'tdap_prestadores_cnpj_unique';

    private const CNPJ_UNIQUE_PARCIAL = 'tdap_prestadores_cnpj_ativos_unique';

    public function up(): void
    {
        $this->normalizarContatos();
        $this->trocarUniqueDoCnpjPorParcial();
    }

    public function down(): void
    {
        // A normalizacao de contatos nao volta: a mascara original nao pode ser
        // reconstruida a partir dos digitos (e nao deveria).
        DB::statement('DROP INDEX IF EXISTS '.self::CNPJ_UNIQUE_PARCIAL);

        if (! $this->indiceExiste(self::CNPJ_UNIQUE_LEGADO)) {
            Schema::table('tdap_prestadores', function ($table): void {
                $table->unique('cnpj');
            });
        }
    }

    /** Remove tudo que nao e digito de cnpj, tel1, tel2 e cep. */
    private function normalizarContatos(): void
    {
        foreach (['cnpj', 'tel1', 'tel2', 'cep'] as $coluna) {
            DB::statement(<<<SQL
                UPDATE tdap_prestadores
                   SET {$coluna} = NULLIF(REGEXP_REPLACE({$coluna}, '\\D', '', 'g'), '')
                 WHERE {$coluna} IS NOT NULL
                   AND {$coluna} <> COALESCE(NULLIF(REGEXP_REPLACE({$coluna}, '\\D', '', 'g'), ''), '')
            SQL);
        }
    }

    /**
     * Troca o UNIQUE simples por indice parcial `WHERE deleted_at IS NULL`.
     *
     * A ordem importa: cria o parcial ANTES de derrubar o antigo, para que a
     * tabela nunca fique sem protecao contra CNPJ duplicado.
     */
    private function trocarUniqueDoCnpjPorParcial(): void
    {
        if (! $this->indiceExiste(self::CNPJ_UNIQUE_PARCIAL)) {
            DB::statement(
                'CREATE UNIQUE INDEX '.self::CNPJ_UNIQUE_PARCIAL.
                ' ON tdap_prestadores (cnpj) WHERE deleted_at IS NULL'
            );
        }

        if ($this->indiceExiste(self::CNPJ_UNIQUE_LEGADO)) {
            // O UNIQUE do Laravel nasce como constraint em algumas versoes e
            // como indice puro em outras; tenta na ordem e ignora o que falhar.
            try {
                DB::statement('ALTER TABLE tdap_prestadores DROP CONSTRAINT '.self::CNPJ_UNIQUE_LEGADO);
            } catch (\Throwable) {
                DB::statement('DROP INDEX IF EXISTS '.self::CNPJ_UNIQUE_LEGADO);
            }
        }
    }

    private function indiceExiste(string $nome): bool
    {
        return DB::table('pg_indexes')
            ->where('tablename', 'tdap_prestadores')
            ->where('indexname', $nome)
            ->exists();
    }
};
