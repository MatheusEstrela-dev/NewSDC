<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao;

use App\Modules\Demandas\Enums\StatusDemanda;

/**
 * Traducao dos valores do cedec-demanda. Status alem do enum da migration
 * (resolvido, fechado, reaberto) aparecem no codigo do legado e sao aceitos.
 */
final class MapaLegado
{
    public static function status(string $legado): StatusDemanda
    {
        return match (mb_strtolower(trim($legado))) {
            'em andamento', 'reaberto' => StatusDemanda::EM_PROGRESSO,
            'concluido', 'resolvido', 'fechado' => StatusDemanda::RESOLVIDA,
            default => StatusDemanda::ABERTA,
        };
    }

    public static function hash(array|object $linha): string
    {
        return hash('sha256', json_encode((array) $linha, JSON_UNESCAPED_UNICODE));
    }

    public static function json(mixed $valor): array
    {
        if (is_array($valor)) {
            return $valor;
        }
        $decodificado = is_string($valor) ? json_decode($valor, true) : null;

        return is_array($decodificado) ? $decodificado : [];
    }
}
