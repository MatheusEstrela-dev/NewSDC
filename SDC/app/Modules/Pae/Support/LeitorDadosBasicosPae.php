<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

/**
 * Le, do TEXTO de um PAE, a secao "Dados basicos sobre a barragem, ZAS e ZSS"
 * (Anexo B, item 2 da Resolucao GMG 83/2024) e os cabecalhos dos demais anexos.
 *
 * Os documentos seguem o modelo da resolucao: pares "Rotulo: valor" em ordem
 * conhecida. O valor de um rotulo vai ate o proximo rotulo conhecido. Nada e
 * inferido: campo nao encontrado fica fora do resultado, e quem decide gravar
 * e o analista.
 */
final class LeitorDadosBasicosPae
{
    /** Rotulos do modelo, tolerantes a acento e a variacoes de redacao. */
    private const ROTULOS = [
        'empreendedor' => 'Nome do empreendedor',
        'empreendimento' => 'Nome do empreendimento',
        'barragem' => 'Nome da barragem',
        'mina' => 'Nome da mina',
        'metodo_alteamento' => 'M[eé]todo de alteamento',
        'volume_reservatorio' => 'Volume do reservat[oó]rio(?:\s*\(m[³3]\))?',
        'localizacao' => 'Localiza[cç][aã]o',
        'tipo_rejeito' => 'Tipo de rejeito',
        'rejeito_toxico' => 'Rejeito t[oó]xico [aà] sa[uú]de humana',
        'extensao_zas' => 'Extens[aã]o da ZAS(?:\s*\(km\))?',
        'populacao_zas' => 'Popula[cç][aã]o total concernida na ZAS',
        'populacao_dificuldade_locomocao' => 'Popula[cç][aã]o com dificuldade de locomo[cç][aã]o[^:]{0,60}',
        'populacao_zss' => 'Popula[cç][aã]o total concernida na ZSS',
        'municipios_zas' => 'Nome dos mun[ií]c[ií]pios concernidos na ZAS',
        'municipios_zss' => 'Nome dos mun[ií]c[ií]pios concernidos na ZSS',
        'cursos_agua' => 'Nome dos cursos de [aá]gua afetados[^:]{0,60}',
        'edificacoes_sensiveis' => 'N[uú]mero de edifica[cç][oõ]es sens[ií]veis[^:]{0,260}',
        'estruturas_associadas' => 'Estruturas associadas\s*(?:\([^)]{0,160}\))?',
        'municipio' => 'Munic[ií]pio(?:\/UF)?',
        'data_exercicio' => 'Data de realiza[cç][aã]o do exerc[ií]cio',
    ];

    /** Rodape/cabecalho de pagina que interrompe um valor. */
    private const FIM_DE_VALOR = '/\s(?:SE[CÇ][AÃ]O II\b|N[ºo°] MUSA\b|Tabela \d|EDIFICA[CÇ][OÕ]ES\b|ANEXO [A-J] [–-]|Data da elabora[cç][aã]o|OBJETIVO D[AE] APRESENTA[CÇ][AÃ]O|\( ?[xX ]? ?\) Obten[cç][aã]o).*$/su';

    private const TAMANHO_MAXIMO_VALOR = 300;

    /**
     * @return array<string, string> campo => valor, so os encontrados
     */
    public static function ler(string $texto): array
    {
        $texto = trim((string) preg_replace('/\s+/u', ' ', $texto));
        if ($texto === '') {
            return [];
        }

        $campos = self::valoresPorRotulo($texto);

        if (isset($campos['rejeito_toxico'])) {
            $campos['rejeito_toxico'] = self::opcaoMarcada($campos['rejeito_toxico']);
        }

        if (isset($campos['localizacao'])) {
            $campos += self::coordenadas($campos['localizacao']);
            $campos['municipio'] ??= self::primeiroMunicipio($campos['localizacao']);
        }
        if (isset($campos['municipio'])) {
            $campos['municipio'] = self::primeiroMunicipio($campos['municipio']);
        }

        return array_filter($campos, static fn (?string $valor): bool => $valor !== null && $valor !== '');
    }

    /**
     * @return array<string, string>
     */
    private static function valoresPorRotulo(string $texto): array
    {
        $ocorrencias = [];
        foreach (self::ROTULOS as $campo => $rotulo) {
            // Primeira ocorrencia de cada rotulo: e a da ficha, antes de tabelas e anexos.
            if (preg_match('/(?<![\p{L}])' . $rotulo . '\s*:/iu', $texto, $m, PREG_OFFSET_CAPTURE) === 1) {
                $ocorrencias[] = ['campo' => $campo, 'inicio' => $m[0][1], 'fim' => $m[0][1] + strlen($m[0][0])];
            }
        }
        usort($ocorrencias, static fn (array $a, array $b): int => $a['inicio'] <=> $b['inicio']);

        $campos = [];
        foreach ($ocorrencias as $i => $ocorrencia) {
            $limite = $ocorrencias[$i + 1]['inicio'] ?? strlen($texto);
            // Rotulo contido em outro (ex.: "Municipio" dentro de um valor ja lido) nao gera campo.
            if ($ocorrencia['fim'] > $limite) {
                continue;
            }

            $valor = substr($texto, $ocorrencia['fim'], $limite - $ocorrencia['fim']);
            $valor = (string) preg_replace(self::FIM_DE_VALOR, '', ' ' . $valor);
            $valor = trim($valor, " \t\n\r\0\x0B;.");

            if ($valor !== '') {
                $campos[$ocorrencia['campo']] = mb_strimwidth($valor, 0, self::TAMANHO_MAXIMO_VALOR, '...');
            }
        }

        return $campos;
    }

    /** "( ) Sim (X) Nao" -> "Nao". Sem marca clara, devolve null. */
    private static function opcaoMarcada(string $valor): ?string
    {
        return preg_match('/\(\s*[xX]\s*\)\s*(Sim|N[aã]o)/u', $valor, $m) === 1 ? $m[1] : null;
    }

    /**
     * @return array<string, string>
     */
    private static function coordenadas(string $localizacao): array
    {
        if (preg_match('/(-?\d{1,2}[.,]\d{3,})\s*[;,\/]\s*(-?\d{1,3}[.,]\d{3,})/', $localizacao, $m) !== 1) {
            return [];
        }

        return [
            'latitude' => str_replace(',', '.', $m[1]),
            'longitude' => str_replace(',', '.', $m[2]),
        ];
    }

    /** "Itatiaiucu-MG, -20.17; -44.45" ou "Itatiaiucu/MG" -> "Itatiaiucu". */
    private static function primeiroMunicipio(string $valor): ?string
    {
        if (preg_match('/^\s*([\p{L}][\p{L}\'. ]*[\p{L}])\s*(?:[-\/–]\s*[A-Z]{2}\b|,|;|$)/u', $valor, $m) !== 1) {
            return null;
        }

        return trim($m[1]);
    }
}
