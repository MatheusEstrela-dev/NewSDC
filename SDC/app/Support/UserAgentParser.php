<?php

namespace App\Support;

/**
 * Parser leve de User-Agent para auditoria.
 *
 * Retorna uma string compacta tipo "Chrome 148 / Windows" — suficiente
 * para indicar o cliente no DB sem instalar lib externa. Sem heuristicas
 * complexas (bots, dispositivos, etc.); se nada bater, devolve "Desconhecido".
 *
 * Cobertura: Chrome (e baseados em Chromium - Edge, Opera, Brave), Firefox,
 * Safari, Edge legado, IE. OS: Windows, macOS, Linux, Android, iOS.
 */
final class UserAgentParser
{
    public static function summarize(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        $browser = self::detectBrowser($userAgent);
        $os = self::detectOs($userAgent);

        if ($browser === null && $os === null) {
            return 'Desconhecido';
        }

        $browser = $browser ?? 'Browser desconhecido';
        $os = $os ?? 'SO desconhecido';

        return mb_substr("{$browser} / {$os}", 0, 100);
    }

    private static function detectBrowser(string $ua): ?string
    {
        // Ordem importa: Edge/Opera/Brave contem "Chrome" no UA, entao
        // checamos os derivados ANTES do Chrome puro.
        $rules = [
            // Edge (Chromium-based): "Edg/120.0.0.0"
            '/\bEdg\/(\d+)/' => 'Edge',
            // Opera
            '/\bOPR\/(\d+)/' => 'Opera',
            // Vivaldi
            '/\bVivaldi\/(\d+)/' => 'Vivaldi',
            // Brave (raramente identifica, mas tenta)
            '/\bBrave\/(\d+)/' => 'Brave',
            // Firefox
            '/\bFirefox\/(\d+)/' => 'Firefox',
            // Samsung Internet
            '/\bSamsungBrowser\/(\d+)/' => 'Samsung Internet',
            // Chrome (deve vir DEPOIS dos baseados em Chromium)
            '/\bChrome\/(\d+)/' => 'Chrome',
            // Safari (deve vir POR ULTIMO porque WebKit aparece em muitos UAs)
            '/\bVersion\/(\d+)[^\s]*\s+Safari\//' => 'Safari',
            // Edge legado (EdgeHTML)
            '/\bEdge\/(\d+)/' => 'Edge legado',
            // IE
            '/\bMSIE (\d+)/' => 'Internet Explorer',
            '/\bTrident\/.+rv:(\d+)/' => 'Internet Explorer',
        ];

        foreach ($rules as $pattern => $name) {
            if (preg_match($pattern, $ua, $m)) {
                return $name . ' ' . $m[1];
            }
        }

        return null;
    }

    private static function detectOs(string $ua): ?string
    {
        // iOS antes de macOS porque iPad pode aparecer como Mac em alguns UAs.
        if (preg_match('/\b(iPhone|iPad|iPod);.*CPU.*OS (\d+)_(\d+)/', $ua, $m)) {
            return 'iOS ' . $m[2] . '.' . $m[3];
        }
        if (preg_match('/\bAndroid (\d+)/', $ua, $m)) {
            return 'Android ' . $m[1];
        }
        if (preg_match('/\bWindows NT 10\.0/', $ua)) {
            // Sem como distinguir Win10 de Win11 pelo UA padrao
            // (alguns browsers expoem via UA-CH; aqui ficamos no NT 10.0).
            return 'Windows';
        }
        if (preg_match('/\bWindows NT (\d+\.\d+)/', $ua, $m)) {
            return 'Windows NT ' . $m[1];
        }
        if (preg_match('/\bMac OS X (\d+)[_\.](\d+)/', $ua, $m)) {
            return 'macOS ' . $m[1] . '.' . $m[2];
        }
        if (preg_match('/\bCrOS\b/', $ua)) {
            return 'ChromeOS';
        }
        if (preg_match('/\bLinux\b/', $ua)) {
            return 'Linux';
        }

        return null;
    }
}
