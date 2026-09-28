<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Console;

use App\Modules\Demandas\Importacao\EtapaImportacao;
use App\Modules\Demandas\Support\ContextoImportacao;
use Illuminate\Console\Command;

class ImportarLegadoCommand extends Command
{
    protected $signature = 'demandas:importar-legado
        {--dry-run : Simula e mostra o relatorio sem gravar}
        {--etapa= : usuarios, catalogo, chamados, historico, comentarios ou anexos}
        {--desde= : So registros com updated_at a partir desta data (delta de corte)}
        {--lote=200 : Tamanho do lote}';

    protected $description = 'Importa chamados do cedec-demanda para Demandas, de forma idempotente';

    /** @param iterable<EtapaImportacao> $etapas */
    public function handle(ContextoImportacao $contexto): int
    {
        /** @var iterable<EtapaImportacao> $etapas */
        $etapas = app()->tagged('demandas.importacao.etapas');
        $filtro = $this->option('etapa');
        $linhas = [];

        $contexto->durante(function () use ($etapas, $filtro, &$linhas): void {
            foreach ($etapas as $etapa) {
                if ($filtro !== null && $etapa->nome() !== $filtro) {
                    continue;
                }
                $relatorio = $etapa->executar((bool) $this->option('dry-run'), $this->option('desde'), max(1, (int) $this->option('lote')));
                $linhas[] = $relatorio->toArray();
                foreach ($relatorio->motivos as $motivo => $n) {
                    $this->warn(sprintf('  %s: %d rejeitado(s) por %s', $etapa->nome(), $n, $motivo));
                }
                foreach ($relatorio->avisos as $motivo => $n) {
                    $this->warn(sprintf('  %s: %d aviso(s) por %s', $etapa->nome(), $n, $motivo));
                }
            }
        });

        $this->table(['etapa', 'lidos', 'importados', 'atualizados', 'ignorados', 'rejeitados'], $linhas);
        if ($this->option('dry-run')) {
            $this->info('Dry-run: nada foi gravado.');
        }

        return self::SUCCESS;
    }
}
