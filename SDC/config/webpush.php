<?php

declare(strict_types=1);

/**
 * Web Push (notificacao do sistema operacional).
 *
 * As chaves VAPID identificam ESTE servidor para os push services (FCM, Mozilla,
 * WNS). Sao geradas uma vez, com `php artisan notificacoes:vapid`, e guardadas no
 * .env. Trocar o par invalida todas as inscricoes existentes: os navegadores
 * ficam inscritos com a chave publica antiga e o envio passa a ser recusado.
 *
 * Sem chave configurada o canal fica indisponivel para todo mundo, e
 * CanaisDisponiveis desabilita o checkbox na tela com esse motivo.
 */
return [

    'vapid' => [
        // Identifica o responsavel pelo envio para o push service, que usa isso
        // para contato em caso de abuso. Precisa ser mailto: ou uma URL.
        'subject' => env('VAPID_SUBJECT', 'mailto:sdc@defesacivil.mg.gov.br'),

        'public_key' => env('VAPID_PUBLIC_KEY', ''),
        'private_key' => env('VAPID_PRIVATE_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Envio
    |--------------------------------------------------------------------------
    |
    | TTL e por quanto tempo o push service segura a mensagem se o dispositivo
    | estiver offline. Notificacao de defesa civil envelhece rapido: 12h e o teto
    | util -- passou disso, o usuario ja soube por outro canal.
    |
    */
    'ttl_segundos' => (int) env('WEBPUSH_TTL', 12 * 3600),

    // 'normal' deixa o dispositivo agrupar entregas para poupar bateria;
    // 'high' acorda na hora. Alerta urgente merece o segundo.
    'urgencia_padrao' => 'normal',
    'urgencia_urgente' => 'high',

    /*
    |--------------------------------------------------------------------------
    | Inscricoes
    |--------------------------------------------------------------------------
    |
    | Um usuario tem uma inscricao por navegador/dispositivo. O limite existe
    | porque o app.js desregistra o service worker na recuperacao de build velho
    | e no 419: cada volta pode gerar um endpoint novo, e sem teto a tabela
    | cresceria sozinha. Ao estourar, a inscricao mais antiga sai.
    |
    */
    'max_por_usuario' => (int) env('WEBPUSH_MAX_DISPOSITIVOS', 10),

];
