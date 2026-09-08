<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Enums;

/**
 * As 10 macrorregioes do legado gestaocedec, que viviam hardcoded num array PHP em
 * mod_cedec/backEnd/View/municipio/cadastrar.php:20-30.
 *
 * O valor de cada caso E o rotulo: o legado gravava o texto em
 * cedec_municipio.macroregiao (varchar), nao um id. Atencao a grafia da COLUNA --
 * macroregiao, com um r so -- que difere do termo do dominio, macrorregiao.
 */
enum Macrorregiao: string
{
    case SUL_DE_MINAS = 'SUL DE MINAS';
    case ALTO_PARANAIBA = 'ALTO PARANAIBA';
    case CENTRAL = 'CENTRAL';
    case ZONA_DA_MATA = 'ZONA DA MATA';
    case VALE_DO_RIO_DOCE = 'VALE DO RIO DOCE';
    case TRIANGULO = 'TRIANGULO';
    case CENTRO_OESTE = 'CENTRO OESTE';
    case JEQUITINHONHA_MUCURI = 'JEQUITINHONHA MUCURI';
    case NORTE_DE_MINAS = 'NORTE DE MINAS';
    case NOROESTE_DE_MINAS = 'NOROESTE DE MINAS';

    public function label(): string
    {
        return $this->value;
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opcoes(): array
    {
        return array_map(
            static fn (self $caso): array => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
