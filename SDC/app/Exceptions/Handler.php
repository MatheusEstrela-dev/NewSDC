<?php

namespace App\Exceptions;

use App\Exceptions\CircuitBreakerOpenException;
use App\Services\Logging\ActivityLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        // Capturar absolutamente TUDO: removido ValidationException
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // Log TODAS as exceções não tratadas
        $this->reportable(function (Throwable $e) {
            $this->logDetailedException($e);
        });

        // Log específico para erros HTTP
        $this->reportable(function (HttpException $e) {
            ActivityLogger::logEvent(
                type: 'error',
                event: 'http_error',
                data: [
                    'status_code' => $e->getStatusCode(),
                    'message' => $e->getMessage(),
                    'headers' => $e->getHeaders(),
                ],
                level: 'error'
            );
        });

        // Log para erros de autenticação
        $this->reportable(function (AuthenticationException $e) {
            ActivityLogger::logSecurity(
                event: 'authentication_failed',
                data: [
                    'message' => $e->getMessage(),
                    'guards' => $e->guards(),
                ],
                severity: 'warning'
            );
        });

        // Log para Model Not Found
        $this->reportable(function (ModelNotFoundException $e) {
            ActivityLogger::logEvent(
                type: 'error',
                event: 'model_not_found',
                data: [
                    'model' => $e->getModel(),
                    'ids' => $e->getIds(),
                ],
                level: 'warning'
            );
        });

        // Log para Circuit Breaker Open
        $this->reportable(function (CircuitBreakerOpenException $e) {
            \Log::channel('circuit_breaker')->warning('Circuit breaker exception thrown', [
                'service' => $e->getService(),
                'message' => $e->getMessage(),
                'url' => request()?->fullUrl(),
            ]);
        })->stop();

        // Log especifico para erros de SQL (QueryException)
        $this->reportable(function (QueryException $e) {
            $this->logQueryException($e);
        });
    }

    /**
     * Log detalhado de erros SQL com query e bindings
     */
    protected function logQueryException(QueryException $e): void
    {
        try {
            $sql = $e->getSql();
            $bindings = $e->getBindings();
            $errorInfo = $e->errorInfo ?? [];

            $sanitizedBindings = array_map(function ($binding) {
                if (is_string($binding) && mb_strlen($binding) > 100) {
                    return mb_substr($binding, 0, 100) . '...[truncated]';
                }
                if (is_object($binding)) {
                    return get_class($binding);
                }
                return $binding;
            }, $bindings);

            $context = [
                'sql_query' => mb_substr($sql, 0, 2000),
                'sql_bindings' => $sanitizedBindings,
                'sql_error_code' => $errorInfo[0] ?? null,
                'sql_driver_code' => $errorInfo[1] ?? null,
                'sql_driver_message' => $errorInfo[2] ?? null,
                'connection' => $e->getConnectionName() ?? 'default',
                'severity' => 'critical',
                'url' => request()?->fullUrl(),
                'method' => request()?->method(),
                'ip' => request()?->ip(),
                'user_id' => app()->bound('auth') ? auth()->id() : null,
            ];

            ActivityLogger::logEvent(
                type: 'error',
                event: 'sql_error',
                data: $context,
                level: 'critical'
            );

            \Log::channel('critical')->critical('SQL Error: ' . $e->getMessage(), $context);
            \Log::channel('queries')->error('Query Failed', $context);

        } catch (\Throwable $logError) {
            \Log::channel('daily')->error('Falha ao logar QueryException: ' . $logError->getMessage());
        }
    }

    /**
     * Log detalhado de exceções para sistema crítico 24/7
     */
    protected function logDetailedException(Throwable $e): void
    {
        // Determina severidade baseada no tipo de erro
        $severity = $this->determineSeverity($e);

        try {
            ActivityLogger::logCriticalError(
                message: $this->getExceptionMessage($e),
                exception: $e,
                context: [
                    'severity' => $severity,
                    'url' => request()?->fullUrl(),
                    'method' => request()?->method(),
                    'ip' => request()?->ip(),
                    'user_id' => app()->bound('auth') ? auth()->id() : null,
                    'user_agent' => request()?->userAgent(),
                    'input' => request()?->except(['password', 'password_confirmation']),
                    'session_id' => app()->bound('session') ? session()->getId() : null,
                    'previous_url' => app()->bound('router') ? url()->previous() : null,
                ]
            );
        } catch (\Throwable $logError) {
            // Fallback: Se ActivityLogger falhar, continue a execução
            error_log('ActivityLogger failed: ' . $logError->getMessage());
        }

        // Log em canal separado se for crítico
        if ($severity === 'critical') {
            try {
                \Log::channel('critical')->critical($e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            } catch (\Throwable $logError) {
                // Fallback: Se o log falhar, use error_log como último recurso
                error_log('Log channel failed: ' . $logError->getMessage());
            }
        }
    }

    /**
     * Determina a severidade do erro
     */
    protected function determineSeverity(Throwable $e): string
    {
        // Erros críticos que podem derrubar o sistema
        if (
            $e instanceof \Error ||
            $e instanceof \ParseError ||
            $e instanceof \TypeError ||
            str_contains($e->getMessage(), 'SQLSTATE') ||
            str_contains($e->getMessage(), 'Connection refused')
        ) {
            return 'critical';
        }

        // Erros HTTP 5xx
        if ($e instanceof HttpException && $e->getStatusCode() >= 500) {
            return 'error';
        }

        // Erros HTTP 4xx
        if ($e instanceof HttpException && $e->getStatusCode() >= 400) {
            return 'warning';
        }

        return 'error';
    }

    /**
     * Mensagem amigável da exceção
     */
    protected function getExceptionMessage(Throwable $e): string
    {
        if ($e instanceof HttpException) {
            return "HTTP {$e->getStatusCode()}: {$e->getMessage()}";
        }

        if ($e instanceof ModelNotFoundException) {
            return "Model not found: {$e->getModel()}";
        }

        return get_class($e) . ': ' . $e->getMessage();
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // API retorna JSON estruturado
        if ($request->is('api/*') || $request->wantsJson()) {
            return $this->renderApiException($request, $e);
        }

        // Web (SPA/Inertia): sempre responder com páginas Inertia (sem redirect 302 / sem HTML "solto")
        // Assim o erro renderiza no mesmo layout, ao lado do sidebar.
        if ($e instanceof AuthenticationException) {
            return Inertia::render('Auth/Login', [
                'status' => 'Sua sessão expirou. Faça login novamente.',
                'intended' => $request->fullUrl(),
            ])->toResponse($request)->setStatusCode(401);
        }

        if ($e instanceof AuthorizationException || ($e instanceof HttpException && $e->getStatusCode() === 403)) {
            // FIX: Inertia shared props (auth, acl) are lost during exception handling
            // because the response bypasses the HandleInertiaRequests middleware pipeline.
            // We must explicitly re-share them so the layout renders correctly (sidebar, avatar).
            Inertia::share($this->getInertiaSharedData($request));

            return Inertia::render('Errors/Forbidden', [
                'title' => 'Acesso negado',
                'message' => 'Você não tem permissão para acessar esta página.',
            ])->toResponse($request)->setStatusCode(403);
        }

        if ($e instanceof ModelNotFoundException || ($e instanceof HttpException && $e->getStatusCode() === 404)) {
            // FIX: Same issue - re-share auth/acl for error pages
            Inertia::share($this->getInertiaSharedData($request));

            return Inertia::render('Errors/NotFound', [
                'title' => 'Não encontrado',
                'message' => 'O recurso solicitado não foi encontrado.',
            ])->toResponse($request)->setStatusCode(404);
        }

        return parent::render($request, $e);
    }

    /**
     * Reconstroi os shared props do Inertia para respostas de erro.
     *
     * Quando uma exceção é lançada (ex: 403 do middleware can:), a resposta
     * criada pelo Handler bypassa o pipeline do HandleInertiaRequests,
     * fazendo com que auth.user e acl fiquem ausentes no frontend.
     * Este método garante que o layout (sidebar, avatar) renderize corretamente.
     */
    protected function getInertiaSharedData(Request $request): array
    {
        $user = $request->user();

        return [
            'auth' => [
                'user' => $user ? $this->getInertiaUserData($user) : null,
            ],
            'acl' => [
                'levels' => config('permissions.levels', []),
                'modules' => config('permissions.modules', []),
                'protected_roles' => config('permissions.protected_roles', []),
                'immutable_permissions' => config('permissions.immutable_permissions', []),
                'default_level' => config('permissions.default_level', 99),
            ],
        ];
    }

    /**
     * Dados do usuario para o Inertia (espelha HandleInertiaRequests::getUserData).
     */
    protected function getInertiaUserData($user): array
    {
        $roles = [];
        if (method_exists($user, 'roles')) {
            $roles = $user->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'hierarchy_level' => $role->hierarchy_level,
            ])->values()->toArray();
        }

        $permissions = [];
        if (method_exists($user, 'permissions') && method_exists($user, 'getPermissionsViaRoles')) {
            $rolePermissions = $user->getPermissionsViaRoles()->pluck('name')->values()->toArray();
            $directPermissions = $user->permissions->pluck('name')->values()->toArray();
            $permissions = array_values(array_unique(array_merge($rolePermissions, $directPermissions)));
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'cpf' => $user->cpf ?? null,
            'email' => $user->email ?? null,
            'is_super_admin' => method_exists($user, 'hasRole') ? $user->hasRole('super-admin') : false,
            'roles' => $roles,
            'role_names' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values() : [],
            'permissions' => $permissions,
            'hierarchy_level' => method_exists($user, 'getHierarchyLevel')
                ? $user->getHierarchyLevel()
                : 99,
        ];
    }

    /**
     * Retorna resposta JSON para API
     */
    protected function renderApiException(Request $request, Throwable $e)
    {
        $statusCode = $this->getStatusCode($e);

        return response()->json([
            'error' => true,
            'message' => $this->getErrorMessage($e),
            'code' => $e->getCode(),
            'status' => $statusCode,
            'timestamp' => now()->toIso8601String(),
            'path' => $request->path(),
            ...(config('app.debug') ? [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->toArray(),
            ] : []),
        ], $statusCode);
    }

    /**
     * Obtém status code HTTP
     */
    protected function getStatusCode(Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        if ($e instanceof ModelNotFoundException) {
            return 404;
        }

        if ($e instanceof AuthenticationException) {
            return 401;
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof ValidationException) {
            return 422;
        }

        if ($e instanceof CircuitBreakerOpenException) {
            return 503;
        }

        return 500;
    }

    /**
     * Mensagem de erro para usuário
     */
    protected function getErrorMessage(Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            return 'Validation failed';
        }

        if ($e instanceof ModelNotFoundException) {
            return 'Resource not found';
        }

        if ($e instanceof AuthenticationException) {
            return 'Unauthenticated';
        }

        if ($e instanceof AuthorizationException) {
            return 'Forbidden';
        }

        if ($e instanceof CircuitBreakerOpenException) {
            return 'Service temporarily unavailable. Please try again later.';
        }

        if (config('app.debug')) {
            return $e->getMessage();
        }

        return 'An error occurred. Please contact support.';
    }
}
