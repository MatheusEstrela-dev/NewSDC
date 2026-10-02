<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Jobs;

use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use App\Modules\Acessos\DTOs\ReferenciaConta;
use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Exceptions\DiretorioIndisponivel;
use App\Modules\Acessos\Exceptions\DiretorioRecusou;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Services\HistoricoDemanda;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * Chamada ao diretorio corporativo fora da requisicao, pela porta
 * DiretorioCorporativo: resolve a conta pelo login e age sobre a referencia
 * recem-lida. Ponte ate a automacao passar pelo caso de uso de Acessos (que
 * traz guardas de escopo e entrega da senha). Resposta do AD nunca vai para
 * historico ou log: so o CodigoErroDiretorio.
 *
 * Sob QUEUE_CONNECTION=sync o SyncQueue chama fail() (que chama failed())
 * antes de relancar a excecao para quem despachou o job; o historico ja tem
 * o automation_failed quando a excecao chega no controller, entao o
 * controller nao grava historico de novo -- so mostra uma mensagem generica.
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
        try {
            $conta = $diretorio->consultar($this->login)
                ?? throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_INEXISTENTE);
            $this->executar($diretorio, $conta->referencia());
        } catch (DiretorioRecusou $erro) {
            // Recusa definitiva (conta inexistente, sem permissao etc): tentar
            // de novo nao muda o resultado, entao falha direto sem reenfileirar.
            $this->fail($erro);

            return;
        }

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
            sprintf('"%s" falhou: %s', $this->acao, $this->descreverFalha($erro)),
            ['operation_id' => $this->operationId],
        );
    }

    private function executar(DiretorioCorporativo $diretorio, ReferenciaConta $conta): void
    {
        match ($this->acao) {
            'desbloquear' => $diretorio->desbloquear($conta, $this->operationId),
            'ativar' => $diretorio->habilitar($conta, $this->operationId),
            // senha so na memoria do job, com troca obrigatoria no proximo logon
            'resetar' => $diretorio->redefinirSenha(
                $conta,
                Str::password(max(14, (int) config('acessos.diretorio.senha_tamanho', 16))),
                true,
                $this->operationId,
            ),
        };
    }

    /**
     * Descricao segura da falha para o historico: so o texto fixo do codigo
     * de erro do diretorio, nunca a mensagem de uma excecao qualquer.
     */
    private function descreverFalha(Throwable $erro): string
    {
        if ($erro instanceof DiretorioRecusou || $erro instanceof DiretorioIndisponivel) {
            return $erro->codigo()->mensagem();
        }

        return 'erro ao falar com o diretório ('.class_basename($erro).')';
    }
}
