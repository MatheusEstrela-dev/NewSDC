<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

/**
 * Status que a Area do Gestor mostra. E derivado - NAO existe coluna para ele.
 *
 * Combina tres coisas que o banco guarda separadas:
 * - treinamentos.status   (StatusTreinamento: fluxo do evento);
 * - treinamentos.publicado_em (publicacao no catalogo);
 * - treinamentos.situacao (SituacaoCurso: pausa/desativacao, ortogonal).
 *
 * PRECEDENCIA (primeira que casar vence):
 *   1. status CANCELADO     -> Cancelado
 *   2. status CONCLUIDO     -> Concluido
 *   3. status EM_ANDAMENTO  -> Em andamento
 *   4. status PLANEJADO e publicado_em preenchido -> Publicado
 *   5. caso restante        -> Planejado
 *
 * A situacao (Pausado/Desativado) NAO entra nessa cadeia: ela e devolvida ao
 * lado, por Treinamento::situacaoEfetiva(), porque um curso pausado continua
 * visivel e continua sendo "Em andamento" ou "Publicado" no fluxo. Juntar as
 * duas numa unica palavra esconderia a situacao do fluxo (um curso pausado
 * voltaria a aparecer como "Em andamento" ao ser retomado, sem registro de que
 * esteve pausado).
 */
enum StatusExibicaoCurso: string
{
    case PLANEJADO = 'PLANEJADO';
    case PUBLICADO = 'PUBLICADO';
    case EM_ANDAMENTO = 'EM_ANDAMENTO';
    case CONCLUIDO = 'CONCLUIDO';
    case CANCELADO = 'CANCELADO';

    public function getLabel(): string
    {
        return match ($this) {
            self::PLANEJADO => 'Planejado',
            self::PUBLICADO => 'Publicado',
            self::EM_ANDAMENTO => 'Em andamento',
            self::CONCLUIDO => 'Concluido',
            self::CANCELADO => 'Cancelado',
        };
    }

    /** Variante do atomo Badge. */
    public function getVariant(): string
    {
        return match ($this) {
            self::PLANEJADO => 'neutral',
            self::PUBLICADO => 'info',
            self::EM_ANDAMENTO => 'info',
            self::CONCLUIDO => 'success',
            self::CANCELADO => 'danger',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::PLANEJADO => 'gray',
            self::PUBLICADO => 'indigo',
            self::EM_ANDAMENTO => 'blue',
            self::CONCLUIDO => 'green',
            self::CANCELADO => 'red',
        };
    }

    /**
     * Unico ponto que aplica a precedencia descrita acima.
     */
    public static function derivar(StatusTreinamento $status, bool $publicado): self
    {
        return match ($status) {
            StatusTreinamento::CANCELADO => self::CANCELADO,
            StatusTreinamento::CONCLUIDO => self::CONCLUIDO,
            StatusTreinamento::EM_ANDAMENTO => self::EM_ANDAMENTO,
            StatusTreinamento::PLANEJADO => $publicado ? self::PUBLICADO : self::PLANEJADO,
        };
    }

    /**
     * @return list<array{value: string, label: string, color: string, variant: string}>
     */
    public static function toSelectArray(): array
    {
        return array_map(
            fn (self $status) => [
                'value' => $status->value,
                'label' => $status->getLabel(),
                'color' => $status->getBadgeColor(),
                'variant' => $status->getVariant(),
            ],
            self::cases(),
        );
    }
}
