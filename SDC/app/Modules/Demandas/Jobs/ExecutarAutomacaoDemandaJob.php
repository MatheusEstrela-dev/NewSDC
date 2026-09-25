<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Jobs;

use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Services\HistoricoDemanda;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Chamada ao diretorio corporativo fora da requisicao. Idempotente pelo
 * operationId (Idempotency-Key no adaptador HTTP): retry nao desbloqueia duas
 * vezes. Resposta do AD nunca vai para historico ou log -- reset devolve senha.
 */
class ExecutarAutomacaoDemandaJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 30;

    public function __construct(
        public readonly int $demandaId,
        public readonly string $acao,
        public readonly string $login,
        public readonly string $operationId,
        public readonly int $userId,
    ) {}

    public function tries(): int
    {
        return (int) config('demandas.automacao.tentativas', 3);
    }

    public function backoff(): array
    {
        return config('demandas.automacao.backoff_segundos', [10, 30]);
    }

    public function handle(DiretorioCorporativo $diretorio, HistoricoDemanda $historico): void
    {
        match ($this->acao) {
            'desbloquear' => $diretorio->solicitarDesbloqueio($this->login, $this->operationId),
            'ativar' => $diretorio->solicitarAtivacao($this->login, $this->operationId),
            'resetar' => $diretorio->solicitarReset($this->login, $this->operationId),
        };

        $demanda = Demanda::find($this->demandaId);
        if ($demanda !== null) {
            $historico->registrar(
                $demanda, $this->userId, AcaoHistoricoDemanda::AUTOMACAO_CONFIRMADA,
                sprintf('"%s" confirmado pelo diretório para %s.', $this->acao, $this->login),
                ['operation_id' => $this->operationId],
            );
        }
    }

    public function failed(Throwable $erro): void
    {
        $demanda = Demanda::find($this->demandaId);
        if ($demanda === null) {
            return;
        }
        app(HistoricoDemanda::class)->registrar(
            $demanda, $this->userId, AcaoHistoricoDemanda::AUTOMACAO_FALHOU,
            sprintf('"%s" falhou: %s', $this->acao, mb_substr($erro->getMessage(), 0, 200)),
            ['operation_id' => $this->operationId],
        );
    }
}
