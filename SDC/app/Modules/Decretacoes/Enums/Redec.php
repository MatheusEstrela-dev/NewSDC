<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Enums;

/**
 * As 19 Regioes de Defesa Civil (REDEC) de Minas Gerais.
 *
 * Os valores sao os mesmos ids do legado (`cedec_municipio.redec_id` e
 * `rat_redec.id`), o que permite fazer a correspondencia municipio -> REDEC
 * direto pelo dump legado, sem tabela de apoio adicional.
 *
 * FONTE: relacao de regionais publicada pela propria CEDEC em
 * sistema.defesacivil.mg.gov.br (acao usuarioRegionaisSite). Cada REDEC
 * corresponde a uma Regiao da Policia Militar (RPM) e leva o nome da cidade
 * sede - por isso `regiao()` devolve a sede, e nao a antiga divisao por
 * mesorregiao.
 *
 * ATENCAO: a lista anterior tinha apenas 14 casos e usava nomes de mesorregiao
 * ("Campo das Vertentes", "Triangulo Norte"...) que nao batiam com a divisao
 * vigente. As REDECs 15 a 19 simplesmente nao existiam para o sistema: nao
 * apareciam nas listas suspensas, nao podiam ser filtradas nem exportadas, e
 * `labelFor()` devolvia null para os processos gravados com elas.
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
    case REDEC_15 = 15;
    case REDEC_16 = 16;
    case REDEC_17 = 17;
    case REDEC_18 = 18;
    case REDEC_19 = 19;

    /** Sigla curta (ex: "3ª REDEC"). */
    public function sigla(): string
    {
        return $this->value . 'ª REDEC';
    }

    /** Cidade sede da regional (mesma sede da RPM correspondente). */
    public function sede(): string
    {
        return match ($this) {
            self::REDEC_1  => 'Belo Horizonte',
            self::REDEC_2  => 'Contagem',
            self::REDEC_3  => 'Santa Luzia',
            self::REDEC_4  => 'Juiz de Fora',
            self::REDEC_5  => 'Uberaba',
            self::REDEC_6  => 'Lavras',
            self::REDEC_7  => 'Divinópolis',
            self::REDEC_8  => 'Governador Valadares',
            self::REDEC_9  => 'Uberlândia',
            self::REDEC_10 => 'Patos de Minas',
            self::REDEC_11 => 'Montes Claros',
            self::REDEC_12 => 'Ipatinga',
            self::REDEC_13 => 'Barbacena',
            self::REDEC_14 => 'Curvelo',
            self::REDEC_15 => 'Teófilo Otoni',
            self::REDEC_16 => 'Unaí',
            self::REDEC_17 => 'Pouso Alegre',
            self::REDEC_18 => 'Poços de Caldas',
            self::REDEC_19 => 'Sete Lagoas',
        };
    }

    /** Regiao atendida (identificada pela cidade sede). */
    public function regiao(): string
    {
        return $this->sede();
    }

    /** Regiao da Policia Militar correspondente (ex: "9ª RPM"). */
    public function rpm(): string
    {
        return $this->value . 'ª RPM';
    }

    /** Rotulo exibido nas listas suspensas (ex: "3ª REDEC - Santa Luzia"). */
    public function label(): string
    {
        return $this->sigla() . ' - ' . $this->regiao();
    }

    /**
     * Opcoes para <select> (contrato id/label usado pelos FormSelect).
     *
     * @return array<int, array{id: int, label: string, sigla: string, sede: string, rpm: string}>
     */
    public static function toSelectOptions(): array
    {
        return array_map(
            fn (self $case) => [
                'id'    => $case->value,
                'label' => $case->label(),
                'sigla' => $case->sigla(),
                'sede'  => $case->sede(),
                'rpm'   => $case->rpm(),
            ],
            self::cases()
        );
    }

    /**
     * Ids validos, para as regras de validacao acompanharem o enum.
     *
     * @return array<int, int>
     */
    public static function ids(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Rotulo a partir de um id cru (null quando desconhecido). */
    public static function labelFor(mixed $id): ?string
    {
        return is_numeric($id) ? (self::tryFrom((int) $id)?->label()) : null;
    }
}
