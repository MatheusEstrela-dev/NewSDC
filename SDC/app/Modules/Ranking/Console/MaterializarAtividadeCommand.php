<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Console;

use App\Modules\Ranking\Services\MaterializarAtividade;
use Illuminate\Console\Command;
use Throwable;

/**
 * Materializa ranking.atividade (dias distintos com login) dos periodos
 * correntes, criterio de desempate do placar.
 *
 * POR QUE RODAR NO SCHEDULER
 * O placar le so a copia materializada. Entre rodadas, o login de hoje ainda
 * nao desempata; quanto maior o intervalo, mais velho o desempate exibido.
 *
 * Idempotente: recalcula o periodo inteiro a cada rodada.
 *
 * Uso:
 *   php artisan ranking:materializar-atividade
 *   php artisan ranking:materializar-atividade --dry-run
 */
class MaterializarAtividadeCommand extends Command
{
    protected $signature = 'ranking:materializar-atividade
                            {--dry-run : mostra o que seria gravado, sem escrever}';

    protected $description = 'Materializa os dias ativos (login) por usuario/orgao/municipio dos periodos correntes do ranking';

    public function __construct(private readonly MaterializarAtividade $materializacao)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $seco = (bool) $this->option('dry-run');

        try {
            $resultado = $this->materializacao->executar($seco);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Ranking - materializacao de atividade em %s%s',
            $resultado['instante'],
            $seco ? ' (dry-run, nada foi escrito)' : '',
        ));

        $this->table(
            ['Periodo', 'Escopo', 'Entidades', 'Soma de dias', 'Max dias', $seco ? 'Removidas (n/a)' : 'Removidas'],
            array_map(static fn (array $linha): array => [
                $linha['chave'],
                $linha['escopo'],
                $linha['entidades'],
                $linha['soma_dias'],
                $linha['max_dias'],
                $linha['removidas'],
            ], $resultado['periodos']),
        );

        return self::SUCCESS;
    }
}
