<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Enums;

/**
 * Unico vestigio de uma falha do diretorio que o SDC persiste: o texto do
 * servidor LDAP nunca sai do adaptador, so este codigo (spec 9.1).
 */
enum CodigoErroDiretorio: string
{
    case INDISPONIVEL = 'indisponivel';
    case TIMEOUT = 'timeout';
    case CREDENCIAL_SERVICO = 'credencial_servico';
    case CERTIFICADO = 'certificado';
    case SEM_PERMISSAO = 'sem_permissao';
    case CONTA_INEXISTENTE = 'conta_inexistente';
    case CONTA_FORA_DO_ESCOPO = 'conta_fora_do_escopo';
    case CONTA_PROTEGIDA = 'conta_protegida';
    case POLITICA_SENHA = 'politica_senha';
    case RECUSADO = 'recusado';
    case CONFIG_AUSENTE = 'config_ausente';
    case ERRO_INTERNO = 'erro_interno';

    /** So falhas de rede/tempo valem nova tentativa do job. */
    public function transitorio(): bool
    {
        return match ($this) {
            self::INDISPONIVEL, self::TIMEOUT => true,
            default => false,
        };
    }

    /**
     * O diretorio nao chegou a responder sobre a conta (rede, tempo, conexao
     * ou ambiente): familia de DiretorioIndisponivel. Os demais codigos sao
     * recusas (DiretorioRecusou).
     */
    public function indicaIndisponibilidade(): bool
    {
        return match ($this) {
            self::INDISPONIVEL, self::TIMEOUT, self::CONFIG_AUSENTE, self::CERTIFICADO, self::CREDENCIAL_SERVICO => true,
            default => false,
        };
    }

    /** Texto de tela para a operacao encerrada com este codigo. */
    public function mensagem(): string
    {
        return match ($this) {
            self::INDISPONIVEL => 'O diretório está indisponível.',
            self::TIMEOUT => 'O diretório demorou a responder.',
            self::CREDENCIAL_SERVICO => 'A conta de serviço do SDC foi recusada pelo AD. Avise a infraestrutura.',
            self::CERTIFICADO => 'Certificado do diretório inválido. Avise a infraestrutura.',
            self::SEM_PERMISSAO => 'O SDC não tem permissão para esta ação nesta conta.',
            self::CONTA_INEXISTENTE => 'Conta não encontrada no AD.',
            self::CONTA_FORA_DO_ESCOPO => 'A conta está fora da unidade organizacional gerenciada pelo SDC.',
            self::CONTA_PROTEGIDA => 'Conta protegida: não pode ser alterada pelo SDC.',
            self::POLITICA_SENHA => 'A senha gerada não atendeu à política do domínio.',
            self::RECUSADO => 'O AD recusou a operação.',
            self::CONFIG_AUSENTE => 'Integração com o AD não configurada neste ambiente.',
            self::ERRO_INTERNO => 'Erro inesperado ao executar a operação.',
        };
    }

    /** Texto de tela enquanto o job ainda vai tentar de novo (so transitorios). */
    public function mensagemEmNovaTentativa(): ?string
    {
        return match ($this) {
            self::INDISPONIVEL => 'O diretório não respondeu. Tentaremos de novo.',
            self::TIMEOUT => 'O diretório demorou a responder.',
            default => null,
        };
    }
}
