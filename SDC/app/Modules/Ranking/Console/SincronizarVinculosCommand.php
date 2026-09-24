<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Console;

use App\Modules\Ranking\Services\SincronizarVinculos;
use Illuminate\Console\Command;
use Throwable;

/**
 * Mantem ranking.vinculos alinhado ao cadastro operacional e garante os
 * participantes dos periodos correntes.
 *
 * POR QUE RODAR NO SCHEDULER
 * O vinculo so passa a valer a partir da sincronizacao que o abre (nunca
 * retroage). Quanto maior o intervalo entre rodadas, maior a janela em que um
 * usuario recem-lotado pontua em apuracao, ou em que quem trocou de orgao
 * continua creditando o orgao antigo.
 *
 * Idempotente: rodar duas vezes seguidas nao produz acao na segunda.
 *
 * Uso:
 *   php artisan ranking:sincronizar-vinculos
 *   php artisan ranking:sincronizar-vinculos --dry-run
 */
class SincronizarVinculosCommand extends Command
{
    protected $signature = 'ranking:sincronizar-vinculos
                            {--dry-run : mostra o que seria feito, sem escrever}';

    protected $description = 'Sincroniza vinculos usuario/orgao/municipio do ranking e garante os participantes dos periodos correntes';

    public function __construct(private readonly SincronizarVinculos $sincronizacao)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $seco = (bool) $this->option('dry-run');

        try {
            $resultado = $this->sincronizacao->executar($seco);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Ranking - sincronizacao de vinculos em %s%s',
            $resultado['instante'],
            $seco ? ' (dry-run, nada foi escrito)' : '',
        ));

        $this->components->twoColumnDetail('Usuarios ativos com orgao (origem)', (string) $resultado['origem']);
        $this->components->twoColumnDetail('Vinculos abertos antes', (string) $resultado['abertos']);
        $this->components->twoColumnDetail($seco ? 'Vinculos a abrir' : 'Vinculos abertos', (string) $resultado['abrir']);
        $this->components->twoColumnDetail($seco ? 'Vinculos a fechar' : 'Vinculos fechados', (string) $resultado['fechar']);
        $this->components->twoColumnDetail('Vinculos inalterados', (string) $resultado['inalterados']);

        $this->table(
            ['Periodo', 'Escopo', 'Entidades', $seco ? 'Seriam criados' : 'Criados'],
            array_map(static fn (array $linha): array => [
                $linha['chave'],
                $linha['escopo'],
                $linha['entidades'],
                $linha['novos'],
            ], $resultado['participantes']),
        );

        return self::SUCCESS;
    }
}
