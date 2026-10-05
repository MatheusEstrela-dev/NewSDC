<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Enums;

/** Diferenca entre o cadastro local e a conta do AD apontada pela sincronizacao. */
enum TipoDivergenciaAd: string
{
    case CADASTRO_SEM_CONTA = 'cadastro_sem_conta';
    case CONTA_FORA_DO_ESCOPO = 'conta_fora_do_escopo';
    case LOGIN_DIVERGENTE = 'login_divergente';
    case ATIVO_LOCAL_DESABILITADA_AD = 'ativo_local_desabilitada_ad';
    case INATIVO_LOCAL_HABILITADA_AD = 'inativo_local_habilitada_ad';
    case LOGIN_AMBIGUO = 'login_ambiguo';
    case CONTA_SEM_CADASTRO = 'conta_sem_cadastro';

    public function label(): string
    {
        return match ($this) {
            self::CADASTRO_SEM_CONTA => 'Cadastro sem conta no AD',
            self::CONTA_FORA_DO_ESCOPO => 'Conta fora da unidade gerenciada',
            self::LOGIN_DIVERGENTE => 'Login diferente no AD',
            self::ATIVO_LOCAL_DESABILITADA_AD => 'Ativo no SDC, desabilitada no AD',
            self::INATIVO_LOCAL_HABILITADA_AD => 'Inativo no SDC, habilitada no AD',
            self::LOGIN_AMBIGUO => 'Login com mais de uma conta',
            self::CONTA_SEM_CADASTRO => 'Conta do AD sem cadastro',
        };
    }
}
