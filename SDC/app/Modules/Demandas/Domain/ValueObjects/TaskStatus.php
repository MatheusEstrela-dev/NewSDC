<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\ValueObjects;

/**
 * Value Object: Status de Tarefas
 *
 * Implementa State Machine para garantir transições válidas de status.
 * Baseado no padrão estabelecido em app/Modules/Rat/Domain/ValueObjects/Status.php
 */
enum TaskStatus: string
{
    case ABERTA = 'aberta';
    case EM_ANALISE = 'em_analise';
    case EM_PROGRESSO = 'em_progresso';
    case AGUARDANDO_TERCEIROS = 'aguardando_terceiros';
    case RESOLVIDA = 'resolvida';
    case FECHADA = 'fechada';
    case CANCELADA = 'cancelada';

    /**
     * Retorna o label humanizado do status
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ABERTA => 'Aberta',
            self::EM_ANALISE => 'Em Análise',
            self::EM_PROGRESSO => 'Em Progresso',
            self::AGUARDANDO_TERCEIROS => 'Aguardando Terceiros',
            self::RESOLVIDA => 'Resolvida',
            self::FECHADA => 'Fechada',
            self::CANCELADA => 'Cancelada',
        };
    }

    /**
     * Alias para getLabel() - para consistência
     */
    public function label(): string
    {
        return $this->getLabel();
    }

    /**
     * Retorna a classe CSS de cor para o status (Tailwind)
     */
    public function getColorClass(): string
    {
        return match ($this) {
            self::ABERTA => 'bg-blue-100 text-blue-800',
            self::EM_ANALISE => 'bg-yellow-100 text-yellow-800',
            self::EM_PROGRESSO => 'bg-indigo-100 text-indigo-800',
            self::AGUARDANDO_TERCEIROS => 'bg-orange-100 text-orange-800',
            self::RESOLVIDA => 'bg-green-100 text-green-800',
            self::FECHADA => 'bg-gray-100 text-gray-800',
            self::CANCELADA => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Alias para getColorClass() - para consistência
     */
    public function color(): string
    {
        return $this->getColorClass();
    }

    /**
     * Retorna ícone representativo do status
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::ABERTA => 'inbox',
            self::EM_ANALISE => 'search',
            self::EM_PROGRESSO => 'cog',
            self::AGUARDANDO_TERCEIROS => 'clock',
            self::RESOLVIDA => 'check-circle',
            self::FECHADA => 'archive',
            self::CANCELADA => 'x-circle',
        };
    }

    /**
     * State Machine: Valida se a transição para um novo status é permitida
     *
     * @param TaskStatus $newStatus
     * @return bool
     */
    public function canTransitionTo(TaskStatus $newStatus): bool
    {
        return in_array($newStatus, $this->getAllowedTransitions(), true);
    }

    /**
     * Retorna os status permitidos para transição a partir do status atual
     *
     * @return array<TaskStatus>
     */
    public function getAllowedTransitions(): array
    {
        return match ($this) {
            self::ABERTA => [
                self::EM_ANALISE,
                self::CANCELADA,
            ],
            self::EM_ANALISE => [
                self::EM_PROGRESSO,
                self::AGUARDANDO_TERCEIROS,
                self::CANCELADA,
            ],
            self::EM_PROGRESSO => [
                self::AGUARDANDO_TERCEIROS,
                self::RESOLVIDA,
                self::CANCELADA,
            ],
            self::AGUARDANDO_TERCEIROS => [
                self::EM_PROGRESSO,
                self::RESOLVIDA,
                self::CANCELADA,
            ],
            self::RESOLVIDA => [
                self::FECHADA,
                self::EM_PROGRESSO, // Pode reabrir se necessário
            ],
            self::FECHADA => [
                // Fechada é final (apenas super-admin pode reabrir via EM_PROGRESSO)
            ],
            self::CANCELADA => [
                // Cancelada é final
            ],
        };
    }

    /**
     * Verifica se o status é considerado "finalizado"
     */
    public function isFinalized(): bool
    {
        return in_array($this, [self::FECHADA, self::CANCELADA], true);
    }

    /**
     * Verifica se o status está ativo (pode receber ações)
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::ABERTA,
            self::EM_ANALISE,
            self::EM_PROGRESSO,
            self::AGUARDANDO_TERCEIROS,
        ], true);
    }

    /**
     * Retorna todos os status disponíveis como array associativo
     *
     * @return array<string, string>
     */
    public static function toSelectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [
                $status->value => $status->getLabel(),
            ])
            ->toArray();
    }
}
