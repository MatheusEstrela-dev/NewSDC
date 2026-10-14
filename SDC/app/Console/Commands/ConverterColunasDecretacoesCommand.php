<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * #83: converte as colunas de Decretacoes que vieram do legado como varchar.
 *
 *   dec_entrada_processos.data_publicacao_diario  varchar -> date
 *   dec_entrada_processos.created_by              varchar -> bigint + FK users
 *
 * Comando, e nao migration, de proposito: a conversao so pode rodar DEPOIS de
 * sanear as linhas ALERTA do `migracao:validar --secao=decretacoes`, e isso
 * acontece na VM, com o dump carregado. Uma migration rodaria sozinha no
 * deploy -- ou apagando os valores que nao convertem, ou travando o
 * `migrate` inteiro ate alguem sanear.
 *
 * Recusa enquanto houver valor que nao converte (nada e alterado), roda numa
 * transacao e e idempotente: com as colunas ja convertidas, so informa.
 */
class ConverterColunasDecretacoesCommand extends Command
{
    protected $signature = 'migracao:converter-decretacoes
        {--dry-run : So mostra o que faria, sem alterar o banco}';

    protected $description = 'Converte data_publicacao_diario para date e created_by para bigint (FK users) em dec_entrada_processos, apos o saneamento (#83).';

    private const TABELA = 'dec_entrada_processos';

    public function handle(): int
    {
        $tipos = $this->tiposAtuais();
        $converterData = $tipos['data_publicacao_diario'] !== 'date';
        $converterAutor = $tipos['created_by'] !== 'bigint';

        if (! $converterData && ! $converterAutor) {
            $this->info('As colunas ja estao convertidas (date e bigint). Nada a fazer.');

            return self::SUCCESS;
        }

        $bloqueios = $this->bloqueios($converterData, $converterAutor);
        if ($bloqueios !== []) {
            $this->error('Ha valores que nao convertem. Nada foi alterado.');
            $this->table(['Problema', 'Linhas', 'Exemplos (id)'], $bloqueios);
            $this->line('Saneie com a lista de `php artisan migracao:validar --secao=decretacoes` e rode de novo.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run: todas as linhas convertem. Rode sem --dry-run para aplicar.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($converterData, $converterAutor): void {
            if ($converterData) {
                DB::statement(<<<'SQL'
                    ALTER TABLE dec_entrada_processos
                    ALTER COLUMN data_publicacao_diario TYPE date USING (
                        CASE
                            WHEN data_publicacao_diario ~ '^\d{4}-\d{2}-\d{2}$' THEN data_publicacao_diario::date
                            WHEN data_publicacao_diario ~ '^\d{2}/\d{2}/\d{4}$' THEN to_date(data_publicacao_diario, 'DD/MM/YYYY')
                            ELSE NULL
                        END
                    )
                    SQL);
            }

            if ($converterAutor) {
                DB::statement('ALTER TABLE dec_entrada_processos ALTER COLUMN created_by TYPE bigint USING created_by::bigint');
                DB::statement('ALTER TABLE dec_entrada_processos ADD CONSTRAINT dec_entrada_processos_created_by_foreign FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT');
            }
        });

        $this->info('Colunas convertidas: ' . implode(', ', array_filter([
            $converterData ? 'data_publicacao_diario -> date' : null,
            $converterAutor ? 'created_by -> bigint (FK users)' : null,
        ])) . '.');

        return self::SUCCESS;
    }

    /** @return array{data_publicacao_diario: string, created_by: string} */
    private function tiposAtuais(): array
    {
        $tipos = DB::table('information_schema.columns')
            ->where('table_name', self::TABELA)
            ->whereIn('column_name', ['data_publicacao_diario', 'created_by'])
            ->pluck('data_type', 'column_name');

        return [
            'data_publicacao_diario' => (string) ($tipos['data_publicacao_diario'] ?? ''),
            'created_by'             => (string) ($tipos['created_by'] ?? ''),
        ];
    }

    /**
     * Mesmas regras das linhas ALERTA do migracao:validar, mais o calendario:
     * "31/02/2024" passa no formato e quebra no to_date. Inclui linhas
     * excluidas (soft delete): o ALTER converte a tabela inteira.
     *
     * @return list<array{0: string, 1: int, 2: string}>
     */
    private function bloqueios(bool $data, bool $autor): array
    {
        $bloqueios = [];

        if ($data) {
            $foraDoFormato = DB::table(self::TABELA)
                ->whereNotNull('data_publicacao_diario')
                ->where('data_publicacao_diario', '!=', '')
                ->whereRaw("data_publicacao_diario !~ '^\\d{4}-\\d{2}-\\d{2}$'")
                ->whereRaw("data_publicacao_diario !~ '^\\d{2}/\\d{2}/\\d{4}$'");
            $this->registrar($bloqueios, 'data_publicacao_diario fora de AAAA-MM-DD ou DD/MM/AAAA', $foraDoFormato);

            $invalidas = DB::table(self::TABELA)
                ->whereRaw("data_publicacao_diario ~ '^\\d{4}-\\d{2}-\\d{2}$' OR data_publicacao_diario ~ '^\\d{2}/\\d{2}/\\d{4}$'")
                ->get(['id', 'data_publicacao_diario'])
                ->filter(function ($linha): bool {
                    $v = $linha->data_publicacao_diario;
                    [$a, $m, $d] = str_contains($v, '/') ? array_reverse(explode('/', $v)) : explode('-', $v);

                    return ! checkdate((int) $m, (int) $d, (int) $a);
                });
            if ($invalidas->isNotEmpty()) {
                $bloqueios[] = ['data_publicacao_diario com data inexistente (ex.: 31/02)', $invalidas->count(), $invalidas->take(5)->pluck('id')->implode(', ')];
            }
        }

        if ($autor) {
            $this->registrar($bloqueios, 'created_by vazio ou nao numerico',
                DB::table(self::TABELA)->where(fn ($q) => $q->whereNull('created_by')->orWhereRaw("created_by !~ '^[0-9]+$'")));
            $this->registrar($bloqueios, 'created_by sem usuario correspondente',
                DB::table(self::TABELA)->whereRaw("created_by ~ '^[0-9]+$'")
                    ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('users')->whereRaw('users.id::text = dec_entrada_processos.created_by')));
        }

        return $bloqueios;
    }

    /** @param list<array{0: string, 1: int, 2: string}> $bloqueios */
    private function registrar(array &$bloqueios, string $problema, \Illuminate\Database\Query\Builder $query): void
    {
        $total = (clone $query)->count();
        if ($total > 0) {
            $bloqueios[] = [$problema, $total, (clone $query)->orderBy('id')->limit(5)->pluck('id')->implode(', ')];
        }
    }
}
