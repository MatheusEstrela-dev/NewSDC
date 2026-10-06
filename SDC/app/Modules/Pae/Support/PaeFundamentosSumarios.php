<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

final class PaeFundamentosSumarios
{
    public const ARTIGOS = [118, 119, 120, 121, 122, 123, 124, 128, 129];

    public const ROTULOS = [
        118 => 'Assinaturas obrigatórias do PAE',
        119 => 'Relatórios de exercícios simulados assinados pela COMPDEC',
        120 => 'Protocolo físico e digital na CEDEC dentro do prazo',
        121 => 'Arquivos cartográficos digitais KMZ ou KML',
        122 => 'Relatórios de treinamentos internos',
        123 => 'Relatórios anuais na renovação da licença de operação',
        124 => 'Comprovante de protocolo nas Defesas Civis municipais',
        128 => 'Assinaturas do plano de abastecimento de água',
        129 => 'Plano de abastecimento para todos os municípios da ZAS e ZSS',
    ];
}
