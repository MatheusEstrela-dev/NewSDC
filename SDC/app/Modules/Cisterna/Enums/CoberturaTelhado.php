<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

use App\Modules\Cisterna\Support\NormalizaEntrada;

/**
 * Material da cobertura do telhado, que e o que define se a agua captada serve.
 *
 * Casos medidos no SELECT DISTINCT sobre
 * cisterna_legado_raw.doc->>'coberturaTelhado', 8.105 linhas:
 *
 *   pvc            4.962  |  PVC            1        -> pvc
 *   ceramica       2.449  |  Cerâmica     434        -> ceramica
 *   fibrocimento     155  |  Fibrocimento   2        -> fibrocimento
 *   zinco             37  |  Zinco          2        -> zinco
 *   outros            13  |  Outros         9        -> outros
 *   metalica          10                             -> metalica
 *   concreto           5  |  Concreto       6        -> concreto
 *   amianto            6                             -> amianto
 *   0                 14                             -> null
 *
 * `fibrocimento` e `amianto` ficam separados de proposito. Tecnicamente
 * fibrocimento e cimento-amianto, mas o formulario oferecia os dois e os
 * usuarios os distinguem -- unificar apagaria uma distincao intencional.
 */
enum CoberturaTelhado: string
{
    case PVC = 'pvc';
    case CERAMICA = 'ceramica';
    case FIBROCIMENTO = 'fibrocimento';
    case ZINCO = 'zinco';
    case CONCRETO = 'concreto';
    case METALICA = 'metalica';
    case AMIANTO = 'amianto';
    case OUTROS = 'outros';

    public function label(): string
    {
        return match ($this) {
            self::PVC => 'PVC',
            self::CERAMICA => 'Ceramica',
            self::FIBROCIMENTO => 'Fibrocimento',
            self::ZINCO => 'Zinco',
            self::CONCRETO => 'Concreto',
            self::METALICA => 'Metalica',
            self::AMIANTO => 'Amianto',
            self::OUTROS => 'Outros',
        };
    }

    /**
     * Texto livre do legado -> case. Aqui basta normalizar: os valores do legado
     * ja saem do formulario com a mesma grafia dos casos, variando so caixa e
     * acento (`Cerâmica`, `Concreto`, `PVC`).
     */
    public static function doLegado(?string $valor): ?self
    {
        $normalizado = NormalizaEntrada::chaveTexto($valor);

        if ($normalizado === null || $normalizado === '0') {
            return null;
        }

        return self::tryFrom($normalizado);
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
