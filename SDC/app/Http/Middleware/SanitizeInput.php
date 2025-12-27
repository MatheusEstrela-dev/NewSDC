<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de Sanitização de Input (XSS Protection)
 *
 * Baseado no papiro2.md - "Sanitização e WAF"
 *
 * Problema: Alguém tenta injetar script malicioso no campo "Descrição do Desastre"
 * Sistema Amador: O script roda no navegador do chefe e rouba a sessão
 * Sistema Ultimate: O ataque é bloqueado na porta
 *
 * Este middleware limpa AUTOMATICAMENTE todos os inputs antes de chegarem no Controller.
 */
class SanitizeInput
{
    /**
     * Campos que NÃO devem ser sanitizados (ex: senha, tokens)
     * Esses campos podem conter caracteres especiais legítimos
     */
    private const SKIP_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'api_token',
        'bearer_token',
        '_token',
    ];

    /**
     * Tags HTML permitidas (whitelist segura)
     * Permite formatação básica, mas bloqueia scripts, iframes, etc
     */
    private const ALLOWED_TAGS = '<p><br><b><i><u><strong><em><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';

    /**
     * Atributos HTML perigosos que devem ser removidos
     */
    private const DANGEROUS_ATTRIBUTES = [
        'onload',
        'onerror',
        'onclick',
        'onmouseover',
        'onfocus',
        'onblur',
        'onchange',
        'onsubmit',
        'javascript:',
        'data:text/html',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Não sanitiza requisições JSON API (OpenAPI já valida)
        // Apenas sanitiza form-data e query strings
        if ($request->isJson() && $request->is('api/*')) {
            return $next($request);
        }

        // Sanitiza todos os inputs recursivamente
        $input = $this->sanitizeArray($request->all());

        // Substitui o input original pelo sanitizado
        $request->replace($input);

        return $next($request);
    }

    /**
     * Sanitiza array recursivamente
     */
    private function sanitizeArray(array $data): array
    {
        $cleaned = [];

        foreach ($data as $key => $value) {
            // Pula campos que não devem ser sanitizados
            if (in_array($key, self::SKIP_FIELDS)) {
                $cleaned[$key] = $value;
                continue;
            }

            // Se for array, sanitiza recursivamente
            if (is_array($value)) {
                $cleaned[$key] = $this->sanitizeArray($value);
                continue;
            }

            // Se for string, sanitiza
            if (is_string($value)) {
                $cleaned[$key] = $this->sanitizeString($value);
                continue;
            }

            // Outros tipos (int, bool, null) passam direto
            $cleaned[$key] = $value;
        }

        return $cleaned;
    }

    /**
     * Sanitiza uma string individual
     */
    private function sanitizeString(string $value): string
    {
        // Trim espaços em branco
        $value = trim($value);

        // Se string vazia, retorna vazio
        if ($value === '') {
            return '';
        }

        // Remove caracteres de controle invisíveis (exceto \n, \r, \t)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        // Remove scripts inline (case insensitive)
        $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);
        $value = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $value);
        $value = preg_replace('/<object\b[^>]*>(.*?)<\/object>/is', '', $value);
        $value = preg_replace('/<embed\b[^>]*>(.*?)<\/embed>/is', '', $value);

        // Remove atributos perigosos (onclick, onerror, etc)
        foreach (self::DANGEROUS_ATTRIBUTES as $attr) {
            $value = preg_replace('/' . preg_quote($attr, '/') . '\s*=\s*["\'][^"\']*["\']/i', '', $value);
        }

        // Remove tags HTML perigosas (mantém apenas tags permitidas)
        $value = strip_tags($value, self::ALLOWED_TAGS);

        // Escape de caracteres especiais em atributos HTML restantes
        // Converte " para &quot;, ' para &#039;, etc
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        // Normaliza espaços múltiplos
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }

    /**
     * Detecta tentativa de XSS para logging
     */
    private function detectXSSAttempt(string $original, string $sanitized): bool
    {
        // Se houve mudança significativa, pode ser tentativa de XSS
        $lengthDiff = strlen($original) - strlen($sanitized);

        // Se removeu mais de 10 caracteres ou contém padrões suspeitos
        if ($lengthDiff > 10 || $this->containsSuspiciousPatterns($original)) {
            \Log::warning('Potential XSS attempt detected', [
                'original_length' => strlen($original),
                'sanitized_length' => strlen($sanitized),
                'removed_chars' => $lengthDiff,
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
                'path' => request()->path(),
                'original_sample' => substr($original, 0, 100),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Verifica padrões suspeitos de XSS
     */
    private function containsSuspiciousPatterns(string $value): bool
    {
        $patterns = [
            '/<script/i',
            '/javascript:/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/<iframe/i',
            '/eval\s*\(/i',
            '/alert\s*\(/i',
            '/document\.cookie/i',
            '/window\.location/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}
