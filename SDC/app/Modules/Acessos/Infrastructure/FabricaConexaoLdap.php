<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Infrastructure;

use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Exceptions\DiretorioIndisponivel;
use LdapRecord\Connection;

/**
 * Monta a conexao LdapRecord a partir de `config('acessos.diretorio')`
 * (spec 4.3): hosts por FQDN (lista ou SRV do dominio, o preferencial
 * primeiro), LDAPS ou StartTLS com certificado obrigatorio, timeouts e sem
 * referrals. A senha de bind vem de SegredoDiretorio no momento do uso.
 * Nao e final: o teste sobrescreve a consulta DNS (registrosSrv).
 */
class FabricaConexaoLdap
{
    private const REGISTRO_SRV = '_ldap._tcp.dc._msdcs.';

    private const FQDN = '/^(?=.{1,253}$)[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/i';

    private const ROTULO_FINAL_NUMERICO = '/\.[0-9]+$/';

    public function __construct(private readonly SegredoDiretorio $segredos) {}

    public function criar(): Connection
    {
        $config = (array) config('acessos.diretorio');
        $ldaps = ($config['seguranca'] ?? 'ldaps') !== 'starttls';

        $porta = $this->inteiro($config['porta'] ?? null, 65535);
        $timeoutConexao = $this->inteiro($config['timeout_conexao'] ?? null);
        $timeoutOperacao = $this->inteiro($config['timeout_operacao'] ?? null);
        $usuario = $this->texto($config['usuario'] ?? null);
        $baseDn = $this->texto($config['base_dn'] ?? null);
        $ca = $this->texto($config['ca_cert'] ?? null);

        if ($usuario === null || $baseDn === null || $ca === null) {
            throw $this->configAusente();
        }
        if (! is_file($ca) || ! is_readable($ca)) {
            throw new DiretorioIndisponivel(CodigoErroDiretorio::CERTIFICADO);
        }

        return new Connection([
            'hosts' => $this->hosts(),
            'port' => $porta,
            'base_dn' => $baseDn,
            'username' => $usuario,
            'password' => $this->segredos->senhaBind(),
            'use_ssl' => $ldaps,
            'use_tls' => ! $ldaps,
            'timeout' => $timeoutConexao,
            'follow_referrals' => false,
            'options' => [
                LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_HARD,
                LDAP_OPT_X_TLS_CACERTFILE => $ca,
                LDAP_OPT_NETWORK_TIMEOUT => $timeoutConexao,
                LDAP_OPT_TIMELIMIT => $timeoutOperacao,
                LDAP_OPT_REFERRALS => 0,
            ],
        ]);
    }

    /**
     * Hosts na ordem de tentativa: o preferencial (PDC emulator) primeiro e
     * depois a lista da config ou, sem ela, o SRV do dominio. IP e recusado.
     *
     * @return list<string>
     */
    public function hosts(): array
    {
        $config = (array) config('acessos.diretorio');
        $hosts = array_values(array_filter((array) ($config['hosts'] ?? []), fn ($host): bool => is_string($host) && trim($host) !== ''));

        if ($hosts === []) {
            $dominio = $this->texto($config['dominio'] ?? null);
            if ($dominio === null) {
                throw $this->configAusente();
            }
            $hosts = $this->resolverSrv(self::REGISTRO_SRV.$this->validarHost($dominio));
            if ($hosts === []) {
                throw new DiretorioIndisponivel(CodigoErroDiretorio::INDISPONIVEL);
            }
        }

        $preferencial = $this->texto($config['host_preferencial'] ?? null);
        if ($preferencial !== null) {
            array_unshift($hosts, $preferencial);
        }

        $unicos = [];
        foreach ($hosts as $host) {
            $host = $this->validarHost($host);
            $unicos[mb_strtolower($host)] ??= $host;
        }

        return array_values($unicos);
    }

    /**
     * Alvos do SRV ordenados por prioridade (menor primeiro) e peso (maior primeiro).
     *
     * @return list<string>
     */
    protected function resolverSrv(string $registro): array
    {
        $registros = array_values(array_filter(
            $this->registrosSrv($registro),
            static fn ($r): bool => is_array($r) && is_string($r['target'] ?? null) && $r['target'] !== '',
        ));
        usort($registros, static fn (array $a, array $b): int => [(int) ($a['pri'] ?? 0), -(int) ($a['weight'] ?? 0)] <=> [(int) ($b['pri'] ?? 0), -(int) ($b['weight'] ?? 0)]);

        return array_map(static fn (array $r): string => rtrim((string) $r['target'], '.'), $registros);
    }

    /** @return list<array<string, mixed>> registros brutos do DNS */
    protected function registrosSrv(string $registro): array
    {
        $registros = @dns_get_record($registro, DNS_SRV);

        return is_array($registros) ? $registros : [];
    }

    private function validarHost(string $host): string
    {
        $host = rtrim(trim($host), '.');
        // ultimo rotulo numerico (`127.1`, `10.0.0.1`) e IP abreviado, nao FQDN
        if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false
            || preg_match(self::FQDN, $host) !== 1
            || preg_match(self::ROTULO_FINAL_NUMERICO, $host) === 1) {
            throw $this->configAusente();
        }

        return $host;
    }

    private function inteiro(mixed $valor, int $maximo = PHP_INT_MAX): int
    {
        if (! is_int($valor) || $valor < 1 || $valor > $maximo) {
            throw $this->configAusente();
        }

        return $valor;
    }

    private function texto(mixed $valor): ?string
    {
        return is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
    }

    private function configAusente(): DiretorioIndisponivel
    {
        return new DiretorioIndisponivel(CodigoErroDiretorio::CONFIG_AUSENTE);
    }
}
