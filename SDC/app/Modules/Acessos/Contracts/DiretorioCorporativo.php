<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Contracts;

use App\Modules\Acessos\DTOs\ContaDiretorio;
use App\Modules\Acessos\DTOs\ReferenciaConta;
use App\Modules\Acessos\DTOs\ResultadoOperacao;

/**
 * Porta do Active Directory (spec 4.1). Contrato de erro de todo adaptador:
 * DiretorioIndisponivel quando o diretorio nao respondeu (o job repete se o
 * codigo for transitorio), DiretorioRecusou quando respondeu e recusou (o job
 * encerra). Nenhum adaptador encadeia a excecao original nem expoe o texto do
 * servidor LDAP.
 */
interface DiretorioCorporativo
{
    /** Conta pelo sAMAccountName dentro da SearchBase; null se nao existe. */
    public function consultar(string $login): ?ContaDiretorio;

    /** Conta pelo objectGUID (forma canonica, 36 chars) em qualquer ponto do dominio; null se nao existe. */
    public function buscarPorGuid(string $objectGuid): ?ContaDiretorio;

    /**
     * Todas as contas de usuario da SearchBase, paginadas no servidor.
     *
     * @return iterable<ContaDiretorio>
     */
    public function listarContas(): iterable;

    public function desbloquear(ReferenciaConta $conta, string $operationId): ResultadoOperacao;

    public function habilitar(ReferenciaConta $conta, string $operationId): ResultadoOperacao;

    public function desabilitar(ReferenciaConta $conta, string $operationId): ResultadoOperacao;

    public function exigirTrocaSenha(ReferenciaConta $conta, string $operationId): ResultadoOperacao;

    /** Grava unicodePwd e, se $exigirTroca, pwdLastSet=0 num unico modify. */
    public function redefinirSenha(
        ReferenciaConta $conta,
        #[\SensitiveParameter] string $senha,
        bool $exigirTroca,
        string $operationId,
    ): ResultadoOperacao;
}
