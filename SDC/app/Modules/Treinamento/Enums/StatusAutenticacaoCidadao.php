<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

/**
 * Desfecho de uma tentativa de login no guard "cidadao".
 *
 * Substitui o antigo retorno booleano de CidadaoAuthService::attempt(): com
 * bool nao havia como a tela de login diferenciar "credencial invalida" de
 * "e-mail ainda nao confirmado", e quem tinha cadastro pendente recebia
 * "CPF ou senha invalidos" - erro que manda a pessoa procurar o problema no
 * lugar errado.
 *
 * Nenhum destes vaza existencia de conta: CREDENCIAL_INVALIDA cobre tanto CPF
 * inexistente quanto senha errada, e os outros dois so sao alcancados DEPOIS
 * da senha conferir, ou seja, por quem ja sabe que a conta existe.
 */
enum StatusAutenticacaoCidadao: string
{
    case AUTENTICADO = 'autenticado';
    case CREDENCIAL_INVALIDA = 'credencial_invalida';
    case EMAIL_NAO_VERIFICADO = 'email_nao_verificado';
    case CONTA_INATIVA = 'conta_inativa';
}
