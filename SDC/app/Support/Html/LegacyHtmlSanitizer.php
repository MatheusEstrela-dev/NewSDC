<?php

declare(strict_types=1);

namespace App\Support\Html;

/**
 * Higieniza o HTML sujo herdado do editor legado (Word/TinyMCE) antes de exibir.
 *
 * Mantem apenas formatacao basica segura e remove scripts, styles, atributos de
 * evento (on*), estilos inline e lixo do MS Word (tags o:p, mso-*), evitando XSS
 * armazenado ao renderizar com v-html no frontend.
 */
class LegacyHtmlSanitizer
{
    /** Tags de formatacao permitidas. */
    private const TAGS_PERMITIDAS = '<p><br><b><strong><i><em><u><ul><ol><li><span><div><h1><h2><h3><h4><table><thead><tbody><tr><td><th>';

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // Remove blocos perigosos por completo (conteudo incluso).
        $html = (string) preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html);

        // Remove tags proprietarias do Word (o:p, v:*, w:*, etc.) e comentarios.
        $html = (string) preg_replace('#</?[a-z]+:[^>]*>#i', '', $html);
        $html = (string) preg_replace('#<!--.*?-->#s', '', $html);

        // Mantem apenas as tags da allowlist.
        $html = strip_tags($html, self::TAGS_PERMITIDAS);

        // Remove todos os atributos das tags remanescentes (style, class, on*, etc.).
        $html = (string) preg_replace('#<([a-z0-9]+)\b[^>]*>#i', '<$1>', $html);

        return trim($html);
    }
}
