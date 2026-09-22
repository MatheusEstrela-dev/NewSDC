<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Console;

use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Ranking\Services\SnapshotPlacar;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Throwable;

/**
 * Congela o placar publicado de um periodo.
 *
 * Uso:
 *   php artisan ranking:snapshot
 *   php artisan ranking:snapshot --periodo=mes:2026-09 --escopo=orgao
 *   php artisan ranking:snapshot --periodo=ano:2026 --forcar
 *   php artisan ranking:snapshot --dry-run
 *
 * Sem --escopo o comando congela as tres dimensoes do periodo, cada uma no seu
 * proprio snapshot: o schema tem um escopo por snapshot e usuario 7 e orgao 7
 * nao podem dividir a mesma publicacao.
 *
 * REEXECUCAO E SEGURA
 * Rodar duas vezes sem alteracao no livro nao publica revisao nova - o servico
 * detecta que nada mudou e informa. Isso e o que permite agendar o comando sem
 * inflar o historico de revisoes com copias identicas. --forcar republica
 * mesmo assim, para reemissao deliberada.
 */
class SnapshotCommand extends Command
{
    protected $signature = 'ranking:snapshot
                            {--periodo= : chave do periodo (ex.: mes:2026-09, ano:2026, acumulado); padrao: mes corrente}
                            {--period= : alias de --periodo; aceita 2026-09, 2026 ou chave completa}
                            {--escopo= : usuario|orgao|municipio; padrao: os tres}
                            {--forcar : publica nova revisao mesmo sem alteracao no livro}
                            {--dry-run : calcula e exibe o placar sem gravar nada}';

    protected $description = 'Congela o placar do periodo em ranking.snapshots, publicando uma nova revisao';

    public function handle(SnapshotPlacar $snapshot): int
    {
        $periodo = $this->periodoAlvo();

        if ($periodo === false) {
            return self::FAILURE;
        }

        $escopos = $this->escoposAlvo();

        if ($escopos === null) {
            $this->error('Escopo invalido. Use: usuario, orgao ou municipio.');

            return self::FAILURE;
        }

        $forcar = (bool) $this->option('forcar');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Simulacao (--dry-run): nada sera gravado.');
        }

        $linhas = [];
        $falhou = false;

        foreach ($escopos as $escopo) {
            try {
                $resultado = $snapshot->gerar(
                    periodoChave: $periodo,
                    escopo: $escopo,
                    forcar: $forcar,
                    dryRun: $dryRun,
                    motivo: $forcar ? 'reemissao solicitada em ranking:snapshot' : null,
                );
            } catch (Throwable $erro) {
                $this->error("[{$escopo->value}] {$erro->getMessage()}");
                $falhou = true;

                continue;
            }

            $linhas[] = [
                $escopo->value,
                $resultado['status'],
                $resultado['revisao'],
                $resultado['geracao'],
                $resultado['ledger_watermark'],
                $resultado['total_itens'],
            ];
        }

        if ($linhas !== []) {
            $this->table(
                ['escopo', 'status', 'revisao', 'geracao', 'watermark', 'itens'],
                $linhas,
            );
        }

        $this->info("Periodo: {$periodo}");

        return $falhou ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Chave estavel do periodo. Sem --periodo, o mes corrente: e o recorte que
     * o agendador congela, e a chave e derivada do enum para nao divergir da
     * que o placar usa.
     */
    private function periodoAlvo(): string|false
    {
        $informado = trim((string) ($this->option('periodo') ?? ''));
        $alias = trim((string) ($this->option('period') ?? ''));

        if ($informado !== '' && $alias !== '') {
            $this->error('Use somente uma das opcoes --periodo ou --period.');

            return false;
        }

        $valor = $informado !== '' ? $informado : $alias;

        if (preg_match('/^\d{4}-\d{2}$/', $valor) === 1) {
            return 'mes:'.$valor;
        }

        if (preg_match('/^\d{4}$/', $valor) === 1) {
            return 'ano:'.$valor;
        }

        if ($valor !== '') {
            return $valor;
        }

        return TipoPeriodo::Mes->chave(new DateTimeImmutable('now'));
    }

    /**
     * @return array<int, EscopoPlacar>|null null quando o valor informado nao e um escopo conhecido.
     */
    private function escoposAlvo(): ?array
    {
        $informado = trim((string) ($this->option('escopo') ?? ''));

        if ($informado === '') {
            return EscopoPlacar::cases();
        }

        $escopo = EscopoPlacar::tryFrom($informado);

        return $escopo === null ? null : [$escopo];
    }
}
