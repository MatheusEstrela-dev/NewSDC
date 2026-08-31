<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Enums;

/**
 * Situacao de uma Ata de Registro de Precos, derivada das datas de vigencia.
 *
 * Modelamos um ESTADO unico em vez de flags booleanas soltas (`ativo` +
 * `vigente` + `vencida`), que podiam se contradizer entre si. Os quatro casos
 * abaixo sao mutuamente exclusivos e cobrem 100% das combinacoes possiveis.
 *
 * A precedencia (e a regra de calculo) vive em
 * @see \App\Modules\Tdap\Support\VigenciaAta::situacao()
 */
enum SituacaoAta: string
{
    /** Ata ligada cuja vigencia ainda nao comecou (dt_inicio > hoje). */
    case Agendada = 'agendada';

    /** Ata ligada dentro da janela de vigencia (dt_inicio <= hoje <= dt_final). */
    case Vigente = 'vigente';

    /** Ata ligada cuja vigencia expirou (dt_final < hoje). */
    case Vencida = 'vencida';

    /** Ata desligada manualmente (ativo = false), independente das datas. */
    case Inativa = 'inativa';

    public function label(): string
    {
        return match ($this) {
            self::Agendada => 'Agendada',
            self::Vigente  => 'Vigente',
            self::Vencida  => 'Vencida',
            self::Inativa  => 'Inativa',
        };
    }

    /**
     * Token semantico de cor consumido pelos badges do front.
     *
     * Devolvemos o token (e nao classes Tailwind) para o back nao conhecer CSS:
     * o mapa token -> classe fica em Pages/Tdap/Atas/Index.vue.
     */
    public function cor(): string
    {
        return match ($this) {
            self::Agendada => 'info',
            self::Vigente  => 'success',
            self::Vencida  => 'danger',
            self::Inativa  => 'neutral',
        };
    }

    /** Atalho de leitura para as regras que so olham a vigencia corrente. */
    public function isVigente(): bool
    {
        return $this === self::Vigente;
    }

    /** Atalho de leitura para o novo indicador de vencimento. */
    public function isVencida(): bool
    {
        return $this === self::Vencida;
    }

    /**
     * Opcoes para o <select> de filtro da listagem.
     *
     * O formato {value, label} e o esperado por SelectInput.vue.
     *
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
