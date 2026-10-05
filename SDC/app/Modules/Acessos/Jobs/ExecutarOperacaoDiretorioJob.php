<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Jobs;

use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use App\Modules\Acessos\DTOs\ContaDiretorio;
use App\Modules\Acessos\DTOs\ResultadoOperacao;
use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Events\OperacaoDiretorioConcluida;
use App\Modules\Acessos\Exceptions\DiretorioIndisponivel;
use App\Modules\Acessos\Exceptions\DiretorioRecusou;
use App\Modules\Acessos\Models\OperacaoAd;
use App\Modules\Acessos\Services\Acoes\HandlerAcaoDiretorio;
use App\Modules\Acessos\Services\AplicaEspelhoAd;
use App\Modules\Acessos\Services\GuardaDeAlvo;
use App\Services\Webhook\CircuitBreakerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use RuntimeException;
use Throwable;

/**
 * Executa uma operacao no AD no worker on-prem (spec 6.2): trava e marca
 * `enviado`, resolve a conta por GUID (ou login), aplica a guarda de escopo nas
 * escritas, chama o handler da acao e grava espelho, resultado e auditoria
 * numa transacao. Falha transitoria repete (tries/backoff); recusa definitiva
 * encerra na hora.
 *
 * As excecoes que saem daqui (fila, failed_jobs, failed()) sao sempre
 * recriadas neste metodo so com o codigo: o rastro da original, que pode ter
 * login ou DN nos argumentos, nao e persistido.
 */
final class ExecutarOperacaoDiretorioJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public const CIRCUITO = 'diretorio';

    private const ESPERA_CIRCUITO_ABERTO = 60;

    private const TENTATIVAS_PADRAO = 3;

    private const BACKOFF_PADRAO = [10, 30];

    private const FALHA_AO_GRAVAR = 'Falha ao gravar o resultado da operacao no diretorio.';

    public int $timeout = 30;

    public function __construct(public readonly string $operacaoId)
    {
        $this->onConnection(config('acessos.diretorio.fila.conexao'));
        $this->onQueue(config('acessos.diretorio.fila.nome'));
    }

    /** A config ja valida; aqui so o padrao se ela vier sobrescrita com lixo. */
    public function tries(): int
    {
        $tentativas = config('acessos.diretorio.tentativas');

        return is_int($tentativas) && $tentativas >= 1 ? $tentativas : self::TENTATIVAS_PADRAO;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        $backoff = config('acessos.diretorio.backoff');
        $valido = is_array($backoff) && $backoff !== []
            && array_filter($backoff, static fn (mixed $segundos): bool => ! is_int($segundos) || $segundos < 0) === [];

        return $valido ? array_values($backoff) : self::BACKOFF_PADRAO;
    }

    public function handle(
        DiretorioCorporativo $diretorio,
        GuardaDeAlvo $guarda,
        AplicaEspelhoAd $espelho,
        CircuitBreakerService $circuito,
    ): void {
        $circuitoAberto = $circuito->isOpen(self::CIRCUITO);
        $operacao = $this->marcarEnviada(contarTentativa: ! $circuitoAberto);
        if ($operacao === null) {
            return; // final (retry tardio ou duplicata) ou inexistente
        }
        if ($circuitoAberto) {
            $this->release(self::ESPERA_CIRCUITO_ABERTO);

            return;
        }

        try {
            $conta = $this->resolverConta($diretorio, $operacao, $espelho);
            $resultado = $this->executarAcao($operacao, $conta, $guarda, $espelho);
        } catch (DiretorioIndisponivel $erro) {
            $circuito->recordFailure(self::CIRCUITO);
            $saneado = new DiretorioIndisponivel($erro->codigo());
            if ($erro->codigo()->transitorio()) {
                throw $saneado;
            }
            $this->fail($saneado);

            return;
        } catch (DiretorioRecusou $erro) {
            $this->fail(new DiretorioRecusou($erro->codigo()));

            return;
        } catch (Throwable $erro) {
            $this->registrarErroInterno($erro);
            $this->fail(new DiretorioRecusou(CodigoErroDiretorio::ERRO_INTERNO));

            return;
        }

        $circuito->recordSuccess(self::CIRCUITO);
        $this->concluir($operacao, $resultado, $guarda, $espelho);
        OperacaoDiretorioConcluida::dispatch($operacao->id);
    }

    /** Ultima tentativa esgotada ou recusa definitiva: operacao `falhou` com o codigo. */
    public function failed(?Throwable $erro): void
    {
        $codigo = match (true) {
            $erro instanceof DiretorioRecusou, $erro instanceof DiretorioIndisponivel => $erro->codigo(),
            $erro instanceof TimeoutExceededException => CodigoErroDiretorio::TIMEOUT,
            $erro instanceof MaxAttemptsExceededException => CodigoErroDiretorio::INDISPONIVEL,
            default => CodigoErroDiretorio::ERRO_INTERNO,
        };

        $operacao = DB::transaction(function () use ($codigo): ?OperacaoAd {
            $operacao = OperacaoAd::query()->lockForUpdate()->find($this->operacaoId);
            if ($operacao === null || $operacao->estaFinalizada()) {
                return null;
            }
            $operacao->falhar($codigo);
            $operacao->registrarAuditoria('falhou', ['codigo_erro' => $codigo->value]);

            return $operacao;
        });

        if ($operacao !== null) {
            OperacaoDiretorioConcluida::dispatch($operacao->id);
        }
    }

    /** Transacao curta: o lock nao fica aberto durante a chamada ao AD. */
    private function marcarEnviada(bool $contarTentativa): ?OperacaoAd
    {
        return DB::transaction(function () use ($contarTentativa): ?OperacaoAd {
            $operacao = OperacaoAd::query()->lockForUpdate()->find($this->operacaoId);
            if ($operacao === null || $operacao->estaFinalizada()) {
                return null;
            }
            $operacao->marcarEnviada($contarTentativa);

            return $operacao;
        });
    }

    /** Por GUID (operacao ou cadastro) quando conhecido; senao pelo login na SearchBase. */
    private function resolverConta(DiretorioCorporativo $diretorio, OperacaoAd $operacao, AplicaEspelhoAd $espelho): ContaDiretorio
    {
        $cadastro = $operacao->cadastro;
        $guid = $operacao->object_guid ?? $cadastro?->object_guid;
        $conta = $guid !== null ? $diretorio->buscarPorGuid($guid) : $diretorio->consultar($operacao->login_ad);

        if ($conta === null) {
            if ($cadastro !== null) {
                $espelho->naoEncontrada($cadastro);
            }
            throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_INEXISTENTE);
        }
        $operacao->vincularConta($conta->objectGuid);

        return $conta;
    }

    /**
     * Escrita: grava o espelho da leitura (GUID, status_ad) e so passa pela
     * guarda depois. Consulta: a guarda so classifica, no espelho final.
     */
    private function executarAcao(OperacaoAd $operacao, ContaDiretorio $conta, GuardaDeAlvo $guarda, AplicaEspelhoAd $espelho): ResultadoOperacao
    {
        if ($operacao->acao->escreve()) {
            if ($operacao->cadastro !== null) {
                $espelho->aplicar($operacao->cadastro, $conta, $guarda->status($conta));
            }
            $guarda->assegurar($conta);
        }

        $handler = app($operacao->acao->handler());
        if (! $handler instanceof HandlerAcaoDiretorio) {
            throw new LogicException('Handler invalido para a acao '.$operacao->acao->value);
        }

        return $handler->executar($operacao, $conta);
    }

    /**
     * Espelho, `confirmado` e auditoria juntos. Se o banco falhar depois do AD
     * aplicar, o job repete: toda acao e convergente (spec 6.3 item 4).
     */
    private function concluir(OperacaoAd $operacao, ResultadoOperacao $resultado, GuardaDeAlvo $guarda, AplicaEspelhoAd $espelho): void
    {
        try {
            DB::transaction(function () use ($operacao, $resultado, $guarda, $espelho): void {
                $travada = OperacaoAd::query()->lockForUpdate()->findOrFail($operacao->id);
                if ($travada->cadastro !== null) {
                    $espelho->aplicar($travada->cadastro, $resultado->contaDepois, $guarda->status($resultado->contaDepois));
                }
                $travada->confirmar($resultado);
                $travada->registrarAuditoria('confirmado');
            });
        } catch (Throwable $erro) {
            $this->registrarErroInterno($erro);

            throw new RuntimeException(self::FALHA_AO_GRAVAR);
        }
    }

    /** So classe e origem da excecao: a mensagem pode carregar dado da conta. */
    private function registrarErroInterno(Throwable $erro): void
    {
        Log::warning('acessos.diretorio.erro_interno', [
            'operacao_id' => $this->operacaoId,
            'excecao' => $erro::class,
            'origem' => basename($erro->getFile()).':'.$erro->getLine(),
        ]);
    }
}
