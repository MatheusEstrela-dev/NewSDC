<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

use App\Modules\Cisterna\Support\NormalizaEntrada;

/**
 * Regime de posse da moradia do beneficiario.
 *
 * O nome do campo no legado era `moradia`, e o do dominio ficou `tipo_moradia`,
 * mas o que a coluna guarda e POSSE, nao material de construcao. Os casos saem
 * do SELECT DISTINCT sobre cisterna_legado_raw.doc->>'moradia', 8.105 linhas:
 *
 *   PROPRIA     5.182  |  propria  2.515  |  PR<U+FFFD>PRIA  67   -> propria
 *   Outros         84  |  OUTROS      19  |  outros        5      -> outros
 *   CEDIDA         41  |  cedida      16                          -> cedida
 *   ALUGADA        12  |  alugada      2                          -> alugada
 *   0             162                                             -> null
 */
enum TipoMoradia: string
{
    case PROPRIA = 'propria';
    case CEDIDA = 'cedida';
    case ALUGADA = 'alugada';
    case OUTROS = 'outros';

    public function label(): string
    {
        return match ($this) {
            self::PROPRIA => 'Propria',
            self::CEDIDA => 'Cedida',
            self::ALUGADA => 'Alugada',
            self::OUTROS => 'Outros',
        };
    }

    /**
     * Texto livre do legado -> case. Null quando nao reconhece: o refino grava
     * a coluna nula e o valor original continua no doc jsonb, sem perda.
     */
    public static function doLegado(?string $valor): ?self
    {
        $normalizado = NormalizaEntrada::chaveTexto($valor);

        if ($normalizado === null || $normalizado === '0') {
            return null;
        }

        return match (true) {
            // 67 cadastros gravaram "PROPRIA" com o caractere de substituicao
            // U+FFFD no lugar do O acentuado: o varchar(7) utf8mb3 do legado
            // nao aguentava "PRÓPRIA". Nao e um regime de posse diferente.
            (bool) preg_match('/^pr.?pria$/u', $normalizado) => self::PROPRIA,
            str_contains($normalizado, 'cedida') => self::CEDIDA,
            str_contains($normalizado, 'alugada') => self::ALUGADA,
            str_starts_with($normalizado, 'outro') => self::OUTROS,
            default => null,
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
