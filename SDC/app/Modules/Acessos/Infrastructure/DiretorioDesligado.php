<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Infrastructure;

use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use App\Modules\Acessos\DTOs\ContaDiretorio;
use App\Modules\Acessos\DTOs\ReferenciaConta;
use App\Modules\Acessos\DTOs\ResultadoOperacao;
use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Exceptions\DiretorioIndisponivel;

/**
 * Padrao do web e de qualquer ambiente sem configuracao: nada fala com o AD,
 * toda chamada falha com `config_ausente` (definitiva, o job nao repete).
 */
final class DiretorioDesligado implements DiretorioCorporativo
{
    public function consultar(string $login): ?ContaDiretorio
    {
        throw $this->desligado();
    }

    public function buscarPorGuid(string $objectGuid): ?ContaDiretorio
    {
        throw $this->desligado();
    }

    public function listarContas(): iterable
    {
        throw $this->desligado();
    }

    public function desbloquear(ReferenciaConta $conta, string $operationId): ResultadoOperacao
    {
        throw $this->desligado();
    }

    public function habilitar(ReferenciaConta $conta, string $operationId): ResultadoOperacao
    {
        throw $this->desligado();
    }

    public function desabilitar(ReferenciaConta $conta, string $operationId): ResultadoOperacao
    {
        throw $this->desligado();
    }

    public function exigirTrocaSenha(ReferenciaConta $conta, string $operationId): ResultadoOperacao
    {
        throw $this->desligado();
    }

    public function redefinirSenha(
        ReferenciaConta $conta,
        #[\SensitiveParameter] string $senha,
        bool $exigirTroca,
        string $operationId,
    ): ResultadoOperacao {
        throw $this->desligado();
    }

    private function desligado(): DiretorioIndisponivel
    {
        return new DiretorioIndisponivel(CodigoErroDiretorio::CONFIG_AUSENTE);
    }
}
