<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\ValueObjects;

/**
 * Value Object: Prioridade da Tarefa
 *
 * Implementa cálculo automático baseado em Matriz de Impacto x Urgência
 * conforme especificado no papiro task01.md
 */
enum Prioridade: int
{
    case PLANEJADA = 5;    // Baixa urgência + Baixo impacto
    case BAIXA = 4;        // Baixa urgência + Médio impacto OU Média urgência + Baixo impacto
    case MEDIA = 3;        // Média urgência + Médio impacto
    case ALTA = 2;         // Alta urgência + Médio impacto OU Média urgência + Alto impacto
    case CRITICA = 1;      // Alta urgência + Alto impacto

    /**
     * Retorna o label humanizado da prioridade
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PLANEJADA => 'Planejada',
            self::BAIXA => 'Baixa',
            self::MEDIA => 'Média',
            self::ALTA => 'Alta',
            self::CRITICA => 'Crítica',
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
     * Retorna a classe CSS de cor (Tailwind)
     */
    public function getColorClass(): string
    {
        return match ($this) {
            self::PLANEJADA => 'bg-slate-100 text-slate-800',
            self::BAIXA => 'bg-blue-100 text-blue-800',
            self::MEDIA => 'bg-yellow-100 text-yellow-800',
            self::ALTA => 'bg-orange-100 text-orange-800',
            self::CRITICA => 'bg-red-100 text-red-800',
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
     * Retorna o ícone representativo
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::PLANEJADA => 'calendar',
            self::BAIXA => 'arrow-down',
            self::MEDIA => 'minus',
            self::ALTA => 'arrow-up',
            self::CRITICA => 'alert-triangle',
        };
    }

    /**
     * Calcula a prioridade automaticamente baseado na Matriz Impacto x Urgência
     *
     * @param Impacto $impacto
     * @param Urgencia $urgencia
     * @return self
     */
    public static function calcularPorMatriz(Impacto $impacto, Urgencia $urgencia): self
    {
        // Matriz de Prioridade (ITIL/ITSM)
        // Urgência -> Alta | Média | Baixa
        // Impacto ↓
        // Alto     -> 1    | 2     | 2
        // Médio    -> 2    | 3     | 4
        // Baixo    -> 2    | 4     | 5

        return match ([$impacto, $urgencia]) {
            // Crítica (1) - Alto impacto + Alta urgência
            [Impacto::ALTO, Urgencia::ALTA] => self::CRITICA,

            // Alta (2) - Combinações de Alto/Alta com Médio/Média
            [Impacto::ALTO, Urgencia::MEDIA],
            [Impacto::ALTO, Urgencia::BAIXA],
            [Impacto::MEDIO, Urgencia::ALTA] => self::ALTA,

            // Média (3) - Médio impacto + Média urgência
            [Impacto::MEDIO, Urgencia::MEDIA] => self::MEDIA,

            // Baixa (4) - Combinações de Médio/Baixo
            [Impacto::MEDIO, Urgencia::BAIXA],
            [Impacto::BAIXO, Urgencia::MEDIA] => self::BAIXA,

            // Planejada (5) - Baixo impacto + Baixa urgência
            [Impacto::BAIXO, Urgencia::BAIXA],
            [Impacto::BAIXO, Urgencia::ALTA] => self::PLANEJADA,
        };
    }

    /**
     * Retorna o SLA padrão em horas para esta prioridade
     */
    public function getSlaHoras(): int
    {
        return match ($this) {
            self::CRITICA => 4,      // 4 horas
            self::ALTA => 8,         // 8 horas (1 dia útil)
            self::MEDIA => 24,       // 24 horas (3 dias úteis)
            self::BAIXA => 40,       // 40 horas (5 dias úteis)
            self::PLANEJADA => 160,  // 160 horas (20 dias úteis)
        };
    }

    /**
     * Retorna a fila de processamento (para Redis queues)
     */
    public function getQueue(): string
    {
        return match ($this) {
            self::CRITICA => 'critical',
            self::ALTA => 'high',
            self::MEDIA => 'default',
            self::BAIXA => 'low',
            self::PLANEJADA => 'planned',
        };
    }

    /**
     * Retorna todos os valores como array associativo
     *
     * @return array<int, string>
     */
    public static function toSelectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $prioridade) => [
                $prioridade->value => $prioridade->getLabel(),
            ])
            ->toArray();
    }
}
