<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support\Simulado;

/**
 * Item 8.1 do Anexo C da Resolucao GMG 83/2024 (Arts. 98 a 101): unica dona do
 * catalogo dos 10 criterios e da regra de validacao. Os criterios 1 a 8 decidem
 * a validacao; 9 e 10 (Art. 100) sao informativos. Indicio e apoio ao analista,
 * nunca decisao: quem marca "atende" e a CEDEC.
 */
final class SimuladoAnexoC
{
    /** Mesma tolerancia da fase E para comparar tempos em ponto flutuante. */
    public const TOLERANCIA = 1e-6;

    public const CATEGORIAS = ['sem_dificuldade', 'com_dificuldade', 'ensino', 'hospitalares_prisionais', 'aglomeracao'];

    /** Criterio do item 8.1 => categoria da secao 7 que alimenta seus indicios de tempo. */
    private const CATEGORIA_DO_CRITERIO = [
        5 => 'sem_dificuldade',
        6 => 'com_dificuldade',
        7 => 'hospitalares_prisionais',
        8 => 'aglomeracao',
    ];

    private const PONTOS_DE_ENCONTRO = 4;
    private const ALARME = 2;
    private const ULTIMO_REPROVAVEL = 8;

    private const ITENS = [
        1 => ['Avaliação das placas e sinalização de risco', 'Todas as placas estarem instaladas conforme previsto no PAE e nesta Resolução.'],
        2 => ['Efetividade do sistema de alarme', 'Indicação do morador residente na ZAS que informou não ser audível o sistema de alarme (nome, localização).'],
        3 => ['Avaliação das estratégias de comunicação de risco', 'Realização de todas as ações listadas no item comunicação de risco desta Resolução que regulamenta a elaboração do PAE.'],
        4 => ['Avaliação dos pontos de encontro', 'Atendimento aos critérios estabelecidos nesta Resolução.'],
        5 => ['Avaliação do tempo de saída das pessoas sem dificuldade de locomoção das áreas de risco', 'Tempo de saída das pessoas das áreas sujeitas à inundação.'],
        6 => ['Avaliação do tempo gasto para retirada das pessoas com dificuldade de locomoção', 'Tempo estimado para a retirada das pessoas com dificuldade de locomoção das áreas de risco.'],
        7 => ['Avaliação do tempo gasto para a retirada das pessoas das unidades prisionais', 'Tempo estimado para a retirada de todas as pessoas das unidades prisionais.'],
        8 => ['Avaliação do tempo gasto para a evacuação dos locais com grande aglomeração de pessoas', 'Tempo gasto para a evacuação de todas as pessoas dos locais com grande aglomeração e chegada em local seguro.'],
        9 => ['Mensuração do número de pessoas participantes do exercício simulado', 'Percentual de participação de pessoas cadastradas no PAE nos exercícios simulados.'],
        10 => ['Avaliar a mobilização da comunidade na participação de exercícios simulados', 'Percentual de participação de pessoas em relação ao simulado realizado em anos anteriores.'],
    ];

    /** @return list<array{numero: int, indice: string, criterio: string, reprovavel: bool}> */
    public static function catalogo(): array
    {
        $catalogo = [];
        foreach (self::ITENS as $numero => [$indice, $criterio]) {
            $catalogo[] = [
                'numero' => $numero,
                'indice' => $indice,
                'criterio' => $criterio,
                'reprovavel' => $numero <= self::ULTIMO_REPROVAVEL,
            ];
        }

        return $catalogo;
    }

    /** @return list<int> */
    public static function numeros(): array
    {
        return array_keys(self::ITENS);
    }

    /** @return list<int> */
    public static function numerosReprovaveis(): array
    {
        return range(1, self::ULTIMO_REPROVAVEL);
    }

    /** Art. 101: validado somente se os 8 criterios reprovaveis estiverem marcados "atende" (booleano verdadeiro). */
    public static function validado(array $criterios): bool
    {
        foreach (self::numerosReprovaveis() as $numero) {
            if (($criterios[$numero]['atende'] ?? null) !== true) {
                return false;
            }
        }

        return true;
    }

    /** Justificativa e obrigatoria no "nao atende" dos criterios reprovaveis; os informativos aceitam nulo. */
    public static function exigeJustificativa(int $numero, mixed $atende): bool
    {
        if ($numero > self::ULTIMO_REPROVAVEL || $atende === null) {
            return false;
        }

        return filter_var($atende, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false;
    }

    /** @return list<int> numeros dos criterios que exigem justificativa e nao a tem */
    public static function justificativasFaltantes(array $criterios): array
    {
        $faltantes = [];
        foreach (self::numeros() as $numero) {
            $criterio = $criterios[$numero] ?? [];
            if (self::exigeJustificativa($numero, $criterio['atende'] ?? null) && self::emBranco($criterio['justificativa'] ?? null)) {
                $faltantes[] = $numero;
            }
        }

        return $faltantes;
    }

    /**
     * @return array<int, list<array{codigo: string, categoria: ?string, linha: ?int, nome: ?string}>>
     */
    public static function indicios(array $tempos, array $alarme): array
    {
        $indicios = array_fill_keys(self::numeros(), []);

        foreach (self::CATEGORIA_DO_CRITERIO as $numero => $categoria) {
            foreach ($tempos[$categoria] ?? [] as $linha => $dados) {
                foreach (self::sinais($dados) as $codigo) {
                    $indicio = [
                        'codigo' => $codigo,
                        'categoria' => $categoria,
                        'linha' => (int) $linha,
                        'nome' => $dados['nome'] ?? null,
                    ];
                    $indicios[$numero][] = $indicio;
                    if ($codigo === 'ponto_invalido') {
                        $indicios[self::PONTOS_DE_ENCONTRO][] = $indicio;
                    }
                }
            }
        }

        if (($alarme['audivel_todos'] ?? null) === false
            && (self::emBranco($alarme['morador_nome'] ?? null) || self::emBranco($alarme['morador_localizacao'] ?? null))) {
            $indicios[self::ALARME][] = ['codigo' => 'alarme_sem_morador', 'categoria' => null, 'linha' => null, 'nome' => null];
        }

        return $indicios;
    }

    /** @return list<string> */
    private static function sinais(array $linha): array
    {
        $sinais = [];
        $saida = $linha['saida_segundos'] ?? null;
        $chegada = $linha['chegada_onda_segundos'] ?? null;
        if ($saida !== null && $chegada !== null && $saida >= $chegada - self::TOLERANCIA) {
            $sinais[] = 'saida_maior_igual_onda';
        }
        if (($linha['houve_problemas'] ?? false) === true) {
            $sinais[] = 'houve_problemas';
        }
        if (($linha['ponto_valido'] ?? true) === false) {
            $sinais[] = 'ponto_invalido';
        }

        return $sinais;
    }

    /** `trim()` e a regra `required` nao removem o espaco nao separavel (U+00A0); `\S` com `/u` remove. */
    private static function emBranco(?string $texto): bool
    {
        return $texto === null || preg_match('/\S/u', $texto) !== 1;
    }
}
