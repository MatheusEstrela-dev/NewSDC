<?php

declare(strict_types=1);

use App\Modules\Integracao\Aplicadores\Estoque\CriarProdutoAplicador;
use App\Modules\Integracao\Aplicadores\Estoque\RegistrarMovimentacaoAplicador;
use App\Modules\Integracao\Aplicadores\Pae\AtualizarEmpreendimentoAplicador;
use App\Modules\Integracao\Aplicadores\Pae\RegistrarDevolutivaAplicador;
use App\Modules\Integracao\Aplicadores\Rat\AtualizarOcorrenciaAplicador;
use App\Modules\Integracao\Aplicadores\Rat\AtualizarRatNovoAplicador;
use App\Modules\Integracao\Aplicadores\Rat\FinalizarOcorrenciaAplicador;

/*
|--------------------------------------------------------------------------
| Caixa de entrada assincrona da API externa (sdc_powerbi, schema entrada)
|--------------------------------------------------------------------------
|
| So tem efeito com DB_POWERBI_CONNECTION definida (ConexaoPowerBi::ativa()).
| A fonte de uma operacao e o prefixo antes do primeiro ponto.
*/
return [
    // operacao => aplicador (App\Modules\Integracao\Contracts\AplicadorDeEntrada).
    'operacoes' => [
        'estoque.movimentacoes.registrar' => RegistrarMovimentacaoAplicador::class,
        'estoque.produtos.criar'          => CriarProdutoAplicador::class,
        'pae.devolutiva.registrar'        => RegistrarDevolutivaAplicador::class,
        'pae.empreendimentos.atualizar'   => AtualizarEmpreendimentoAplicador::class,
        'rat.novo.atualizar'              => AtualizarRatNovoAplicador::class,
        'rat.ocorrencias.atualizar'       => AtualizarOcorrenciaAplicador::class,
        'rat.ocorrencias.finalizar'       => FinalizarOcorrenciaAplicador::class,
    ],

    // Operacoes cuja repeticao duplica o efeito (movimentacao soma/debita de
    // novo; PUT de ocorrencia recria itens sem id). No modo assincrono exigem
    // Idempotency-Key, senao 428: um retry do cliente nao pode reaplicar.
    'exige_chave_idempotente' => [
        'estoque.movimentacoes.registrar',
        'rat.ocorrencias.atualizar',
    ],

    // Adesao por fonte: sincrono (contrato atual) | adesao (desvia para a
    // caixa quando o cliente pede: Prefer: respond-async ou ability abaixo).
    'modo' => [
        'estoque' => env('INTEGRACAO_MODO_ESTOQUE', 'sincrono'),
        'pae'     => env('INTEGRACAO_MODO_PAE', 'sincrono'),
        'rat'     => env('INTEGRACAO_MODO_RAT', 'sincrono'),
    ],

    // Ability de token que liga o assincrono sem o header Prefer. Comparada
    // literalmente: o curinga '*' NAO conta, senao todo token antigo viraria assincrono.
    'ability_assincrona' => 'integracao:assincrono',

    'fila' => [
        'conexao' => env('INTEGRACAO_FILA_CONEXAO', 'redis-integracao'),
        'nome'    => 'integracao',
    ],

    // Trava da mensagem em processamento. Acima do timeout do job (110s) e
    // do --timeout do consumidor (120s); expirada, o reenfileirador retoma.
    'lease_segundos' => (int) env('INTEGRACAO_LEASE_SEGUNDOS', 150),

    // Falhas transitorias antes de ir para entrada.mortas.
    'max_tentativas' => (int) env('INTEGRACAO_MAX_TENTATIVAS', 6),

    // Espera (s) antes da tentativa N+1; repete o ultimo valor.
    'backoff' => [30, 120, 600, 1800, 3600],

    // Recebidas ha mais que isto sem job (Redis fora, deploy) sao redespachadas.
    'carencia_reenfileirar_segundos' => 30,

    'lote_reenfileirar' => 500,

    'chave_idempotente_ttl_horas' => (int) env('INTEGRACAO_CHAVE_TTL_HORAS', 24),

    // Particoes mensais de entrada.mensagens mais antigas que isto sao removidas.
    'retencao_meses' => (int) env('INTEGRACAO_RETENCAO_MESES', 3),

    'particoes_a_frente' => 2,

    // Unicos cabecalhos gravados na mensagem. Authorization e Cookie nunca
    // entram, mesmo que alguem os inclua aqui (CaixaDeEntrada os descarta).
    'cabecalhos_permitidos' => ['Prefer', 'Idempotency-Key', 'User-Agent', 'X-Request-Id'],
];
