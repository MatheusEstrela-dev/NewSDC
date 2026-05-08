<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Compdec\Services\AnexoService;
use App\Modules\Compdec\Services\EquipeService;
use App\Modules\Compdec\Services\OrgaoService;
use App\Modules\Compdec\Services\PrefeituraService;
use App\Modules\Compdec\Support\MigracaoReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * ETL legado (sdc) -> novo (NewSDC/SDC) para o modulo COMPDEC.
 *
 * F1: orgaos + prefeituras.
 * F2-F4: equipes, anexos, planos (a serem implementados nas proximas fases).
 *
 * Idempotente via legacy_id. Suporta dry-run, particionamento por --only e
 * recuperacao parcial via --continue-on-error.
 */
class MigrarCompdecLegadoCommand extends Command
{
    protected $signature = 'compdec:migrar-legado
                            {--dry-run : Simula sem persistir}
                            {--only= : Recursos a migrar (csv): orgaos,prefeituras,equipes,anexos,planos}
                            {--municipio= : Migra apenas o municipio_id especificado (futuro)}
                            {--chunk=100 : Tamanho do chunk de leitura}
                            {--continue-on-error : Continua mesmo apos erros}';

    protected $description = 'Migra dados do banco legado (com_comdec, cedec_prefeitura, etc.) para o schema novo do modulo COMPDEC.';

    /** @var array<int, string> */
    private const RECURSOS_DISPONIVEIS = ['orgaos', 'prefeituras', 'equipes', 'anexos'];

    /** @var array<int, string> */
    private const RECURSOS_FUTUROS = ['planos'];

    public function __construct(
        private readonly OrgaoService $orgaoService,
        private readonly PrefeituraService $prefeituraService,
        private readonly EquipeService $equipeService,
        private readonly AnexoService $anexoService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $continueOnError = (bool) $this->option('continue-on-error');
        $recursos = $this->resolverRecursos();

        $this->info(sprintf(
            '== ETL COMPDEC ==  dry-run=%s  chunk=%d  recursos=%s',
            $dryRun ? 'sim' : 'nao',
            $chunk,
            implode(',', $recursos),
        ));

        if (! $this->validarPreRequisitos()) {
            return self::FAILURE;
        }

        if (! $dryRun && ! $this->confirm('Confirma migracao em modo persistente?', false)) {
            $this->warn('Cancelado pelo usuario.');

            return self::FAILURE;
        }

        $reports = [];

        foreach ($recursos as $recurso) {
            try {
                $reports[$recurso] = $this->executar($recurso, $chunk, $dryRun);
            } catch (Throwable $e) {
                $this->error("Erro fatal em {$recurso}: {$e->getMessage()}");
                if (! $continueOnError) {
                    return self::FAILURE;
                }
            }
        }

        $this->imprimirSumario($reports);

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function resolverRecursos(): array
    {
        $only = (string) $this->option('only');

        if ($only === '') {
            return self::RECURSOS_DISPONIVEIS;
        }

        $solicitados = array_map('trim', explode(',', $only));
        $futuros = array_intersect($solicitados, self::RECURSOS_FUTUROS);

        if ($futuros !== []) {
            $this->warn('Recursos ainda nao implementados (F3-F4): ' . implode(',', $futuros));
        }

        return array_values(array_intersect($solicitados, self::RECURSOS_DISPONIVEIS));
    }

    private function validarPreRequisitos(): bool
    {
        $connection = (string) config('compdec.legacy_connection', 'legacy');

        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $e) {
            $this->error("Conexao legada '{$connection}' nao disponivel: {$e->getMessage()}");

            return false;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('compdec_orgaos')) {
            $this->error('Tabela compdec_orgaos nao existe. Rode as migrations da F1 antes do ETL.');

            return false;
        }

        return true;
    }

    private function executar(string $recurso, int $chunk, bool $dryRun): MigracaoReport
    {
        $this->newLine();
        $this->line("--> {$recurso}");

        return match ($recurso) {
            'orgaos' => $this->orgaoService->migrarLegado($chunk, $dryRun),
            'prefeituras' => $this->prefeituraService->migrarLegado($chunk, $dryRun),
            'equipes' => $this->equipeService->migrarLegado($chunk, $dryRun),
            'anexos' => $this->anexoService->migrarLegado($chunk, $dryRun),
            default => throw new \InvalidArgumentException("Recurso desconhecido: {$recurso}"),
        };
    }

    /**
     * @param  array<string, MigracaoReport>  $reports
     */
    private function imprimirSumario(array $reports): void
    {
        $this->newLine();
        $this->info('== Sumario ==');

        $linhas = [];
        foreach ($reports as $recurso => $report) {
            $linhas[] = [
                $recurso,
                $report->total(),
                $report->inseridos,
                $report->atualizados,
                $report->ignorados,
                $report->erros,
            ];
        }

        $this->table(
            ['Recurso', 'Total', 'Inseridos', 'Atualizados', 'Ignorados', 'Erros'],
            $linhas,
        );

        $totalErros = array_sum(array_map(fn (MigracaoReport $r): int => $r->erros, $reports));

        if ($totalErros > 0) {
            $this->warn("Houve {$totalErros} erro(s). Detalhes em compdec_etl_log.");
        }
    }
}
