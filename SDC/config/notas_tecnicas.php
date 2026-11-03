<?php

declare(strict_types=1);

return [
    // Limite do PDF de cada versao, em bytes. Fluxograma escaneado passa facil de 10 MB.
    'upload_max_bytes' => (int) env('NOTAS_TECNICAS_UPLOAD_MAX_BYTES', 30 * 1024 * 1024),

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
