<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Modules\PowerBi\Contracts\Materializador;
use App\Modules\PowerBi\Exceptions\ModuloEmExecucaoException;
use App\Support\Database\ConexaoPowerBi;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Roda um materializador com lock por modulo, janela da fonte, cursor e trilha
 * em controle.execucoes. No modo seco tudo roda dentro de uma transacao que e
 * revertida (nada persiste, nem o cursor).
 */
final class ExecutorMaterializacao
{
    private const LOCK_SEGUNDOS = 960;

    public function __construct(private readonly RegistroDeExecucoes $registro)
    {
    }

    public function materializar(Materializador $m, ?Carbon $desde = null, bool $seco = false): ResultadoExecucao
    {
        return $this->comLock('materializar', $m->modulo(), function () use ($m, $desde, $seco): ResultadoExecucao {
            $janela = JanelaDaFonte::obter();
            $cursor = new CursorDeMaterializacao($m->modulo(), $desde);
            $id = $seco ? null : $this->registro->iniciar($m->modulo(), $desde ? 'incremental_desde' : 'incremental');

            try {
                $resultado = $seco
                    ? $this->emRollback(fn (): ResultadoExecucao => $m->materializar($janela, $cursor))
                    : $m->materializar($janela, $cursor);
            } catch (Throwable $e) {
                if ($id !== null) {
                    $this->registro->falhar($id, $e);
                }
                throw $e;
            }

            if ($id !== null) {
                $this->registro->concluir($id, $resultado);
            }

            return $resultado;
        });
    }

    /** Lock por modulo e operacao; lanca se ja ha execucao em curso. */
    public function comLock(string $operacao, string $modulo, Closure $fn): mixed
    {
        $lock = Cache::lock("powerbi:{$operacao}:{$modulo}", self::LOCK_SEGUNDOS);
        if (! $lock->get()) {
            throw ModuloEmExecucaoException::modulo($modulo);
        }

        try {
            return $fn();
        } finally {
            $lock->release();
        }
    }

    private function emRollback(Closure $fn): mixed
    {
        $db = ConexaoPowerBi::escrita();
        $db->beginTransaction();

        try {
            return $fn();
        } finally {
            $db->rollBack();
        }
    }
}
