<?php

declare(strict_types=1);

namespace App\Console\Commands\Log;

use App\Support\Database\Log\PodadorDeLogs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Poda unica das notificacoes e dos logs tecnicos (config/retencao.php).
 *
 * Substitui o webhooks:archive e o notificacoes:arquivar: passada a retencao,
 * a linha e apagada, e nao arquivada. Notificacao nao lida nunca sai.
 *
 * Na primeira execucao de cada ambiente, rodar a mao com --max-segundos para
 * controlar WAL e lag das replicas; o que sobrar fica para o dia seguinte.
 *
 * Uso: php artisan log:podar --dry-run
 *      php artisan log:podar --tabela=notifications --max-segundos=300
 */
class PodarLogsCommand extends Command
{
    protected $signature = 'log:podar
                            {--dias= : retencao em dias para todas as tabelas com poda (padrao: config retencao.dias)}
                            {--tabela=* : poda so estas tabelas de config/retencao.php}
                            {--lote= : linhas por DELETE (padrao: config retencao.lote)}
                            {--dry-run : so conta o que seria apagado}
                            {--max-segundos= : para de abrir lote novo depois deste tempo}';

    protected $description = 'Apaga notificacoes lidas e logs tecnicos alem da retencao, mantendo as nao lidas';

    public function handle(PodadorDeLogs $podador): int
    {
        $simular = (bool) $this->option('dry-run');

        try {
            $resultado = $podador->podar(
                tabelas: array_values(array_filter((array) $this->option('tabela'))),
                dias: $this->inteiro('dias'),
                lote: $this->inteiro('lote'),
                simular: $simular,
                maxSegundos: $this->inteiro('max-segundos'),
            );
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Tabela', $simular ? 'Seriam apagadas' : 'Apagadas', 'Situacao'],
            array_map(
                fn (string $tabela, array $r): array => [$tabela, $r['linhas'], $r['situacao']],
                array_keys($resultado),
                $resultado
            )
        );

        if (! $simular) {
            Log::info('log:podar concluido', $resultado);
        }

        return self::SUCCESS;
    }

    /**
     * Opcao numerica ou null quando ausente. Texto nao numerico e erro, e nao
     * zero: --dias=abc virando 0 seria apagar tudo.
     */
    private function inteiro(string $opcao): ?int
    {
        $valor = $this->option($opcao);

        if ($valor === null || $valor === '') {
            return null;
        }

        if (filter_var($valor, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("--{$opcao} precisa ser um numero inteiro.");
        }

        return (int) $valor;
    }
}
