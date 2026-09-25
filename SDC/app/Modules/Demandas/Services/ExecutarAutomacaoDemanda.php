<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Jobs\ExecutarAutomacaoDemandaJob;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAuditLog;
use DomainException;

/**
 * Botao "Automacao" do chamado. No legado era shell_exec de script Python com
 * assunto fixo no codigo; aqui a acao vem do assunto e o login do campo que o
 * solicitante preencheu, e a chamada ao AD vai para fila.
 */
final class ExecutarAutomacaoDemanda
{
    public const ACOES = ['desbloquear', 'ativar', 'resetar'];

    // sAMAccountName: letras, digitos, ponto, hifen e sublinhado; ate 20.
    private const LOGIN_VALIDO = '/^[A-Za-z0-9._-]{1,20}$/';

    public function __construct(private readonly HistoricoDemanda $historico) {}

    public function solicitar(Demanda $demanda, int $userId): string
    {
        $config = $demanda->assunto?->form_automacao;
        $acao = $config['acao'] ?? null;
        if (! in_array($acao, self::ACOES, true)) {
            throw new DomainException('O assunto desta demanda não tem automação configurada.');
        }

        $login = trim((string) ($demanda->campos_customizados[$config['campo_login'] ?? ''] ?? ''));
        if (preg_match(self::LOGIN_VALIDO, $login) !== 1) {
            throw new DomainException('Login AD ausente ou inválido no campo "'.($config['campo_login'] ?? '').'".');
        }

        $sequencia = DemandaAuditLog::query()
            ->where('task_id', $demanda->id)
            ->where('acao', AcaoHistoricoDemanda::AUTOMACAO_SOLICITADA->value)
            ->count() + 1;
        $operationId = sprintf('demanda:%d:%s:%d', $demanda->id, $acao, $sequencia);

        $this->historico->registrar(
            $demanda, $userId, AcaoHistoricoDemanda::AUTOMACAO_SOLICITADA,
            sprintf('Solicitado "%s" para o login %s.', $acao, $login),
            ['operation_id' => $operationId],
        );

        ExecutarAutomacaoDemandaJob::dispatch($demanda->id, $acao, $login, $operationId, $userId)->afterCommit();

        return $operationId;
    }
}
