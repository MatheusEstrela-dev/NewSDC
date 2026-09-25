<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Support;

/**
 * Hash encadeado da trilha de um pedido (plano, secao 5):
 *
 *   hash = sha256(json(campos do evento) + hash_anterior)
 *
 * Alterar qualquer evento - mesmo por escrita direta no banco - quebra a
 * cadeia a partir dele, e a verificacao aponta onde. O mesmo calculo serve
 * para gravar e para verificar.
 */
final class HashEvento
{
    /** Campos que entram no hash, em ordem fixa. */
    private const CAMPOS = [
        'pedido_id', 'sequencia', 'etapa', 'de_status', 'para_status', 'ator_user_id',
        'permissao', 'justificativa', 'ip_address', 'ocorrido_em',
    ];

    /** @param array<string, mixed> $evento */
    public static function calcular(array $evento, ?string $anterior): string
    {
        $normalizado = [];
        foreach (self::CAMPOS as $campo) {
            $valor = $evento[$campo] ?? null;
            $normalizado[$campo] = $valor === null ? null : (string) $valor;
        }

        return hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . ($anterior ?? ''));
    }

    /**
     * Recalcula a cadeia na ordem da sequencia.
     *
     * @param  list<array<string, mixed>>  $eventos
     * @return int|null Sequencia do primeiro evento adulterado; null se integra.
     */
    public static function verificar(array $eventos): ?int
    {
        $anterior = null;
        foreach ($eventos as $evento) {
            if (($evento['hash_anterior'] ?? null) !== $anterior || self::calcular($evento, $anterior) !== $evento['hash']) {
                return (int) $evento['sequencia'];
            }
            $anterior = $evento['hash'];
        }

        return null;
    }
}
