<?php

namespace App\Modules\Tdap\Domain\ValueObjects;

enum RecebimentoStatus: string
{
    case PENDENTE = 'pendente';
    case EM_CONFERENCIA = 'em_conferencia';
    case CONFERIDO = 'conferido';
    case APROVADO = 'aprovado';
    case REJEITADO = 'rejeitado';
    case FINALIZADO = 'finalizado';

    public function getLabel(): string
    {
        return match($this) {
            self::PENDENTE => 'Pendente',
            self::EM_CONFERENCIA => 'Em Conferência',
            self::CONFERIDO => 'Conferido',
            self::APROVADO => 'Aprovado',
            self::REJEITADO => 'Rejeitado',
            self::FINALIZADO => 'Finalizado',
        };
    }

    public function getColorClass(): string
    {
        return match($this) {
            self::PENDENTE => 'bg-gray-100 text-gray-800',
            self::EM_CONFERENCIA => 'bg-blue-100 text-blue-800',
            self::CONFERIDO => 'bg-yellow-100 text-yellow-800',
            self::APROVADO => 'bg-green-100 text-green-800',
            self::REJEITADO => 'bg-red-100 text-red-800',
            self::FINALIZADO => 'bg-purple-100 text-purple-800',
        };
    }

    public function canTransitionTo(RecebimentoStatus $newStatus): bool
    {
        return in_array($newStatus, $this->getAllowedTransitions());
    }

    /**
     * @return RecebimentoStatus[]
     */
    public function getAllowedTransitions(): array
    {
        return match($this) {
            self::PENDENTE => [self::EM_CONFERENCIA, self::REJEITADO],
            self::EM_CONFERENCIA => [self::CONFERIDO, self::REJEITADO],
            self::CONFERIDO => [self::APROVADO, self::REJEITADO],
            self::APROVADO => [self::FINALIZADO],
            self::REJEITADO => [self::PENDENTE],
            self::FINALIZADO => [],
        };
    }
}
