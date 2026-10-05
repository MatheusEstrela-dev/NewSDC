<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Services;

use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Events\OperacaoDiretorioConcluida;
use App\Modules\Acessos\Models\OperacaoAd;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Encerramento unico de uma operacao em `falhou`, usado pelo failed() do job e
 * pela reconciliacao das operacoes presas: trava a linha, transiciona (podeIrPara),
 * grava a auditoria `diretorio_<acao>_falhou` com o codigo e, depois do commit,
 * emite OperacaoDiretorioConcluida. Idempotente: operacao final nao muda.
 */
final class EncerraOperacaoComFalha
{
    /** @param (Closure(OperacaoAd): bool)|null $condicao reavaliada com a linha travada */
    public function encerrar(string $operacaoId, CodigoErroDiretorio $codigo, ?Closure $condicao = null): bool
    {
        $encerrada = DB::transaction(static function () use ($operacaoId, $codigo, $condicao): bool {
            $operacao = OperacaoAd::query()->lockForUpdate()->find($operacaoId);
            if ($operacao === null || $operacao->estaFinalizada() || ($condicao !== null && ! $condicao($operacao))) {
                return false;
            }
            $operacao->falhar($codigo);
            $operacao->registrarAuditoria('falhou', ['codigo_erro' => $codigo->value]);

            return true;
        });

        if ($encerrada) {
            OperacaoDiretorioConcluida::dispatch($operacaoId);
        }

        return $encerrada;
    }
}
