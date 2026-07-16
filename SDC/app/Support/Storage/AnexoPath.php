<?php

declare(strict_types=1);

namespace App\Support\Storage;

/**
 * Monta caminhos relativos de anexos dentro do disk de cada modulo.
 *
 * O prefixo do modulo (PAE/, RAT/, ...) NAO entra aqui: ele e o root do
 * proprio disk (config/filesystems.php; no bind mount da VM vira
 * ANEXOS_ROOT/{MODULO}). Cada modulo mantem sua regra de negocio de
 * organizacao; este helper padroniza apenas a nomenclatura comum
 * "protocolos/{numero}/documentos/anexos" e a sanitizacao de segmentos.
 */
final class AnexoPath
{
    /**
     * Diretorio canonico dos anexos de um protocolo:
     * protocolos/{numero}/documentos/anexos
     */
    public static function protocolo(string $numeroProtocolo): string
    {
        return 'protocolos/' . self::sanitize($numeroProtocolo) . '/documentos/anexos';
    }

    /**
     * Sanitiza um segmento de path: colapsa qualquer caractere fora de
     * letras/numeros/._- (inclui / e \) em hifen e rejeita segmentos que
     * virariam traversal ("." / "..").
     */
    public static function sanitize(string $value): string
    {
        $segment = preg_replace('/[^\pL\pN._-]+/u', '-', trim($value));
        $segment = trim((string) $segment, '-');

        if ($segment === '' || preg_match('/^\.+$/', $segment)) {
            return 'sem-numero';
        }

        return $segment;
    }
}
