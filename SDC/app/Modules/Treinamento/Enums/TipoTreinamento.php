<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

/**
 * Modalidade do treinamento.
 *
 * PRESENCIAL       encontros no local informado.
 * SEMI_PRESENCIAL  parte presencial e parte a distancia: exige local (ha
 *                  encontro presencial) e nao abre sala de aula EAD - o
 *                  conteudo a distancia fica no cronograma do curso.
 * ONLINE           encontro remoto ao vivo (presenca autoconfirmavel).
 * EAD              curso com trilha e sala de aula, continuo.
 */
enum TipoTreinamento: string
{
    case PRESENCIAL = 'PRESENCIAL';
    case SEMI_PRESENCIAL = 'SEMI_PRESENCIAL';
    case ONLINE = 'ONLINE';
    case EAD = 'EAD';

    public function getLabel(): string
    {
        return match ($this) {
            self::PRESENCIAL => 'Presencial',
            self::SEMI_PRESENCIAL => 'Semi-presencial',
            self::ONLINE => 'Online',
            self::EAD => 'EAD (curso com trilha)',
        };
    }

    public function getBadgeColor(): string
    {
        return 'blue';
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PRESENCIAL => 'users',
            // Mesmo icone do presencial de proposito: a modalidade e nova e o
            // mapa de icones das telas nao muda neste bloco.
            self::SEMI_PRESENCIAL => 'users',
            self::ONLINE => 'computer-desktop',
            self::EAD => 'academic-cap',
        };
    }

    /** Ha encontro no mundo fisico: o local passa a fazer falta. */
    public function requerLocal(): bool
    {
        return $this === self::PRESENCIAL || $this === self::SEMI_PRESENCIAL;
    }

    /**
     * Abre sala de aula com trilha, itens, prova e certificado por conclusao.
     * So o EAD: o semi-presencial tem parte a distancia, mas ela e conduzida
     * pelo cronograma do curso, nao pela sala de aula do EAD.
     */
    public function temSalaDeAula(): bool
    {
        return $this === self::EAD;
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $tipo) => [
                'value' => $tipo->value,
                'label' => $tipo->getLabel(),
                'color' => $tipo->getBadgeColor(),
                'icon' => $tipo->getIcon(),
            ],
            self::cases()
        );
    }
}
