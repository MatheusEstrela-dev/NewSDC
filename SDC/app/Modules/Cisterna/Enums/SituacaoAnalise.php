<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

/**
 * Ciclo de analise documental do cadastro. Substitui a coluna `aprovado`
 * (tinyint 0..5) do legado `sinc_cisterna`. Ortogonal a SituacaoObra: um
 * cadastro aprovado pode estar em Processamento, e um em Ressalva pode ja
 * estar Instalado.
 */
enum SituacaoAnalise: string
{
    case EM_EDICAO = 'em_edicao';
    case APROVADO = 'aprovado';
    case REPROVADO = 'reprovado';
    case RESSALVA = 'ressalva';
    case DESCONSIDERADO = 'desconsiderado';
    case DUPLICADO = 'duplicado';

    public function label(): string
    {
        return match ($this) {
            self::EM_EDICAO => 'Em Edicao',
            self::APROVADO => 'Aprovado',
            self::REPROVADO => 'Reprovado',
            self::RESSALVA => 'Ressalva',
            self::DESCONSIDERADO => 'Desconsiderar Cadastro',
            self::DUPLICADO => 'Duplicado',
        };
    }

    /**
     * Codigo numerico do legado, para o refino do ETL.
     */
    public static function doLegado(int|string|null $codigo): self
    {
        return match ((string) $codigo) {
            '1' => self::APROVADO,
            '2' => self::REPROVADO,
            '3' => self::RESSALVA,
            '4' => self::DESCONSIDERADO,
            '5' => self::DUPLICADO,
            default => self::EM_EDICAO,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
