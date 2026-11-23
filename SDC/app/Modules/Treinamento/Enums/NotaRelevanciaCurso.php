<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

/**
 * Resposta a "Em que medida o conteudo apresentado foi relevante para sua
 * atuacao ou contribuiu para ampliar seus conhecimentos sobre o tema?" na
 * avaliacao do curso. Mesma barra de 5 niveis (e mesmas cores) da
 * NotaAvaliacaoCurso. O CHECK da coluna curso_avaliacoes.nota_relevancia
 * espelha a faixa 1..5.
 */
enum NotaRelevanciaCurso: int
{
    case NAO_RELEVANTE = 1;
    case POUCO_RELEVANTE = 2;
    case MODERADAMENTE_RELEVANTE = 3;
    case RELEVANTE = 4;
    case MUITO_RELEVANTE = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::NAO_RELEVANTE => 'Não foi relevante',
            self::POUCO_RELEVANTE => 'Pouco relevante',
            self::MODERADAMENTE_RELEVANTE => 'Moderadamente relevante',
            self::RELEVANTE => 'Relevante',
            self::MUITO_RELEVANTE => 'Muito relevante',
        };
    }

    /** Cor do segmento na barra (o front traduz para as classes do Tailwind). */
    public function getColor(): string
    {
        return match ($this) {
            self::NAO_RELEVANTE => 'red',
            self::POUCO_RELEVANTE => 'orange',
            self::MODERADAMENTE_RELEVANTE => 'yellow',
            self::RELEVANTE => 'lime',
            self::MUITO_RELEVANTE => 'green',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn (self $nota) => [
                'value' => $nota->value,
                'label' => $nota->getLabel(),
                'color' => $nota->getColor(),
            ],
            self::cases()
        );
    }
}
