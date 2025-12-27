<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de Validação OpenAPI
 *
 * Valida TODAS as requisições contra o contrato OpenAPI (api-docs.json)
 * ANTES de chegar no Controller.
 *
 * Filosofia: "O arquivo openapi.yaml/json é a lei"
 * Se o JSON enviado não bater com o schema, a requisição nem deve chegar no Controller.
 *
 * Baseado no papiro.md - Sistema Robusto de API
 */
class ValidateOpenApiRequest
{
    /**
     * Rotas que não devem ser validadas
     */
    private const SKIP_VALIDATION = [
        'api/documentation',
        'api/health',
        'sanctum/csrf-cookie',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validação para rotas específicas
        if ($this->shouldSkipValidation($request)) {
            return $next($request);
        }

        // Carrega o schema OpenAPI (com cache para performance)
        $openApiSpec = $this->loadOpenApiSpec();

        if (!$openApiSpec) {
            // Se não conseguir carregar o spec, apenas loga e continua
            // (Em produção você pode querer falhar hard)
            Log::warning('OpenAPI spec not found, skipping validation');
            return $next($request);
        }

        // Valida a requisição contra o spec
        $validation = $this->validateRequest($request, $openApiSpec);

        if (!$validation['valid']) {
            return response()->json([
                'error' => 'Schema Validation Failed',
                'message' => 'The request does not match the OpenAPI specification',
                'violations' => $validation['errors'],
                'path' => $request->path(),
                'method' => $request->method(),
            ], 422); // 422 Unprocessable Entity
        }

        return $next($request);
    }

    /**
     * Verifica se deve pular a validação
     */
    private function shouldSkipValidation(Request $request): bool
    {
        $path = $request->path();

        foreach (self::SKIP_VALIDATION as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Carrega o schema OpenAPI (com cache de 1 hora + Cache Stampede Protection)
     *
     * Cache Stampede Protection: Se o cache expirar e 500 requisições chegarem
     * simultaneamente, apenas UMA vai carregar o arquivo. As outras 499 aguardam
     * o lock e depois pegam do cache já gravado.
     */
    private function loadOpenApiSpec(): ?array
    {
        // Primeiro tenta pegar do cache (caminho rápido)
        $cached = Cache::get('openapi_spec');
        if ($cached !== null) {
            return $cached;
        }

        // Cache miss - tenta adquirir lock para carregar
        // Apenas UMA thread vai conseguir o lock
        $lock = Cache::lock('openapi_spec_lock', 10);

        if ($lock->get()) {
            try {
                // Double-check: verifica se outro processo já carregou enquanto esperava
                $cached = Cache::get('openapi_spec');
                if ($cached !== null) {
                    return $cached;
                }

                // Carrega o arquivo
                $spec = $this->loadSpecFromFile();

                // Cacheia por 1 hora
                if ($spec !== null) {
                    Cache::put('openapi_spec', $spec, 3600);
                }

                return $spec;

            } finally {
                $lock->release();
            }
        }

        // Se não conseguiu o lock, aguarda um pouco e tenta pegar do cache
        // (provavelmente outro processo está carregando)
        usleep(100000); // 100ms

        $cached = Cache::get('openapi_spec');
        if ($cached !== null) {
            return $cached;
        }

        // Fallback: carrega direto (não ideal, mas melhor que retornar null)
        Log::warning('OpenAPI spec lock timeout, loading without cache');
        return $this->loadSpecFromFile();
    }

    /**
     * Carrega o spec do arquivo (extraído para reutilização)
     */
    private function loadSpecFromFile(): ?array
    {
        $specPath = storage_path('api-docs/api-docs.json');

        if (!file_exists($specPath)) {
            Log::warning('OpenAPI spec file not found', ['path' => $specPath]);
            return null;
        }

        $content = file_get_contents($specPath);

        if ($content === false) {
            Log::error('Failed to read OpenAPI spec file', ['path' => $specPath]);
            return null;
        }

        $decoded = json_decode($content, true);

        // Verifica se houve erro no decode
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to decode OpenAPI spec JSON', [
                'error' => json_last_error_msg(),
                'path' => $specPath,
                'json_error_code' => json_last_error(),
            ]);
            return null;
        }

