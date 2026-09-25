<?php

declare(strict_types=1);

/*
 * Resgate de pontos por servicos, adesoes e bens.
 * Plano: docs/superpowers/plans/2026-09-25-resgate-pontos-catalogo.md
 *
 * Os valores aqui sao PROPOSTAS ate o normativo da CEDEC (Fase 0 do plano).
 * Nenhum deles libera resgate: a Fase 1 so mostra a carteira.
 */
return [
    // Mesma database do ledger: a reserva le o saldo e grava o movimento na
    // mesma transacao. Nunca a conexao operacional.
    'conexao' => 'ranking',

    // Dias ate um credito confirmado amadurecer e entrar no saldo resgatavel.
    // Cobre a janela de contestacao, estorno e reconciliacao. Proposta do
    // plano; o numero definitivo sai do normativo.
    'carencia_dias' => (int) env('RESGATE_CARENCIA_DIAS', 30),

    // MODO DEMONSTRACAO (so homologacao). Ligado, permite pedir item de
    // demonstracao usando SO pontos de demonstracao; o pedido nasce marcado
    // e os dois saldos nunca se misturam. Em producao e ignorado a forca
    // (ModoDemonstracao), mesmo que a variavel venha ligada por engano.
    'permitir_demonstracao' => (bool) env('RESGATE_PERMITIR_DEMONSTRACAO', false),
];
