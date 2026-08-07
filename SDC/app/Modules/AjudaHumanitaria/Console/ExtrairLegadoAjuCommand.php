<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Console;

use App\Modules\AjudaHumanitaria\Domain\Etl\MapaTabelasLegado;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Copia as tabelas aju_* da base legada para a area de pouso, como documento.
 *
 * Somente leitura no legado: o comando executa apenas SELECT na conexao
 * configurada em ajuda-humanitaria.legacy_connection.
 *
 * Idempotente por (tabela, pk_legado): reexecutar atualiza o documento em vez
 * de duplicar, entao a carga pode rodar quantas vezes for preciso ate o corte.
 *
 * O mapa de tabelas e a uniao das duas bases legadas conhecidas. Tabela ausente
 * na origem e pulada, nao e erro: e a outra base.
 */
final class ExtrairLegadoAjuCommand extends Command
{
    protected $signature = 'legado:aju:extrair
        {--tabela=* : Limita a extracao a estas tabelas}
        {--chunk=1000 : Linhas por lote}';

    protected $description = 'Extrai as tabelas aju_* da base legada para ajuda_h_legado_raw';

    public function handle(): int
    {
        $conexao = (string) config('ajuda-humanitaria.legacy_connection', 'legacy');

        try {
            DB::connection($conexao)->getPdo();
        } catch (Throwable $erro) {
            $this->error("Conexao legada indisponivel ({$conexao}): {$erro->getMessage()}");

            return self::FAILURE;
        }

        $escolhidas = (array) $this->option('tabela');
        $tabelas    = $escolhidas === []
            ? MapaTabelasLegado::tabelas()
            : array_intersect_key(MapaTabelasLegado::tabelas(), array_flip($escolhidas));

        if ($tabelas === []) {
            $this->error('Nenhuma tabela conhecida entre as informadas.');

            return self::FAILURE;
        }

        $schema  = Schema::connection($conexao);
        $total   = 0;
        $puladas = 0;

        foreach (array_keys($tabelas) as $tabela) {
            if (! $schema->hasTable($tabela)) {
                $this->line(sprintf('%-20s ausente nesta base, pulada', $tabela));
                $puladas++;

                continue;
            }

            $chave = MapaTabelasLegado::resolverChave($tabela, $schema->getColumnListing($tabela));

            // Sem chave resolvida, pk_legado sairia vazio e o upsert colapsaria
            // a tabela inteira em uma linha. Falha alto em vez de corromper.
            if ($chave === null) {
                $this->error(sprintf(
                    'Nenhuma coluna de chave (%s) existe em %s. Corrija MapaTabelasLegado.',
                    implode(', ', MapaTabelasLegado::candidatasChave($tabela)),
                    $tabela
                ));

                return self::FAILURE;
            }

            $extraidas = 0;

            DB::connection($conexao)
                ->table($tabela)
                ->orderBy($chave)
                ->chunk((int) $this->option('chunk'), function ($linhas) use ($tabela, $chave, &$extraidas): void {
                    $lote = [];

                    foreach ($linhas as $linha) {
                        $dados = (array) $linha;

                        $lote[] = [
                            'tabela'      => $tabela,
                            'pk_legado'   => (string) $dados[$chave],
                            'doc'         => json_encode($dados, JSON_UNESCAPED_UNICODE),
                            'extraido_em' => now(),
                        ];
                    }

                    DB::table('ajuda_h_legado_raw')
                        ->upsert($lote, ['tabela', 'pk_legado'], ['doc', 'extraido_em']);

                    $extraidas += count($lote);
                });

            $this->line(sprintf('%-20s %6d linhas  (chave: %s)', $tabela, $extraidas, $chave));
            $total += $extraidas;
        }

        $this->info(sprintf(
            'Extracao concluida: %d linhas em %d tabelas (%d puladas).',
            $total,
            count($tabelas) - $puladas,
            $puladas
        ));

        return self::SUCCESS;
    }
}
