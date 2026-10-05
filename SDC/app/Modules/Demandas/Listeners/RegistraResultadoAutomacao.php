<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Listeners;

use App\Modules\Acessos\Enums\EstadoOperacaoAd;
use App\Modules\Acessos\Enums\OrigemOperacaoAd;
use App\Modules\Acessos\Events\OperacaoDiretorioConcluida;
use App\Modules\Acessos\Models\OperacaoAd;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAuditLog;
use App\Modules\Demandas\Services\HistoricoDemanda;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Fecha no historico da demanda a automacao pedida por ela (spec 6.6 item 3).
 * Em fila na conexao/fila padrao, fora do job do AD: falha aqui nunca muda a
 * operacao. Idempotente por operation_id (evento repetido ou retry nao duplica).
 * Do diretorio so entra o rotulo do codigo de erro, nunca resposta do AD.
 */
final class RegistraResultadoAutomacao implements ShouldQueue
{
    private const FALHA = 'Falha ao registrar o resultado da automacao no historico da demanda.';

    private const RESULTADOS = [
        AcaoHistoricoDemanda::AUTOMACAO_CONFIRMADA,
        AcaoHistoricoDemanda::AUTOMACAO_FALHOU,
    ];

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30];

    public function __construct(private readonly HistoricoDemanda $historico) {}

    public function handle(OperacaoDiretorioConcluida $evento): void
    {
        try {
            $this->registrar($evento->operacaoId);
        } catch (Throwable $erro) {
            // A mensagem pode ter SQL com valores: so classe e origem vao ao
            // log, e a fila (failed_jobs) recebe texto fixo.
            $this->registrarErro('demandas.automacao.historico_falhou', $evento->operacaoId, $erro);

            throw new RuntimeException(self::FALHA);
        }
    }

    public function failed(OperacaoDiretorioConcluida $evento, Throwable $erro): void
    {
        $this->registrarErro('demandas.automacao.historico_perdido', $evento->operacaoId, $erro);
    }

    private function registrar(string $operacaoId): void
    {
        $operacao = OperacaoAd::find($operacaoId);
        if ($operacao?->origem !== OrigemOperacaoAd::DEMANDA || ! $operacao->estado->final()) {
            return;
        }

        DB::transaction(function () use ($operacao): void {
            // Trava a demanda: duas entregas do mesmo evento nao gravam duas vezes.
            $demanda = Demanda::query()->lockForUpdate()->find($operacao->origem_id);
            if ($demanda === null || $this->jaRegistrado($demanda, $operacao->chave_idempotencia)) {
                return;
            }

            $confirmada = $operacao->estado === EstadoOperacaoAd::CONFIRMADO;
            $this->historico->registrar(
                $demanda, (int) $operacao->solicitado_por_id,
                $confirmada ? AcaoHistoricoDemanda::AUTOMACAO_CONFIRMADA : AcaoHistoricoDemanda::AUTOMACAO_FALHOU,
                $confirmada
                    ? sprintf('"%s" confirmado pelo diretório para %s.', $operacao->acao->label(), $operacao->login_ad)
                    : sprintf('"%s" falhou: %s', $operacao->acao->label(), $operacao->codigo_erro?->mensagem() ?? 'erro desconhecido'),
                ['operation_id' => $operacao->chave_idempotencia],
            );
        });
    }

    private function jaRegistrado(Demanda $demanda, string $operationId): bool
    {
        return DemandaAuditLog::query()
            ->where('task_id', $demanda->getKey())
            ->whereIn('acao', array_map(static fn (AcaoHistoricoDemanda $acao): string => $acao->value, self::RESULTADOS))
            ->where('metadata->operation_id', $operationId)
            ->exists();
    }

    private function registrarErro(string $evento, string $operacaoId, Throwable $erro): void
    {
        Log::warning($evento, [
            'operacao_id' => $operacaoId,
            'excecao' => $erro::class,
            'origem' => basename($erro->getFile()).':'.$erro->getLine(),
        ]);
    }
}
