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
            return Inertia::render('Errors/Forbidden', [
                'title' => 'Acesso negado',
                'message' => 'Você não tem permissão para acessar esta página.',
            ])->toResponse($request)->setStatusCode(403);
        }

        if ($e instanceof ModelNotFoundException || ($e instanceof HttpException && $e->getStatusCode() === 404)) {
            return Inertia::render('Errors/NotFound', [
                'title' => 'Não encontrado',
                'message' => 'O recurso solicitado não foi encontrado.',
            ])->toResponse($request)->setStatusCode(404);
        }

        return parent::render($request, $e);
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
