<?php

namespace App\Exceptions;

use Exception;

/**
 * Excecao lancada quando o Circuit Breaker esta aberto
 * Indica que o servico externo esta instavel e requisicoes estao sendo bloqueadas
 */
class CircuitBreakerOpenException extends Exception
{
    protected string $service;

    public function __construct(string $service, string $message = null)
    {
        $this->service = $service;
        $message = $message ?? "Circuit breaker is open for service: {$service}";
        parent::__construct($message, 503);
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'circuit_breaker_open',
                'message' => $this->getMessage(),
                'service' => $this->service,
            ], 503);
        }

        return response()->view('errors.503', [
            'message' => $this->getMessage(),
        ], 503);
    }
}
