<?php

namespace App\Exceptions;

use App\Services\Logging\ActivityLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        // Não reportar validações (muito comum)
        ValidationException::class,
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
    }

    /**
     * Log detalhado de exceções para sistema crítico 24/7
     */
    protected function logDetailedException(Throwable $e): void
    {
        // Determina severidade baseada no tipo de erro
        $severity = $this->determineSeverity($e);

        ActivityLogger::logCriticalError(
            message: $this->getExceptionMessage($e),
            exception: $e,
            context: [
                'severity' => $severity,
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'user_id' => auth()->id(),
                'user_agent' => request()->userAgent(),
                'input' => request()->except(['password', 'password_confirmation']),
                'session_id' => session()->getId(),
                'previous_url' => url()->previous(),
            ]
        );

        // Log em canal separado se for crítico
        if ($severity === 'critical') {
            \Log::channel('critical')->critical($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determina a severidade do erro
     */
    protected function determineSeverity(Throwable $e): string
    {
        // Erros críticos que podem derrubar o sistema
        if ($e instanceof \Error ||
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

        if (config('app.debug')) {
            return $e->getMessage();
        }

        return 'An error occurred. Please contact support.';
    }
}
