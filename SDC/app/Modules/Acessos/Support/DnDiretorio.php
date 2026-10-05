<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Support;

/**
 * Comparacao estrutural de DNs (RFC 4514), nunca textual: o DN e quebrado em
 * RDNs por ldap_explode_dn, que ja devolve `,` `=` `+` de dentro dos valores
 * como escape hex. Assim `OU=Evil\,OU=SDC` e um unico RDN e nao passa por
 * filho de `OU=SDC`. Cada RDN vira uma lista ordenada de pares
 * tipo/valor, com o tipo em minusculas e o valor sem escape e em minusculas.
 * DN invalido nunca esta dentro de nada nem e igual a nada.
 */
final class DnDiretorio
{
    /** O DN esta estritamente abaixo da base (a propria base nao conta). */
    public static function estaDentroDe(string $dn, string $base): bool
    {
        $rdns = self::rdns($dn);
        $rdnsBase = self::rdns($base);
        if ($rdns === null || $rdnsBase === null || $rdnsBase === [] || count($rdns) <= count($rdnsBase)) {
            return false;
        }

        return array_slice($rdns, -count($rdnsBase)) === $rdnsBase;
    }

    public static function iguais(string $a, string $b): bool
    {
        $rdnsA = self::rdns($a);

        return $rdnsA !== null && $rdnsA !== [] && $rdnsA === self::rdns($b);
    }

    /** @return list<list<array{0: string, 1: string}>>|null */
    private static function rdns(string $dn): ?array
    {
        $dn = trim($dn);
        $componentes = $dn === '' ? false : ldap_explode_dn($dn, 0);
        if (! is_array($componentes) || ! array_key_exists('count', $componentes)) {
            return null;
        }
        unset($componentes['count']);

        $rdns = [];
        foreach ($componentes as $componente) {
            $rdn = self::rdn((string) $componente);
            if ($rdn === null) {
                return null;
            }
            $rdns[] = $rdn;
        }

        return $rdns;
    }

    /**
     * RDN multivalorado (`CN=a+UID=b`): o `+` cru so aparece como separador,
     * porque o ldap_explode_dn escapa o `+` de dentro do valor.
     *
     * @return list<array{0: string, 1: string}>|null
     */
    private static function rdn(string $componente): ?array
    {
        $pares = [];
        foreach (explode('+', $componente) as $ava) {
            $partes = explode('=', $ava, 2);
            if (count($partes) !== 2 || trim($partes[0]) === '') {
                return null;
            }
            $pares[] = [mb_strtolower(trim($partes[0])), mb_strtolower(self::semEscape($partes[1]))];
        }
        sort($pares);

        return $pares;
    }

    private static function semEscape(string $valor): string
    {
        return (string) preg_replace_callback(
            '/\\\\([0-9A-Fa-f]{2}|.)/s',
            static fn (array $m): string => strlen($m[1]) === 2 ? chr((int) hexdec($m[1])) : $m[1],
            $valor,
        );
    }
}
