<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeNotificacaoService;

/**
 * Leitura do protocolo PAE como etapas do fluxo, para o acompanhamento do
 * analista. So traduz o status da maquina de estados (PaeProtocoloWorkflow)
 * em etapa, tom e responsavel; nao decide nem grava transicao.
 *
 * Os status fora do caminho feliz (tratativa, dilacao, suspensao, reprovacoes
 * e revogacao) ficam na etapa onde acontecem, com tom de alerta ou de erro.
 */
final class AcompanhamentoProtocolo
{
    /** @var list<array{ordem: int, value: string, label: string, responsavel: string}> */
    private const ETAPAS = [
        ['ordem' => 1, 'value' => 'entrada', 'label' => 'Entrada', 'responsavel' => 'Triagem CEDEC'],
        ['ordem' => 2, 'value' => 'gerenciamento', 'label' => 'Gerenciamento', 'responsavel' => 'Analista CEDEC'],
        ['ordem' => 3, 'value' => 'notificacao', 'label' => 'Notificação', 'responsavel' => 'Empreendedor'],
        ['ordem' => 4, 'value' => 'analise', 'label' => 'Análise', 'responsavel' => 'Analista CEDEC'],
        ['ordem' => 5, 'value' => 'aprovado', 'label' => 'Aprovado', 'responsavel' => 'CEDEC'],
        ['ordem' => 6, 'value' => 'ccpae', 'label' => 'CCPAE', 'responsavel' => 'Empreendedor'],
    ];

    /**
     * @return array<string, mixed>
     */
    public static function de(PaeProtocolo $protocolo): array
    {
        $protocolo->loadMissing('analistaAtual:id,name');
        $status = $protocolo->status;
        $etapa = self::etapa($status);
        $analista = $protocolo->analistaAtual?->name;

        return [
            'status' => $status->value,
            'status_label' => $status->getLabel(),
            'etapa' => $etapa,
            'tom' => self::tom($status),
            'encerrado' => $status->isTerminal(),
            'concluido' => in_array($status, [PaeProtocoloStatus::CCPAE, PaeProtocoloStatus::ATIVO_3_ANOS], true),
            'responsavel_agora' => self::responsavel($status, $analista),
            'analista' => $analista,
            'arquivado' => (bool) $protocolo->arquivado,
            'max_ciclos_automaticos' => PaeNotificacaoService::MAX_CICLOS_AUTOMATICOS,
            'etapas' => self::ETAPAS,
        ];
    }

    private static function etapa(PaeProtocoloStatus $status): int
    {
        return match ($status) {
            PaeProtocoloStatus::NOVO,
            PaeProtocoloStatus::ENTRADA_PROCESSO,
            PaeProtocoloStatus::CRIACAO_SDC,
            PaeProtocoloStatus::REPROVADO_SUMARIAMENTE => 1,
            PaeProtocoloStatus::GERENCIAMENTO,
            PaeProtocoloStatus::ESPERAR_TRATATIVA,
            PaeProtocoloStatus::DILACAO => 2,
            PaeProtocoloStatus::NOTIFICACAO => 3,
            PaeProtocoloStatus::ANALISE,
            PaeProtocoloStatus::REPROVADO => 4,
            PaeProtocoloStatus::APROVADO => 5,
            PaeProtocoloStatus::CCPAE,
            PaeProtocoloStatus::ATIVO_3_ANOS,
            PaeProtocoloStatus::SUSPENSO,
            PaeProtocoloStatus::REVOGADO => 6,
        };
    }

    /** 'normal' segue o fluxo; 'alerta' esta parado aguardando alguem; 'erro' saiu do fluxo. */
    private static function tom(PaeProtocoloStatus $status): string
    {
        return match ($status) {
            PaeProtocoloStatus::REPROVADO,
            PaeProtocoloStatus::REPROVADO_SUMARIAMENTE,
            PaeProtocoloStatus::REVOGADO => 'erro',
            PaeProtocoloStatus::ESPERAR_TRATATIVA,
            PaeProtocoloStatus::DILACAO,
            PaeProtocoloStatus::SUSPENSO => 'alerta',
            default => 'normal',
        };
    }

    private static function responsavel(PaeProtocoloStatus $status, ?string $analista): ?string
    {
        return match ($status) {
            PaeProtocoloStatus::NOVO,
            PaeProtocoloStatus::ENTRADA_PROCESSO,
            PaeProtocoloStatus::CRIACAO_SDC => 'Triagem CEDEC',
            PaeProtocoloStatus::GERENCIAMENTO,
            PaeProtocoloStatus::ANALISE => $analista ?? 'Aguardando atribuição de analista',
            PaeProtocoloStatus::NOTIFICACAO => 'Empreendedor (resposta à notificação)',
            PaeProtocoloStatus::ESPERAR_TRATATIVA => 'Empreendedor (tratativa)',
            PaeProtocoloStatus::DILACAO => 'Empreendedor (prazo dilatado)',
            PaeProtocoloStatus::APROVADO => 'CEDEC (emissão do CCPAE)',
            PaeProtocoloStatus::CCPAE,
            PaeProtocoloStatus::ATIVO_3_ANOS => 'Empreendedor (manter a vigência)',
            PaeProtocoloStatus::SUSPENSO => 'CEDEC (decisão sobre a suspensão)',
            PaeProtocoloStatus::REPROVADO,
            PaeProtocoloStatus::REPROVADO_SUMARIAMENTE,
            PaeProtocoloStatus::REVOGADO => null,
        };
    }
}
