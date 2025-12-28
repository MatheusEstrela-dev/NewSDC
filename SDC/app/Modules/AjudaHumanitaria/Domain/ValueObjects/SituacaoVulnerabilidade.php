<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Situação de Vulnerabilidade do Beneficiário
 */
enum SituacaoVulnerabilidade: string
{
    case DESABRIGADO = 'DESABRIGADO';
    case DESALOJADO = 'DESALOJADO';
    case ISOLADO = 'ISOLADO';
    case EM_RISCO = 'EM_RISCO';
    case OUTRA = 'OUTRA';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DESABRIGADO => 'Desabrigado',
            self::DESALOJADO => 'Desalojado',
            self::ISOLADO => 'Isolado',
            self::EM_RISCO => 'Em Risco',
            self::OUTRA => 'Outra',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::DESABRIGADO => 'red',
            self::DESALOJADO => 'orange',
            self::ISOLADO => 'yellow',
            self::EM_RISCO => 'purple',
            self::OUTRA => 'gray',
        };
    }

    /**
     * Verifica se é situação crítica
     */
    public function isCritica(): bool
    {
        return in_array($this, [
            self::DESABRIGADO,
            self::DESALOJADO,
        ]);
    }

    /**
     * Lista todas as situações disponíveis para select
     */
    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $situacao) => [
                'value' => $situacao->value,
                'label' => $situacao->getLabel(),
                'color' => $situacao->getBadgeColor(),
            ],
            self::cases()
        );
    }
}
