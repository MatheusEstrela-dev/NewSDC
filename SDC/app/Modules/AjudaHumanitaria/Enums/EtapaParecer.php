<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Etapa do fluxo a que o parecer pertence (RN-10).
 *
 * Os slugs sao preservados do legado (aju_h_pedido_an_tec.tramit_parecer).
 * A etapa 'analise_drd' existia no legado e estava desativada por comentario;
 * nao entra neste modulo.
 */
enum EtapaParecer: string
{
    case AnaliseDlog    = 'analise_dlog';
    case AnaliseDiretor = 'analise_coord';

    public function label(): string
    {
        return match ($this) {
            self::AnaliseDlog    => 'Análise DLOG',
            self::AnaliseDiretor => 'Análise Diretor DLOG',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
