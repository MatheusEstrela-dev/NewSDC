<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Enums;

/**
 * Acao executada no AD pelo worker. Cada acao sabe o slug que a autoriza e o
 * handler que a executa (classes de Services/Acoes, resolvidas pelo job).
 */
enum AcaoDiretorio: string
{
    case CONSULTAR = 'consultar';
    case DESBLOQUEAR = 'desbloquear';
    case HABILITAR = 'habilitar';
    case DESABILITAR = 'desabilitar';
    case EXIGIR_TROCA_SENHA = 'exigir_troca_senha';
    case REDEFINIR_SENHA = 'redefinir_senha';

    private const NAMESPACE_HANDLERS = 'App\\Modules\\Acessos\\Services\\Acoes\\';

    public function label(): string
    {
        return match ($this) {
            self::CONSULTAR => 'Consultar no AD',
            self::DESBLOQUEAR => 'Desbloquear conta',
            self::HABILITAR => 'Habilitar conta',
            self::DESABILITAR => 'Desabilitar conta',
            self::EXIGIR_TROCA_SENHA => 'Exigir troca de senha',
            self::REDEFINIR_SENHA => 'Redefinir senha',
        };
    }

    public function permissao(): string
    {
        return match ($this) {
            self::CONSULTAR => 'acessos.diretorio.view',
            self::REDEFINIR_SENHA => 'acessos.diretorio.reset',
            default => 'acessos.diretorio.manage',
        };
    }

    public function escreve(): bool
    {
        return $this !== self::CONSULTAR;
    }

    public function exigeMotivo(): bool
    {
        return $this === self::DESABILITAR;
    }

    /** @return class-string */
    public function handler(): string
    {
        return self::NAMESPACE_HANDLERS.match ($this) {
            self::CONSULTAR => 'ConsultarConta',
            self::DESBLOQUEAR => 'DesbloquearConta',
            self::HABILITAR => 'HabilitarConta',
            self::DESABILITAR => 'DesabilitarConta',
            self::EXIGIR_TROCA_SENHA => 'ExigirTrocaSenha',
            self::REDEFINIR_SENHA => 'RedefinirSenhaConta',
        };
    }
}
