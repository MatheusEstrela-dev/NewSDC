<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Listeners;

use App\Modules\Acessos\Enums\EstadoOperacaoAd;
use App\Modules\Acessos\Enums\OrigemOperacaoAd;
use App\Modules\Acessos\Events\OperacaoDiretorioConcluida;
use App\Modules\Acessos\Models\OperacaoAd;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Services\HistoricoDemanda;

/**
 * Fecha no historico da demanda a automacao pedida por ela (spec 6.6 item 3).
 * Sincrono, roda no worker logo depois do commit da operacao. Do diretorio so
 * entra o rotulo do codigo de erro, nunca resposta do AD.
 */
final class RegistraResultadoAutomacao
{
    public function __construct(private readonly HistoricoDemanda $historico) {}

    public function handle(OperacaoDiretorioConcluida $evento): void
    {
        $operacao = OperacaoAd::find($evento->operacaoId);
        if ($operacao?->origem !== OrigemOperacaoAd::DEMANDA || ($demanda = Demanda::find($operacao->origem_id)) === null) {
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
    }
}
