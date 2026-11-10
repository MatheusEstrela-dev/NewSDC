<?php

declare(strict_types=1);

namespace App\Services\Integration;

/**
 * Politica anti-SSRF para chamadas de saida do hub de integracao.
 *
 * Regras: host obrigatoriamente na allowlist (config integrations.outbound),
 * esquema https (http so para hosts em http_hosts), e o host nao pode apontar
 * (literal ou via DNS) para IP privado, loopback, link-local ou reservado.
 */
final class OutboundUrlGuard
{
    /**
     * @throws UrlSaidaNaoPermitidaException
     */
    public function assertPermitida(string $url): void
    {
        $partes = parse_url($url);
        $host = strtolower((string) ($partes['host'] ?? ''));
        $esquema = strtolower((string) ($partes['scheme'] ?? ''));

        if ($host === '' || ! in_array($esquema, ['http', 'https'], true)) {
            throw new UrlSaidaNaoPermitidaException('URL de saida invalida.');
        }

        if (isset($partes['user']) || isset($partes['pass'])) {
            throw new UrlSaidaNaoPermitidaException('URL de saida com credenciais embutidas nao e permitida.');
        }

        if (! $this->hostNaLista($host, (array) config('integrations.outbound.allowed_hosts', []))) {
            throw new UrlSaidaNaoPermitidaException("Host de saida fora da allowlist: {$host}.");
        }

        if ($esquema !== 'https' && ! $this->hostNaLista($host, (array) config('integrations.outbound.http_hosts', []))) {
            throw new UrlSaidaNaoPermitidaException('Somente https e permitido para este host.');
        }

        foreach ($this->resolver($host) as $ip) {
            if ($this->ipProibido($ip)) {
                throw new UrlSaidaNaoPermitidaException("Host de saida resolve para endereco nao publico: {$host}.");
            }
        }
    }

    /** @param list<string> $lista */
    private function hostNaLista(string $host, array $lista): bool
    {
        foreach ($lista as $permitido) {
            $permitido = strtolower(trim((string) $permitido));
            if ($permitido === '') {
                continue;
            }
            if ($host === $permitido || str_ends_with($host, '.' . ltrim($permitido, '.'))) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function resolver(string $host): array
    {
        $literal = trim($host, '[]');
        if (filter_var($literal, FILTER_VALIDATE_IP) !== false) {
            return [$literal];
        }

        $ips = gethostbynamel($host) ?: [];
        // Sem resolucao nao ha como garantir o destino: recusa.
        if ($ips === []) {
            throw new UrlSaidaNaoPermitidaException("Host de saida nao resolvido: {$host}.");
        }

        return array_values($ips);
    }

    private function ipProibido(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
