<?php

declare(strict_types=1);

return [
    // Limite do PDF de cada versao, em bytes. Fluxograma escaneado passa facil de 10 MB.
    'upload_max_bytes' => (int) env('NOTAS_TECNICAS_UPLOAD_MAX_BYTES', 30 * 1024 * 1024),

    /*
     | Extracao do texto do PDF para a busca no conteudo. Binario do poppler-utils
     | (instalado nas imagens swoole e queue). O job roda na fila `low`; o worker
     | mais restritivo que a consome (homolog, --timeout=120) mata job acima de
     | 120s, entao o processo fica em 90s e o job em 110s.
     */
    'pdftotext' => [
        'binario' => env('NOTAS_TECNICAS_PDFTOTEXT_BIN', 'pdftotext'),
        'timeout' => (int) env('NOTAS_TECNICAS_PDFTOTEXT_TIMEOUT', 90),
    ],

    /*
     | Telas do sistema que uma nota pode apontar como "relacionadas" (nome da
     | rota => rotulo). Rota que nao existir no ambiente some da lista em silencio.
     */
    'telas_relacionadas' => [
        'rat.index' => 'RAT',
        'pae.index' => 'PAE',
        'demandas.index' => 'Demandas',
        'decretacoes.index' => 'Decretações',
        'tdap.dashboard' => 'TDAP',
        'pmda.index' => 'PMDA',
        'plantao.index' => 'Plantão Diário',
        'ajuda-humanitaria.pedidos.index' => 'Ajuda Humanitária',
        'compdec.index' => 'Órgãos',
        'cedec.prefeituras.index' => 'Prefeituras',
        'cisternas.index' => 'Cisternas',
        'estoque.index' => 'Estoque',
        'treinamentos.index' => 'Treinamento',
        'plancon.index' => 'Plano de Contingência',
    ],
];
