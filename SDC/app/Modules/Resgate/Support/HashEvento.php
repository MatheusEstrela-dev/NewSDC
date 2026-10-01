<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Support;

/**
 * Hash encadeado da trilha de um pedido (plano, secao 5):
 *
 *   hash = sha256(json(campos do evento + retrato do pedido) + hash_anterior)
 *
 * O RETRATO do pedido (ente, item e versao, custo, unidade, demonstracao,
 * solicitante) entra em todo evento: trocar o custo ou o ente direto no banco
 * quebra a cadeia, nao so alterar um evento. A verificacao tambem confere que
 * o ultimo evento leva ao status atual do pedido - eventos cortados do fim
 * aparecem como adulteracao.
 *
 * Instante em formato CANONICO UTC (FORMATO_SQL): o texto nao depende do
 * TimeZone nem do DateStyle da sessao, entao um auditor recalcula o mesmo
 * hash com psql em qualquer fuso.
 */
final class HashEvento
{
    /** Expressao SQL do instante canonico; use com a coluna ou clock_timestamp(). */
    public const FORMATO_SQL = "to_char(%s AT TIME ZONE 'UTC', 'YYYY-MM-DD\"T\"HH24:MI:SS.US\"Z\"')";

    /** Campos do evento que entram no hash, em ordem fixa. */
    private const CAMPOS = [
        'pedido_id', 'sequencia', 'etapa', 'de_status', 'para_status', 'ator_user_id', 'permissao',
        'justificativa', 'ip_address', 'user_agent', 'session_id', 'request_id', 'ocorrido_em',
    ];

    public static function instante(string $expressao): string
    {
        return sprintf(self::FORMATO_SQL, $expressao);
    }

    /** Retrato imutavel do pedido; os dados da solicitacao nunca mudam (trigger). */
    public static function retrato(array|object $pedido): string
    {
        $p = (array) $pedido;
        $demo = is_bool($p['demonstracao']) ? $p['demonstracao'] : in_array(strtolower((string) $p['demonstracao']), ['t', 'true', '1'], true);

        return implode('|', [
            $p['ente_escopo'], $p['ente_id'], $p['item_id'], $p['item_versao'], $p['custo_pontos'],
            $p['unidade_id'] ?? '', $demo ? '1' : '0', $p['solicitado_por'],
        ]);
    }

    /** @param array<string, mixed> $evento */
    public static function calcular(array $evento, string $retrato, ?string $anterior): string
    {
        $normalizado = [];
        foreach (self::CAMPOS as $campo) {
            $valor = $evento[$campo] ?? null;
            $normalizado[$campo] = $valor === null ? null : (string) $valor;
        }
        $normalizado['retrato'] = $retrato;

        return hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . ($anterior ?? ''));
    }

    /**
     * Recalcula a cadeia na ordem da sequencia e confere o fim contra o status.
     *
     * @param  list<array<string, mixed>>  $eventos  com ocorrido_em no formato canonico
     * @return int|null Sequencia do primeiro evento adulterado (ou a seguinte a
     *                  ultima, se o fim foi cortado); null se integra.
     */
    public static function verificar(array $eventos, array|object $pedido): ?int
    {
        $retrato = self::retrato($pedido);
        $anterior = null;
        foreach ($eventos as $evento) {
            if (($evento['hash_anterior'] ?? null) !== $anterior || self::calcular($evento, $retrato, $anterior) !== $evento['hash']) {
                return (int) $evento['sequencia'];
            }
            $anterior = $evento['hash'];
        }

        $ultimo = $eventos === [] ? null : $eventos[array_key_last($eventos)];
        if ($ultimo === null || $ultimo['para_status'] !== ((array) $pedido)['status']) {
            return $ultimo === null ? 1 : (int) $ultimo['sequencia'] + 1;
        }

        return null;
    }
}
