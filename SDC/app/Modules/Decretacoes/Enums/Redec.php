<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Enums;

/**
 * As 14 Regioes de Defesa Civil (REDEC) de Minas Gerais.
 *
 * Os valores sao os mesmos ids do legado (`cedec_municipio.redec_id` e
 * `rat_redec.id`), o que permite fazer a correspondencia municipio -> REDEC
 * direto pelo dump legado, sem tabela de apoio adicional.
 *
 * Substitui o antigo MockRedec (que listava apenas 5 regioes ficticias).
 */
enum Redec: int
{
    case REDEC_1  = 1;
    case REDEC_2  = 2;
    case REDEC_3  = 3;
    case REDEC_4  = 4;
    case REDEC_5  = 5;
    case REDEC_6  = 6;
    case REDEC_7  = 7;
    case REDEC_8  = 8;
    case REDEC_9  = 9;
    case REDEC_10 = 10;
    case REDEC_11 = 11;
    case REDEC_12 = 12;
    case REDEC_13 = 13;
    case REDEC_14 = 14;

    /** Sigla curta (ex: "3ª REDEC"). */
    public function sigla(): string
    {
        return $this->value . 'ª REDEC';
    }

    /** Regiao administrativa atendida. */
    public function regiao(): string
    {
        return match ($this) {
            self::REDEC_1  => 'Metropolitana de Belo Horizonte',
            self::REDEC_2  => 'Vale do Paraopeba',
            self::REDEC_3  => 'Campo das Vertentes',
            self::REDEC_4  => 'Zona da Mata',
            self::REDEC_5  => 'Triangulo Norte',
            self::REDEC_6  => 'Triangulo Sul',
            self::REDEC_7  => 'Norte de Minas',
            self::REDEC_8  => 'Vale do Rio Doce',
            self::REDEC_9  => 'Mucuri',
            self::REDEC_10 => 'Oeste de Minas',
            self::REDEC_11 => 'Sul de Minas',
            self::REDEC_12 => 'Circuito das Aguas',
            self::REDEC_13 => 'Serrana do Sul',
            self::REDEC_14 => 'Jequitinhonha',
        };
    }

    /** Rotulo exibido nas listas suspensas (ex: "3ª REDEC - Campo das Vertentes"). */
    public function label(): string
    {
        return $this->sigla() . ' - ' . $this->regiao();
    }

    /**
     * Opcoes para <select> (contrato id/label usado pelos FormSelect).
     *
     * @return array<int, array{id: int, label: string, sigla: string}>
     */
    public static function toSelectOptions(): array
    {
        return array_map(
            fn (self $case) => [
                'id'    => $case->value,
                'label' => $case->label(),
                'sigla' => $case->sigla(),
            ],
            self::cases()
        );
    }

    /** Rotulo a partir de um id cru (null quando desconhecido). */
    public static function labelFor(mixed $id): ?string
    {
        return is_numeric($id) ? (self::tryFrom((int) $id)?->label()) : null;
    }
}
