<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Enums;

/**
 * Acoes gravadas em task_audit_logs.acao. Os rotulos repetem os do
 * cedec-demanda, para quem vem do legado reconhecer a aba Historico.
 */
enum AcaoHistoricoDemanda: string
{
    case CRIADA = 'created';
    case EDITADA = 'updated';
    case STATUS_ALTERADO = 'status_changed';
    case ATRIBUIDA = 'assigned';
    case ANEXO_ADICIONADO = 'attachment_added';
    case RESOLVIDA = 'resolved';
    case REABERTA = 'reopened';
    case AUTOMACAO_SOLICITADA = 'automation_requested';
    case AUTOMACAO_CONFIRMADA = 'automation_confirmed';
    case AUTOMACAO_FALHOU = 'automation_failed';
    case IMPORTADA = 'imported';

    public function rotulo(): string
    {
        return match ($this) {
            self::CRIADA => 'Criou o chamado',
            self::EDITADA => 'Edição',
            self::STATUS_ALTERADO => 'Alteração de Status',
            self::ATRIBUIDA => 'Transferência',
            self::ANEXO_ADICIONADO => 'Novo Anexo',
            self::RESOLVIDA => 'Resolução',
            self::REABERTA => 'Chamado REABERTO',
            self::AUTOMACAO_SOLICITADA => 'Automação solicitada',
            self::AUTOMACAO_CONFIRMADA => 'Automação confirmada',
            self::AUTOMACAO_FALHOU => 'Automação falhou',
            self::IMPORTADA => 'Importado do sistema anterior',
        };
    }
}
