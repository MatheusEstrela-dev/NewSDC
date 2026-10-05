<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Jobs;

use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use App\Modules\Acessos\DTOs\ContaDiretorio;
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
use Throwable;

/**
 * Chamada ao diretorio corporativo fora da requisicao, pela porta
 * DiretorioCorporativo: resolve a conta pelo login e age sobre a referencia
 * recem-lida. Ponte ate a automacao passar pelo caso de uso de Acessos (que
 * traz guardas de escopo e entrega da senha); ate la `resetar` e recusado
 * sem chamada ao diretorio. Resposta do AD nunca vai para
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

    // Sem a entrega com leitura unica a senha nova nao chegaria a ninguem: a
    // ponte recusa o reset antes de tocar no diretorio.
    private const RESET_INDISPONIVEL = 'Redefinição de senha pela demanda ainda não disponível.';

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
        if ($this->acao === 'resetar') {
            // config_ausente e definitiva: falha sem reenfileirar e sem chamada ao AD
            $this->fail(new DiretorioIndisponivel(CodigoErroDiretorio::CONFIG_AUSENTE));

            return;
        }

        try {
            $conta = $diretorio->consultar($this->login)
                ?? throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_INEXISTENTE);
            if ($this->protegida($conta)) {
                throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_PROTEGIDA);
            }
            $this->executar($diretorio, $conta->referencia());
        } catch (DiretorioRecusou|DiretorioIndisponivel $erro) {
            // So falha transitoria (rede/tempo) volta para a fila; recusa e
            // indisponibilidade definitiva (config_ausente, certificado,
            // credencial_servico) falham direto, sem reenfileirar (spec 9.1).
            if ($erro->codigo()->transitorio()) {
                throw $erro;
            }
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

    /** adminCount=1 no AD ou login na lista de protegidas (que sempre traz a conta de servico). */
    private function protegida(ContaDiretorio $conta): bool
    {
        $lista = array_map('mb_strtolower', (array) config('acessos.diretorio.contas_protegidas', []));

        return $conta->protegida || in_array(mb_strtolower($conta->login), $lista, true);
    }

    private function executar(DiretorioCorporativo $diretorio, ReferenciaConta $conta): void
    {
        match ($this->acao) {
            'desbloquear' => $diretorio->desbloquear($conta, $this->operationId),
            'ativar' => $diretorio->habilitar($conta, $this->operationId),
        };
    }

    /**
     * Descricao segura da falha para o historico: so o texto fixo do codigo
     * de erro do diretorio, nunca a mensagem de uma excecao qualquer.
     */
    private function descreverFalha(Throwable $erro): string
    {
        if ($this->acao === 'resetar' && $erro instanceof DiretorioIndisponivel && $erro->codigo() === CodigoErroDiretorio::CONFIG_AUSENTE) {
            return self::RESET_INDISPONIVEL;
        }
        if ($erro instanceof DiretorioRecusou || $erro instanceof DiretorioIndisponivel) {
            return $erro->codigo()->mensagem();
        }

        return 'erro ao falar com o diretório ('.class_basename($erro).')';
    }
}