        return $decoded;
    }

    /**
     * Valida a requisição contra o schema OpenAPI
     */
    private function validateRequest(Request $request, array $openApiSpec): array
    {
        $method = strtolower($request->method());

        // Pega o path completo da requisição, sem hardcoded prefix
        // Suporta tanto /api/users quanto /v1/api/users
        $requestPath = '/' . ltrim($request->path(), '/');

        // Encontra o path no spec (suporta path parameters como /api/users/{id})
        $pathSpec = $this->findPathInSpec($requestPath, $openApiSpec['paths'] ?? []);

        if (!$pathSpec) {
            // Path não encontrado no spec - pode ser uma rota não documentada
            // Em desenvolvimento, avisa. Em produção, você decide se bloqueia ou não.
            if (config('app.env') === 'local') {
                Log::warning('OpenAPI path not found', [
                    'path' => $requestPath,
                    'method' => $method,
                ]);

                return [
                    'valid' => false,
                    'errors' => ["Path '{$requestPath}' not found in OpenAPI specification"]
                ];
            }

            // Em produção, deixa passar (ou você pode mudar para bloquear)
            return ['valid' => true, 'errors' => []];
        }

        // Pega o spec do método HTTP
        $methodSpec = $pathSpec['spec'][$method] ?? null;

        if (!$methodSpec) {
            Log::warning('OpenAPI method not allowed', [
                'path' => $requestPath,
                'method' => $method,
            ]);

            return [
                'valid' => false,
                'errors' => ["Method '{$method}' not allowed for path '{$requestPath}'"]
            ];
        }

        // Valida o body da requisição (se o método suporta body)
        if (in_array($method, ['post', 'put', 'patch', 'delete'])) {
            $bodyValidation = $this->validateRequestBody(
                $request->all(),
                $methodSpec['requestBody'] ?? null
            );

            if (!$bodyValidation['valid']) {
                return $bodyValidation;
            }
        }

        // Valida query parameters
        $queryValidation = $this->validateQueryParameters(
            $request->query(),
            $methodSpec['parameters'] ?? []
        );

        if (!$queryValidation['valid']) {
            return $queryValidation;
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * Encontra o path no spec (suporta path parameters)
     */
    private function findPathInSpec(string $requestPath, array $paths): ?array
    {
        // Tenta match exato primeiro
        if (isset($paths[$requestPath])) {
            return ['spec' => $paths[$requestPath], 'params' => []];
        }

        // Tenta match com path parameters (ex: /api/users/{id})
        foreach ($paths as $specPath => $spec) {
            if ($this->matchPathPattern($requestPath, $specPath)) {
                return ['spec' => $spec, 'params' => []];
            }
        }

        return null;
    }

    /**
     * Verifica se o path da requisição bate com o pattern do spec
     */
    private function matchPathPattern(string $requestPath, string $specPath): bool
    {
        // Converte {id} para regex
        $pattern = preg_replace('/\{[^}]+\}/', '[^/]+', $specPath);
        $pattern = '#^' . $pattern . '$#';

        return preg_match($pattern, $requestPath) === 1;
    }

    /**
     * Valida o body da requisição
     */
    private function validateRequestBody(array $data, ?array $bodySpec): array
    {
        if (!$bodySpec) {
            // Se não tem spec de body, qualquer body é válido
            return ['valid' => true, 'errors' => []];
        }

        $content = $bodySpec['content']['application/json'] ?? null;

        if (!$content) {
            return ['valid' => true, 'errors' => []];
        }

        $schema = $content['schema'] ?? null;

        if (!$schema) {
            return ['valid' => true, 'errors' => []];
        }

        // Valida o schema
        return $this->validateAgainstSchema($data, $schema, 'body');
    }

    /**
     * Valida query parameters
     */
    private function validateQueryParameters(array $query, array $parameters): array
    {
        $errors = [];

        foreach ($parameters as $param) {
            if ($param['in'] !== 'query') {
                continue;
            }

            $name = $param['name'];
            $required = $param['required'] ?? false;
            $value = $query[$name] ?? null;

            // Verifica se é required
            if ($required && $value === null) {
                $errors[] = "Required query parameter '{$name}' is missing";
                continue;
            }

            // Valida o tipo se o valor existir
            if ($value !== null) {
                $schema = $param['schema'] ?? [];
                $typeValidation = $this->validateType($value, $schema['type'] ?? 'string', $name);

                if (!$typeValidation['valid']) {
                    $errors = array_merge($errors, $typeValidation['errors']);
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Valida dados contra um schema JSON Schema
     */
    private function validateAgainstSchema(array $data, array $schema, string $path = ''): array
    {
        $errors = [];

        // Valida required fields
        $required = $schema['required'] ?? [];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $errors[] = "Required field '{$path}.{$field}' is missing";
            }
        }

        // Valida properties
        $properties = $schema['properties'] ?? [];
        foreach ($properties as $field => $fieldSchema) {
            $value = $data[$field] ?? null;

            // Se o campo existe, valida o tipo
            if ($value !== null) {
                $type = $fieldSchema['type'] ?? 'string';
                $typeValidation = $this->validateType($value, $type, "{$path}.{$field}");

                if (!$typeValidation['valid']) {
                    $errors = array_merge($errors, $typeValidation['errors']);
                }

                // Valida enum se existir
                if (isset($fieldSchema['enum']) && !in_array($value, $fieldSchema['enum'])) {
                    $errors[] = "Field '{$path}.{$field}' must be one of: " . implode(', ', $fieldSchema['enum']);
                }

                // Valida min/max para números
                if (in_array($type, ['integer', 'number'])) {
                    if (isset($fieldSchema['minimum']) && $value < $fieldSchema['minimum']) {
                        $errors[] = "Field '{$path}.{$field}' must be >= {$fieldSchema['minimum']}";
                    }
                    if (isset($fieldSchema['maximum']) && $value > $fieldSchema['maximum']) {
                        $errors[] = "Field '{$path}.{$field}' must be <= {$fieldSchema['maximum']}";
                    }
                }

                // Valida minLength/maxLength para strings
                if ($type === 'string') {
                    $length = strlen($value);
                    if (isset($fieldSchema['minLength']) && $length < $fieldSchema['minLength']) {
                        $errors[] = "Field '{$path}.{$field}' must have at least {$fieldSchema['minLength']} characters";
                    }
                    if (isset($fieldSchema['maxLength']) && $length > $fieldSchema['maxLength']) {
                        $errors[] = "Field '{$path}.{$field}' must have at most {$fieldSchema['maxLength']} characters";
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Valida o tipo de um valor
     */
    private function validateType($value, string $expectedType, string $fieldName): array
    {
        $actualType = gettype($value);

        $valid = match ($expectedType) {
            'string' => is_string($value),
            'integer' => is_int($value) || (is_string($value) && ctype_digit($value)),
            'number' => is_numeric($value),
            'boolean' => is_bool($value) || in_array($value, ['true', 'false', '0', '1', 0, 1], true),
            'array' => is_array($value),
            'object' => is_array($value) && !array_is_list($value),
            default => true,
        };

        if (!$valid) {
            return [
                'valid' => false,
                'errors' => ["Field '{$fieldName}' must be of type '{$expectedType}', got '{$actualType}'"]
            ];
        }

        return ['valid' => true, 'errors' => []];
    }
}
