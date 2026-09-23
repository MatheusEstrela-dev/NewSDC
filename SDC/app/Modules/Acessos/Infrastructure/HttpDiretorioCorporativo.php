<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Infrastructure;

use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class HttpDiretorioCorporativo implements DiretorioCorporativo
{
    public function consultar(string $login): array
    {
        return $this->request('GET', '/accounts/'.rawurlencode($login));
    }

    public function solicitarDesbloqueio(string $login, string $operationId): array
    {
        return $this->request('POST', '/accounts/'.rawurlencode($login).'/unlock', $operationId);
    }

    public function solicitarReset(string $login, string $operationId): array
    {
        return $this->request('POST', '/accounts/'.rawurlencode($login).'/reset', $operationId);
    }

    private function request(string $method, string $path, ?string $operationId = null): array
    {
        $baseUrl = rtrim((string) config('services.corporate_directory.url'), '/');
        $token = (string) config('services.corporate_directory.token');
        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('Diretório corporativo não configurado.');
        }

        $http = Http::withToken($token)->acceptJson()->timeout(10)->connectTimeout(3);
        if ($operationId !== null) {
            $http = $http->withHeaders(['Idempotency-Key' => $operationId]);
        }
        $response = $http->send($method, $baseUrl.$path);
        $response->throw();

        return $response->json() ?? [];
    }
}
