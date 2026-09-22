<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Console;

use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Services\ReconcileLeaderboard;
use Illuminate\Console\Command;
use Throwable;

/**
 * Confere a projecao do placar contra o livro de pontos.
 *
 * Uso:
 *   php artisan ranking:reconcile
 *   php artisan ranking:reconcile --periodo=mes:2026-09
 *   php artisan ranking:reconcile --corrigir
 *
 * GATE DE MONITORAMENTO
 * Sem divergencia sai com 0; com divergencia sai diferente de zero. E o que
 * permite agendar o comando e tratar a saida como alarme - um placar errado nao
 * se denuncia sozinho, ele continua respondendo com o numero errado.
 *
 * SEM --corrigir NAO ESCREVE NADA. Divergencia e sintoma de defeito no caminho
 * de escrita; corrigir calado apagaria a evidencia e o defeito voltaria amanha.
 */
class ReconcileCommand extends Command
{
    protected $signature = 'ranking:reconcile
                            {--periodo= : chave do periodo (mes:2026-09, ano:2026, acumulado); vazio confere todos}
                            {--escopo= : usuario|orgao|municipio; vazio confere as tres dimensoes}
                            {--geracao= : geracao da projecao; vazio usa a ativa (a maior publicada)}
                            {--corrigir : aplica no placar o valor do livro (nao e o padrao)}
                            {--dry-run : confere e detalha sem corrigir; incompativel com --corrigir}
                            {--limite=20 : quantas divergencias detalhar na saida}';

    protected $description = 'Compara o livro de pontos com a projecao do placar e reporta divergencias';

    public function handle(ReconcileLeaderboard $conciliacao): int
    {
        if ($this->option('dry-run') && $this->option('corrigir')) {
            $this->error('--dry-run e --corrigir nao podem ser usados juntos.');

            return self::FAILURE;
        }

        $escopo = $this->escopo();

        if ($escopo === false) {
            return self::FAILURE;
        }

        $periodo = $this->periodo();
        $geracao = $this->option('geracao') === null ? null : (int) $this->option('geracao');
        $limite = max(1, (int) $this->option('limite'));

        try {
            $relatorio = $conciliacao->conciliar($periodo, $escopo, $geracao, $limite);
        } catch (Throwable $erro) {
            $this->error('Conciliacao abortada: '.$erro->getMessage());

            return self::FAILURE;
        }

        $this->line(sprintf(
            'Conciliacao da geracao %d: periodo=%s escopo=%s',
            $relatorio['geracao'],
            $periodo ?? 'todos',
            $escopo?->value ?? 'todos',
        ));

        if ($relatorio['total'] === 0) {
            $this->info('Livro e projecao batem: nenhuma divergencia.');

            return self::SUCCESS;
        }

        $this->error(sprintf('%d divergencia(s) entre o livro e a projecao.', $relatorio['total']));
        $this->detalhar($relatorio, $limite);

        if (! $this->option('corrigir')) {
            $this->comment('Nada foi alterado. Rode com --corrigir para aplicar o valor do livro.');

            return self::FAILURE;
        }

        $corrigidas = $conciliacao->corrigir($periodo, $escopo, $relatorio['geracao']);
        $this->info("Linhas corrigidas pelo livro: {$corrigidas}.");

        // Confere de novo: correcao que nao restaura a invariante e pior que
        // divergencia conhecida, porque some do relatorio sem ter resolvido.
        $depois = $conciliacao->conciliar($periodo, $escopo, $relatorio['geracao'], $limite);

        if ($depois['total'] > 0) {
            $this->error(sprintf(
                'Ainda restam %d divergencia(s) depois da correcao. O defeito nao esta na projecao.',
                $depois['total'],
            ));
            $this->detalhar($depois, $limite);

            return self::FAILURE;
        }

        $this->info('Projecao reconciliada com o livro.');

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $relatorio */
    private function detalhar(array $relatorio, int $limite): void
    {
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
                array_slice($relatorio['divergencias'], 0, $limite),
            ),
        );

        if ($relatorio['total'] > $limite) {
            $this->comment(sprintf(
                '... e mais %d linha(s). Use --limite para ver mais.',
                $relatorio['total'] - $limite,
            ));
        }
    }

    private function periodo(): ?string
    {
        $periodo = trim((string) $this->option('periodo'));

        return $periodo === '' ? null : $periodo;
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
