<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Enums;

/**
 * Prioridade como o legado pedia: baixa, media ou alta.
 *
 * Cada opcao vira um par impacto x urgencia, e a matriz ITIL calcula a
 * prioridade de 1 a 5. A volta (dePrioridade) agrupa as cinco faixas nas tres,
 * para o badge da tela.
 */
enum PrioridadeSimples: string
{
    case BAIXA = 'baixa';
    case MEDIA = 'media';
    case ALTA = 'alta';

    public function label(): string
    {
        return match ($this) {
            self::BAIXA => 'Baixa',
            self::MEDIA => 'Média',
            self::ALTA => 'Alta',
        };
    }

    public function impacto(): Impacto
    {
        return match ($this) {
            self::BAIXA => Impacto::BAIXO,
            self::MEDIA => Impacto::MEDIO,
            self::ALTA => Impacto::ALTO,
        };
    }

    public function urgencia(): Urgencia
    {
        return match ($this) {
            self::BAIXA => Urgencia::BAIXA,
            self::MEDIA => Urgencia::MEDIA,
            self::ALTA => Urgencia::ALTA,
        };
    }

    public static function dePrioridade(?Prioridade $prioridade): self
    {
        return match ($prioridade) {
            Prioridade::CRITICA, Prioridade::ALTA => self::ALTA,
            Prioridade::BAIXA, Prioridade::PLANEJADA => self::BAIXA,
            default => self::MEDIA,
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $p): array => ['value' => $p->value, 'label' => $p->label()],
            self::cases(),
        );
    }
}
