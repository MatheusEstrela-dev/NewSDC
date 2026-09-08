<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Enums;

/**
 * Os 17 territorios de desenvolvimento do legado gestaocedec, que viviam hardcoded
 * num array PHP em mod_cedec/backEnd/View/municipio/cadastrar.php:33-49.
 *
 * O valor de cada caso E o rotulo, e a grafia sem acento e a do legado, porque e ela
 * que esta gravada em cedec_municipio.territorio_desenv (varchar).
 */
enum TerritorioDesenvolvimento: string
{
    case VERTENTES = 'Vertentes';
    case VALE_DO_RIO_DOCE = 'Vale do Rio Doce';
    case VALE_DO_ACO = 'Vale do Aco';
    case TRIANGULO_SUL = 'Triangulo Sul';
    case TRIANGULO_NORTE = 'Triangulo Norte';
    case SUL = 'Sul';
    case SUDOESTE = 'Sudoeste';
    case OESTE = 'Oeste';
    case NORTE = 'Norte';
    case NOROESTE = 'Noroeste';
    case MUCURI = 'Mucuri';
    case METROPOLITANA = 'Metropolitana';
    case MEDIO_E_BAIXO_JEQUITINHONHA = 'Medio e Baixo Jequitinhonha';
    case MATA = 'Mata';
    case CENTRAL = 'Central';
    case CAPARAO = 'Caparao';
    case ALTO_JEQUITINHONHA = 'Alto Jequitinhonha';

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
