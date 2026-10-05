<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Infrastructure;

use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Exceptions\DiretorioIndisponivel;
use App\Modules\Acessos\Exceptions\DiretorioRecusou;
use LdapRecord\LdapRecordException;

/**
 * Traduz a falha do LdapRecord para a excecao de dominio (spec 9.1). O codigo
 * de resultado LDAP e o diagnostico do AD (subcodigo `data XXX`, texto de
 * TLS) sao lidos so em memoria e descartados: a excecao devolvida tem
 * mensagem fixa por codigo e nunca encadeia a original.
 */
final class TradutorErroLdap
{
    /** LDAP_SERVER_DOWN (-1/81), LDAP_CONNECT_ERROR (-11/91), busy (51), unavailable (52). */
    private const INDISPONIVEL = [-1, -11, 51, 52, 81, 91];

    /** LDAP_TIMEOUT (-5/85) e timeLimitExceeded (3). */
    private const TIMEOUT = [-5, 3, 85];

    /** Codigos de conexao em que uma falha de TLS/CA aparece no diagnostico; 13 = confidentialityRequired. */
    private const CONEXAO = [-1, -11, 13, 81, 91];

    /** Recusas do servidor sobre o objeto ou o pedido (atributo, sintaxe, nome, classe, unwillingToPerform). */
    private const RECUSADO = [16, 17, 18, 20, 21, 34, 36, 53, 64, 65, 66, 67, 68, 69, 71];

    private const SEM_CONTATO = "Can't contact LDAP server";

    public function traduzir(#[\SensitiveParameter] LdapRecordException $e): DiretorioIndisponivel|DiretorioRecusou
    {
        $codigo = $this->codigo($e);

        return $codigo->indicaIndisponibilidade() ? new DiretorioIndisponivel($codigo) : new DiretorioRecusou($codigo);
    }

    private function codigo(LdapRecordException $e): CodigoErroDiretorio
    {
        $erro = $e->getDetailedError();
        if ($erro === null) {
            return str_contains($e->getMessage(), self::SEM_CONTATO)
                ? CodigoErroDiretorio::INDISPONIVEL
                : CodigoErroDiretorio::ERRO_INTERNO;
        }

        $resultado = $erro->getErrorCode();
        $diagnostico = (string) $erro->getDiagnosticMessage();

        return match (true) {
            in_array($resultado, self::CONEXAO, true) && $this->falhaDeCertificado($diagnostico) => CodigoErroDiretorio::CERTIFICADO,
            in_array($resultado, self::INDISPONIVEL, true) => CodigoErroDiretorio::INDISPONIVEL,
            in_array($resultado, self::TIMEOUT, true) => CodigoErroDiretorio::TIMEOUT,
            $resultado === 13 => CodigoErroDiretorio::CERTIFICADO,
            $resultado === 49 => CodigoErroDiretorio::CREDENCIAL_SERVICO,
            $resultado === 50 => CodigoErroDiretorio::SEM_PERMISSAO,
            $resultado === 32 => CodigoErroDiretorio::CONTA_INEXISTENTE,
            $resultado === 19, $this->violouPoliticaDeSenha($diagnostico) => CodigoErroDiretorio::POLITICA_SENHA,
            in_array($resultado, self::RECUSADO, true) => CodigoErroDiretorio::RECUSADO,
            default => CodigoErroDiretorio::ERRO_INTERNO,
        };
    }

    private function falhaDeCertificado(string $diagnostico): bool
    {
        return stripos($diagnostico, 'certificate') !== false || stripos($diagnostico, 'TLS') !== false;
    }

    /** Subcodigo 0x52D do AD (ERROR_PASSWORD_RESTRICTION), como `0000052D:` ou `data 52d`. */
    private function violouPoliticaDeSenha(string $diagnostico): bool
    {
        return preg_match('/\b0000052D\b|\bdata 52D\b/i', $diagnostico) === 1;
    }
}
