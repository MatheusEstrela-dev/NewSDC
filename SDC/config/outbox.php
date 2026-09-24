<?php

declare(strict_types=1);

return [
    // Agenda o despacho do Transactional Outbox pelo scheduler.
    //
    // O comando outbox:dispatch existe desde o Tdap, mas nenhum ambiente o
    // executava: nao estava no scheduler, no Kernel nem no supervisor. Todo
    // Domain Event persistido ficava em outbox_events para sempre e nenhum
    // listener - do Tdap, do ranking ou de qualquer modulo - chegava a rodar.
    //
    // Default false de proposito: ligar faz os listeners de TODOS os modulos
    // passarem a disparar, inclusive sobre o backlog acumulado. E mudanca de
    // comportamento, entao cada ambiente liga explicitamente.
    //
    // ATENCAO: o processo que despacha precisa enxergar a mesma configuracao do
    // ranking que o app. O listener do ranking so e registrado quando
    // ranking.habilitado e true; se o despachante rodar com o modulo desligado,
    // o evento e marcado como despachado sem que o ranking o receba, e esse
    // fato nao volta mais.
    'agendar_despacho' => (bool) env('OUTBOX_AGENDAR_DESPACHO', false),

    // Eventos por execucao. Com o intervalo de 30s, 100 por vez escoa 12 mil
    // eventos por hora, muito acima do volume de marcos de negocio, sem
    // segurar transacao longa na base operacional.
    'lote' => (int) env('OUTBOX_LOTE', 100),
];
