<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Console;

use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Services\RebuildLeaderboard;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

/**
 * Reconstroi ranking.saldos a partir do livro de pontos.
 *
 * Uso:
 *   php artisan ranking:rebuild --dry-run
 *   php artisan ranking:rebuild
 *   php artisan ranking:rebuild --periodo=mes:2026-09 --escopo=usuario
 *
 * A reconstrucao publica uma geracao NOVA e nao encosta na que esta no ar: o
 * placar continua respondendo durante o recalculo e a troca acontece no COMMIT.
 * Ver RebuildLeaderboard para o tratamento do delta concorrente.
 *
 * --dry-run nao e cortesia: escrita massiva sem conferencia previa e como o
 * placar fica errado sem ninguem perceber. Ele compara o livro com a projecao
 * ativa e mostra exatamente o que mudaria, sem gravar nada.
 */
class RebuildCommand extends Command
{
    protected $signature = 'ranking:rebuild
                            {--periodo= : chave do periodo (mes:2026-09, ano:2026, acumulado); vazio reconstroi todos}
                            {--period= : alias de --periodo; aceita 2026-09, 2026 ou chave completa}
                            {--escopo= : usuario|orgao|municipio; vazio reconstroi as tres dimensoes}
                            {--dry-run : calcula e compara sem escrever nada}
                            {--recusar-delta : aborta em vez de incorporar o que chegar durante a reconstrucao}
                            {--limite=20 : quantas divergencias detalhar na saida}';

    protected $description = 'Reconstroi a projecao do placar a partir do livro de pontos, em geracao nova';

    public function handle(RebuildLeaderboard $reconstrucao): int
    {
        $escopo = $this->escopo();

        if ($escopo === false) {
            return self::FAILURE;
        }

        $periodo = $this->periodo();

        if ($periodo === false) {
            return self::FAILURE;
        }
        $dryRun = (bool) $this->option('dry-run');
        $limite = max(1, (int) $this->option('limite'));

        $this->info($dryRun
            ? 'Simulando a reconstrucao do placar (nada sera escrito).'
            : 'Reconstruindo o placar a partir do livro de pontos.');

        $this->line('  recorte: periodo='.($periodo ?? 'todos').' escopo='.($escopo?->value ?? 'todos'));

        try {
            $resultado = $reconstrucao->executar(
                periodoChave: $periodo,
                escopo: $escopo,
                dryRun: $dryRun,
                recusarDelta: (bool) $this->option('recusar-delta'),
                limiteDivergencias: $limite,
            );
        } catch (RuntimeException $erro) {
            // Recusa por delta e decisao de negocio, nao defeito: a mensagem ja
            // diz o que chegou durante a reconstrucao e que nada foi publicado.
            $this->error($erro->getMessage());

            return self::FAILURE;
        } catch (Throwable $erro) {
            $this->error('Reconstrucao abortada: '.$erro->getMessage());

            return self::FAILURE;
        }

        $this->resumo($resultado);
        $this->divergencias($resultado, $limite);

        if ($dryRun) {
            $this->comment('Dry-run: nenhuma linha foi escrita. Rode sem --dry-run para publicar.');

            return self::SUCCESS;
        }

        if ($resultado['divergencias_total'] > 0) {
            $this->error(sprintf(
                'Geracao %d publicada com %d divergencia(s) contra o livro. Investigue antes de confiar no placar.',
                (int) $resultado['geracao_nova'],
                $resultado['divergencias_total'],
            ));

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Geracao %d publicada e conferida contra o livro. Aponte a leitura do placar para ela.',
            (int) $resultado['geracao_nova'],
        ));

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $resultado */
    private function resumo(array $resultado): void
    {
        $this->table(
            ['indicador', 'valor'],
            [
                ['geracao ativa', $resultado['geracao_ativa'] ?? '(projecao vazia)'],
                ['geracao publicada', $resultado['geracao_nova'] ?? '(nenhuma)'],
                ['watermark inicial', $resultado['watermark_inicial']],
                ['watermark final', $resultado['watermark_final']],
                ['lancamentos no delta', $resultado['delta_lancamentos']],
                ['confirmacoes tardias', $resultado['delta_confirmacoes']],
                ['periodos criados', $resultado['periodos_criados']],
                ['linhas projetadas', $resultado['linhas_projetadas']],
                ['linhas do delta', $resultado['linhas_delta']],
                ['linhas copiadas do recorte nao alvo', $resultado['linhas_copiadas']],
                ['divergencias contra o livro', $resultado['divergencias_total']],
            ],
        );

        if ($resultado['periodos_ausentes'] !== []) {
            $this->warn(
                'Periodos que o livro exige e ainda nao existem (seriam criados na execucao): '
                .implode(', ', $resultado['periodos_ausentes'])
            );
        }
    }

    /** @param array<string, mixed> $resultado */
    private function divergencias(array $resultado, int $limite): void
    {
        if ($resultado['divergencias'] === []) {
            return;
        }

        $this->line('');
        $this->line($resultado['dry_run']
            ? 'O que a reconstrucao mudaria na geracao ativa:'
            : 'Divergencias remanescentes na geracao publicada:');

        $this->table(
            ['periodo', 'escopo', 'entidade', 'modulo', 'livro', 'projecao', 'diferenca'],
            array_map(
                static fn (array $d): array => [
                    $d['periodo_chave'],
                    $d['escopo'],
                    $d['entidade_id'],
                    $d['modulo'],
                    $d['pontos_livro'],
                    $d['pontos_projecao'],
                    $d['diferenca'],
                ],
                array_slice($resultado['divergencias'], 0, $limite),
            ),
        );

        if ($resultado['divergencias_total'] > $limite) {
            $this->comment(sprintf(
                '... e mais %d linha(s). Use --limite para ver mais.',
                $resultado['divergencias_total'] - $limite,
            ));
        }
    }

    private function periodo(): string|null|false
    {
        $periodo = trim((string) ($this->option('periodo') ?? ''));
        $alias = trim((string) ($this->option('period') ?? ''));

        if ($periodo !== '' && $alias !== '') {
            $this->error('Use somente uma das opcoes --periodo ou --period.');

            return false;
        }

        $valor = $periodo !== '' ? $periodo : $alias;

        if (preg_match('/^\d{4}-\d{2}$/', $valor) === 1) {
            return 'mes:'.$valor;
        }

        if (preg_match('/^\d{4}$/', $valor) === 1) {
            return 'ano:'.$valor;
        }

        return $valor === '' ? null : $valor;
    }

    /** @return EscopoPlacar|null|false false sinaliza opcao invalida */
    private function escopo(): EscopoPlacar|null|false
    {
        $escopo = trim((string) $this->option('escopo'));

        if ($escopo === '') {
            return null;
        }

        $resolvido = EscopoPlacar::tryFrom($escopo);

        if ($resolvido === null) {
            $this->error(sprintf(
                "Escopo invalido: '%s'. Use um de: %s.",
                $escopo,
                implode(', ', array_column(EscopoPlacar::cases(), 'value')),
            ));

            return false;
        }

        return $resolvido;
    }
}
