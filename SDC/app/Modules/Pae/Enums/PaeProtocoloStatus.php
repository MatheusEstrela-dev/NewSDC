<?php

declare(strict_types=1);

namespace App\Modules\Pae\Enums;

enum PaeProtocoloStatus: string
{
    case NOVO = 'novo';
    case ENTRADA_PROCESSO = 'entrada_processo';
    case CRIACAO_SDC = 'criacao_sdc';
    case GERENCIAMENTO = 'gerenciamento';
    case NOTIFICACAO = 'notificacao';
    case ANALISE = 'analise';
    case APROVADO = 'aprovado';
    case REPROVADO = 'reprovado';
    case CCPAE = 'ccpae';
    case ATIVO_3_ANOS = 'ativo_3_anos';
    case SUSPENSO = 'suspenso';
    case REVOGADO = 'revogado';
    case ESPERAR_TRATATIVA = 'esperar_tratativa';
    case DILACAO = 'dilacao';

    public function getLabel(): string
    {
        return match ($this) {
            self::NOVO => 'Novo',
            self::ENTRADA_PROCESSO => 'Entrada do Processo',
            self::CRIACAO_SDC => 'Criação no SDC',
            self::GERENCIAMENTO => 'Gerenciamento',
            self::NOTIFICACAO => 'Notificação',
            self::ANALISE => 'Análise',
            self::APROVADO => 'Aprovado',
            self::REPROVADO => 'Reprovado',
            self::CCPAE => 'CCPAE',
            self::ATIVO_3_ANOS => 'Ativo (3 anos)',
            self::SUSPENSO => 'Suspenso',
            self::REVOGADO => 'Revogado',
            self::ESPERAR_TRATATIVA => 'Aguardando Tratativa',
            self::DILACAO => 'Dilatação',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColorClass(): string
    {
        return match ($this) {
            self::NOVO => 'bg-slate-100 text-slate-800',
            self::ENTRADA_PROCESSO, self::CRIACAO_SDC => 'bg-blue-100 text-blue-800',
            self::GERENCIAMENTO => 'bg-blue-100 text-blue-700',
            self::NOTIFICACAO => 'bg-amber-100 text-amber-800',
            self::ANALISE => 'bg-indigo-100 text-indigo-800',
            self::APROVADO => 'bg-green-100 text-green-800',
            self::REPROVADO, self::REVOGADO => 'bg-red-100 text-red-800',
            self::CCPAE, self::ATIVO_3_ANOS => 'bg-emerald-100 text-emerald-800',
            self::SUSPENSO => 'bg-yellow-100 text-yellow-800',
            self::ESPERAR_TRATATIVA, self::DILACAO => 'bg-orange-100 text-orange-800',
        };
    }

    public function color(): string
    {
        return $this->getColorClass();
    }

    public function canTransitionTo(PaeProtocoloStatus $novo): bool
    {
        return in_array($novo, $this->getAllowedTransitions(), true);
    }

    public function getAllowedTransitions(): array
    {
        return match ($this) {
            self::NOVO => [
                self::ENTRADA_PROCESSO,
            ],
            self::ENTRADA_PROCESSO => [
                self::CRIACAO_SDC,
            ],
            self::CRIACAO_SDC => [
                self::GERENCIAMENTO,
            ],
            self::GERENCIAMENTO => [
                self::NOTIFICACAO,
                self::ESPERAR_TRATATIVA,
            ],
            self::NOTIFICACAO => [
                self::ANALISE,
            ],
            self::ESPERAR_TRATATIVA => [
                self::DILACAO,
            ],
            self::DILACAO => [
                self::GERENCIAMENTO,
            ],
            self::ANALISE => [
                self::APROVADO,
                self::REPROVADO,
            ],
            self::APROVADO => [
                self::CCPAE,
            ],
            self::CCPAE => [
                self::ATIVO_3_ANOS,
                self::SUSPENSO,
                self::REVOGADO,
            ],
            self::ATIVO_3_ANOS => [
                self::SUSPENSO,
                self::REVOGADO,
            ],
            self::SUSPENSO => [
                self::ATIVO_3_ANOS,
                self::REVOGADO,
            ],
            self::REPROVADO, self::REVOGADO => [],
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::REPROVADO, self::REVOGADO], true);
    }

    public function isActive(): bool
    {
        return !$this->isTerminal() && !in_array($this, [self::ATIVO_3_ANOS], true);
    }

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $s) => [$s->value => $s->getLabel()])
            ->toArray();
    }
}
