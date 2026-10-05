<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

final class PaeAnexoJ
{
    public const ROTULOS = [
        'pasta_vermelha' => 'PAE impresso em pasta fichário vermelha, formato A4',
        'assinaturas_originais' => 'Fichas de assinaturas originais preenchidas',
        'pae_fisico_digital' => 'PAE entregue em formato físico e digital',
        'treinamentos_internos' => 'Relatório da realização de treinamentos internos',
        'protocolo_compdecs' => 'PAE protocolado nas COMPDECs dos municípios da ZAS e ZSS',
        'mapas_fisicos_digitais' => 'Mapas impressos e digitais nos padrões exigidos',
        'anexo_b' => 'Anexo B entregue (PAE)',
        'anexo_c' => 'Anexo C entregue (relatório de exercício simulado)',
        'anexo_d' => 'Anexo D entregue (plano de abastecimento de água potável)',
    ];

    public const CHAVES = [
        'pasta_vermelha',
        'assinaturas_originais',
        'pae_fisico_digital',
        'treinamentos_internos',
        'protocolo_compdecs',
        'mapas_fisicos_digitais',
        'anexo_b',
        'anexo_c',
        'anexo_d',
    ];
}
