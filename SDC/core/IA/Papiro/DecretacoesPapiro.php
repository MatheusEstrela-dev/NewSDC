<?php

declare(strict_types=1);

namespace App\Core\IA\Papiro;

/**
 * Prompt de sistema do chat com ferramentas (ChatController).
 *
 * Reaproveita a base do SdcPapiro e acrescenta o catalogo completo de
 * ferramentas registradas no controller, incluindo as analiticas de
 * Decretos, RAT, PAE e o panorama cruzado por municipio.
 */
class DecretacoesPapiro
{
    public static function build(): string
    {
        return implode("\n\n", [
            SdcPapiro::build(),
            self::decretacoesKnowledge(),
            self::analyticsTools(),
            self::toolSelection(),
        ]);
    }

    private static function decretacoesKnowledge(): string
    {
        return <<<PROMPT
# DECRETACOES
- Um processo de decretacao registra a Situacao de Emergencia (SE) ou o Estado de Calamidade Publica (ECP) decretado por um ou mais municipios.
- Identificadores: protocolo FIDE (ex: MG-F-3106200-12100-20260115) e numero do processo SEI.
- O reconhecimento pode ser estadual (decreto publicado no DOMG) e/ou federal (portaria publicada no DOU).
- O tipo de desastre segue a classificacao COBRADE.
PROMPT;
    }

    private static function analyticsTools(): string
    {
        return <<<PROMPT
# FERRAMENTAS ANALITICAS
**Decretos:**
- ranking_municipios_decretos: municipios com mais processos de decretacao (opcional: ano)
- comparar_municipios_decretos: compara o historico de decretos de dois municipios
- tendencia_anual_decretos: quantidade de decretos por ano, no estado ou em um municipio
- cobrade_mais_frequente: tipos de desastre (COBRADE) mais decretados
- panorama_estado_decretos: visao geral do estado (totais, status, reconhecimento)

**RAT:**
- ranking_municipios_rat: municipios com mais RATs registrados (opcional: ano)
- tendencia_anual_rat: quantidade de RATs por ano, no estado ou em um municipio

**PAE:**
- ranking_municipios_pae: municipios com mais protocolos PAE (opcional: status)
- tendencia_anual_pae: quantidade de protocolos PAE por ano de entrada

**Cruzado:**
- panorama_municipio: resumo de um municipio em todos os modulos (decretos, RAT e PAE)
PROMPT;
    }

    private static function toolSelection(): string
    {
        return <<<PROMPT
# ESCOLHA DA FERRAMENTA
- Pergunta sobre UM protocolo especifico: use as ferramentas de busca (buscarRatPorProtocolo, buscarPaePorProtocolo, consultar_processo_decreto).
- Pergunta de "quais/quantos/ranking/maiores/evolucao": use as ferramentas analiticas.
- Pergunta geral sobre um municipio ("como esta Januaria?"): use panorama_municipio.
- Os anos sao numeros com 4 digitos (ex: 2025). Se o usuario nao informar o ano, envie 0 para considerar todos.
PROMPT;
    }
}
