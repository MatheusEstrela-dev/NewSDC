<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Console;

use App\Modules\Cisterna\Domain\Etl\LeitorRaw;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaBeneficiarios;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaComunidades;
use App\Modules\Cisterna\Domain\Etl\Refinadores\Refinador;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaLotes;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaOrdensServico;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use Illuminate\Console\Command;
use Throwable;

/**
 * Etapa 2 do ETL: cisterna_legado_raw.doc jsonb -> tabelas do dominio.
 *
 * Idempotente por legacy_id: rodar duas vezes atualiza em vez de duplicar.
 * Cada linha e tratada isoladamente — um erro entra no cisterna_etl_log com
 * o payload de origem e a carga segue, em vez de abortar tudo.
 *
 * Mesmo padrao de AjudaHumanitaria\Console\RefinarLegadoAjuCommand.
 */
class RefinarCisternaLegadoCommand extends Command
{
    protected $signature = 'cisterna:refinar-legado
                            {--only= : Recursos separados por virgula: comunidades,lotes,os,beneficiarios,vistorias,itens,notificacoes,midia}
                            {--chunk=500 : Linhas por lote}
                            {--dry-run : Nao escreve no dominio; registra no cisterna_etl_log o que faria}';

    protected $description = 'Refina cisterna_legado_raw para as tabelas do dominio Cisterna.';

    public function handle(LeitorRaw $leitor): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        if ($dryRun) {
            $this->warn('DRY-RUN: nada sera escrito nas tabelas do dominio.');
        }

        $selecionados = $this->refinadoresSelecionados();

        if ($selecionados === []) {
            $this->error('Nenhum recurso reconhecido em --only. Conhecidos: '
                .implode(', ', array_keys($this->todosOsRefinadores())));

            return self::FAILURE;
        }

        foreach ($selecionados as $recurso => $refinador) {
            $total = $leitor->contar($refinador->tabelaLegado());

            if ($total === 0) {
                $this->warn("Sem linhas em cisterna_legado_raw para {$refinador->tabelaLegado()}. "
                    .'Rodar cisterna:extrair-legado primeiro.');
                continue;
            }

            $this->line("Refinando {$recurso} ({$total} linha(s))...");
            $barra = $this->output->createProgressBar($total);

            $leitor->porTabela(
                $refinador->tabelaLegado(),
                $chunk,
                function (array $doc, int $legacyId) use ($refinador, $dryRun, $barra): void {
                    try {
                        $refinador->refinar($doc, $legacyId, $dryRun);
                    } catch (Throwable $e) {
                        // Erro de uma linha nao derruba a carga inteira.
                        RegistroEtl::erro(
                            $refinador->recurso(),
                            $refinador->tabelaLegado(),
                            $legacyId,
                            'Excecao no refino: '.$e->getMessage(),
                            $doc,
                        );
                    }

                    $barra->advance();
                },
            );

            $barra->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info('Resumo do cisterna_etl_log:');

        foreach (RegistroEtl::resumo() as $acao => $quantidade) {
            $this->line(sprintf('  %-10s %6d', $acao, $quantidade));
        }

        $this->newLine();
        $this->line('Detalhe dos erros:');
        $this->line("  artisan cisterna:refinar-legado --dry-run  # sem escrever no dominio");
        $this->line('  SELECT recurso, motivo, COUNT(*) FROM cisterna_etl_log');
        $this->line("  WHERE acao = 'error' GROUP BY 1, 2 ORDER BY 3 DESC;");

        return self::SUCCESS;
    }

    /**
     * @return array<string, Refinador>
     */
    private function todosOsRefinadores(): array
    {
        // A ordem importa: comunidades, lotes e OS antes dos beneficiarios,
        // que resolvem FK contra os tres. Vistorias e midia entram na Task 18.
        return [
            'comunidades' => app(RefinaComunidades::class),
            'lotes' => app(RefinaLotes::class),
            'os' => app(RefinaOrdensServico::class),
            'beneficiarios' => app(RefinaBeneficiarios::class),
        ];
    }

    /**
     * @return array<string, Refinador>
     */
    private function refinadoresSelecionados(): array
    {
        $todos = $this->todosOsRefinadores();
        $only = $this->option('only');

        if ($only === null || trim((string) $only) === '') {
            return $todos;
        }

        $pedidos = array_map('trim', explode(',', (string) $only));

        return array_filter(
            $todos,
            fn (string $recurso): bool => in_array($recurso, $pedidos, true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
