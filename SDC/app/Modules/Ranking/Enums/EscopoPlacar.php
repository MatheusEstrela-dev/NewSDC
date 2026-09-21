<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Enums;

/**
 * Dimensao do placar.
 *
 * Um mesmo lancamento aparece nas tres dimensoes, mas continua sendo UM
 * lancamento: 20 pontos de Ana viram 20 no placar dela, 20 na contribuicao do
 * orgao e 20 na do municipio. Nao sao 60.
 */
enum EscopoPlacar: string
{
    case Usuario = 'usuario';
    case Orgao = 'orgao';
    case Municipio = 'municipio';

    public function label(): string
    {
        return match ($this) {
            self::Usuario   => 'Meu placar',
            self::Orgao     => 'Meu orgao',
            self::Municipio => 'Meu municipio',
        };
    }

    /**
     * Coluna do lancamento que identifica a entidade desta dimensao.
     */
    public function colunaEntidade(): string
    {
        return match ($this) {
            self::Usuario   => 'credited_user_id',
            self::Orgao     => 'orgao_id',
            self::Municipio => 'municipio_id',
        };
    }

    /**
     * Municipio pode ser nulo no lancamento: usuario de orgao estadual sem
     * municipio de origem aplicavel nao entra no placar municipal.
     */
    public function aceitaEntidadeNula(): bool
    {
        return $this === self::Municipio;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
