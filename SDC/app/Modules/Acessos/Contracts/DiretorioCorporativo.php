<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Contracts;

interface DiretorioCorporativo
{
    public function consultar(string $login): array;

    public function solicitarDesbloqueio(string $login, string $operationId): array;

    public function solicitarReset(string $login, string $operationId): array;
}
