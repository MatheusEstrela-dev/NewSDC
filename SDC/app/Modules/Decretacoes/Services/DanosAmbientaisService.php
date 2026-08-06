<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\Constants\DesastreConstants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Agregacao dos DANOS AMBIENTAIS (categoria do FIDE) por processo x municipio.
 *
 * POR QUE UM SERVICE PROPRIO: os agregadores de danos materiais e prejuizos
 * (ProcessoQueryService, ProcessoExportService, ProcessoExportBIService) filtram
 * `dic.tipo IN (number, currency)` e somam valores. Danos ambientais nao tem
 * valor somavel: cada item tem dois radios (Sim/Nao) e um select de intensidade
 * (faixa da populacao atingida ou da area atingida). Somar nao faz sentido - o
 * que interessa e "quais danos foram marcados e em que intensidade". Este
 * service concentra essa leitura para que totais, modal de detalhes e os tres
 * CSVs mostrem exatamente a mesma informacao.
 *
 * FLUXO: processo ids -> query unica -> [processo][municipio][item] -> consumidores
 */
class DanosAmbientaisService
{
    /**
     * Chave estavel de cada item, resolvida pelo titulo normalizado.
     *
     * A estrutura de danos vive apenas no banco (nao ha seeder no repositorio),
     * entao os ids dos itens variam por ambiente - a resolucao por titulo e mais
     * segura que fixar id. `PALAVRAS_CHAVE` cobre variacoes de redacao.
     *
     * @var array<string, string>
     */
    private const ITENS = [
        'poluicao ou contaminacao da agua'  => 'agua',
        'poluicao ou contaminacao do ar'    => 'ar',
        'poluicao ou contaminacao do solo'  => 'solo',
        'diminuicao ou exaurimento hidrico' => 'hidrico',
        'incendios em parques apas ou apps' => 'incendio',
    ];

    /**
     * Rotulos para interface e cabecalho de CSV.
     *
     * @var array<string, string>
     */
    private const ROTULOS = [
        'agua'     => 'Poluição ou contaminação da água',
        'ar'       => 'Poluição ou contaminação do ar',
        'solo'     => 'Poluição ou contaminação do solo',
        'hidrico'  => 'Diminuição ou exaurimento hídrico',
        'incendio' => "Incêndios em parques, APA's ou APP's",
    ];

    /** Titulo do campo radio que marca a ocorrencia do dano. */
    private const CAMPO_SIM = 'sim';
    private const CAMPO_NAO = 'nao';

    /**
     * @return array<int, string> Chaves na ordem de exibicao
     */
    public static function chaves(): array
    {
        return array_values(self::ITENS);
    }

    /**
     * @return array<string, string> chave => rotulo
     */
    public static function rotulos(): array
    {
        return self::ROTULOS;
    }

    /**
     * Estrutura vazia, usada quando o municipio nao tem nenhum dano ambiental.
     *
     * @return array{marcados: int, itens: array<string, array{resposta: ?string, faixa: ?string}>}
     */
    public static function vazio(): array
    {
        $itens = [];

        foreach (self::chaves() as $chave) {
            $itens[$chave] = ['resposta' => null, 'faixa' => null];
        }

        return ['marcados' => 0, 'itens' => $itens];
    }

    /**
     * Danos ambientais de varios processos numa unica query.
     *
     * @param Collection<int, int>|array<int, int> $processoIds
     * @return array<int, array<int, array{marcados: int, itens: array<string, array{resposta: ?string, faixa: ?string}>}>>
     *         [processo_id][municipio_id] => estrutura agregada
     */
    public function porProcessoMunicipio(Collection|array $processoIds): array
    {
        $ids = $processoIds instanceof Collection ? $processoIds->all() : $processoIds;

        if (empty($ids)) {
            return [];
        }

        // A categoria vem por `di.categoria_id` (definicao do item) e nao por
        // `ecd.categoria_id`: o vinculo de entrada e gravado por categoria, mas
        // a definicao e a fonte confiavel do que o item realmente e.
        $rows = DB::table('dec_entrada_desastres as ed')
            ->join('dec_entrada_categoria_desastres as ecd', 'ed.entrada_categoria_desastre_id', '=', 'ecd.id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_items as di', 'dic.desastre_item_id', '=', 'di.id')
            ->join('dec_desastre_categorias as dc', 'di.categoria_id', '=', 'dc.id')
            ->whereIn('ecd.entrada_processo_id', $ids)
            ->where('dc.titulo', DesastreConstants::CAT_DANOS_AMBIENTAIS)
            ->whereNull('ed.deleted_at')
            ->whereNull('ecd.deleted_at')
            ->select(
                'ecd.entrada_processo_id as processo_id',
                'ed.municipio_id',
                'di.titulo as item_titulo',
                'dic.titulo as campo_titulo',
                'dic.tipo',
                'ed.valor'
            )
            ->get();

        $resultado = [];

        foreach ($rows as $row) {
            $chave = self::chaveDoItem((string) $row->item_titulo);

            if ($chave === null) {
                continue;
            }

            $pid = (int) $row->processo_id;
            $mid = (int) $row->municipio_id;

            if (! isset($resultado[$pid][$mid])) {
                $resultado[$pid][$mid] = self::vazio();
            }

            $this->aplicaCampo($resultado[$pid][$mid]['itens'][$chave], $row);
        }

        // `marcados` depende de todos os campos do municipio: contado no fim.
        foreach ($resultado as $pid => $municipios) {
            foreach ($municipios as $mid => $dados) {
                $resultado[$pid][$mid]['marcados'] = count(
                    array_filter($dados['itens'], fn (array $item) => $item['resposta'] === 'Sim')
                );
            }
        }

        return $resultado;
    }

    /**
     * Resumo somado de varios municipios (bloco "geral" dos totais).
     *
     * @param array<int|string, array{marcados: int, itens: array<string, array{resposta: ?string, faixa: ?string}>}> $porMunicipio
     * @return array{marcados: int, municipios_afetados: int, itens: array<string, int>}
     */
    public function resumoGeral(array $porMunicipio): array
    {
        $itens = array_fill_keys(self::chaves(), 0);
        $marcados = 0;
        $municipiosAfetados = 0;

        foreach ($porMunicipio as $dados) {
            $doMunicipio = 0;

            foreach ($dados['itens'] ?? [] as $chave => $item) {
                if (($item['resposta'] ?? null) === 'Sim' && isset($itens[$chave])) {
                    $itens[$chave]++;
                    $doMunicipio++;
                }
            }

            $marcados += $doMunicipio;

            if ($doMunicipio > 0) {
                $municipiosAfetados++;
            }
        }

        return [
            'marcados'            => $marcados,
            'municipios_afetados' => $municipiosAfetados,
            'itens'               => $itens,
        ];
    }

    /**
     * Colunas planas para os CSVs (uma coluna de resposta e uma de intensidade
     * por item, mais o contador). Sempre devolve as mesmas chaves, para que o
     * cabecalho do CSV nao mude conforme os dados da primeira linha.
     *
     * @param array{marcados: int, itens: array<string, array{resposta: ?string, faixa: ?string}>}|array{} $dados
     * @return array<string, mixed>
     */
    public function colunasExport(array $dados): array
    {
        $dados = $dados ?: self::vazio();
        $colunas = ['danos_ambientais_marcados' => $dados['marcados'] ?? 0];

        foreach (self::chaves() as $chave) {
            $item = $dados['itens'][$chave] ?? ['resposta' => null, 'faixa' => null];

            $colunas["danos_ambientais_{$chave}"]           = $item['resposta'];
            $colunas["danos_ambientais_{$chave}_intensidade"] = $item['faixa'];
        }

        return $colunas;
    }

    /**
     * Nomes das colunas devolvidas por colunasExport(), na mesma ordem. Usado
     * pelos CSVs de cabecalho fixo.
     *
     * @return array<int, string>
     */
    public function colunasExportNomes(): array
    {
        return array_keys($this->colunasExport(self::vazio()));
    }

    /**
     * Aplica um campo (radio Sim, radio Nao ou select de intensidade) ao item.
     *
     * REGRA DO RADIO: o front grava o proprio id do campo como valor, entao
     * "marcado" e simplesmente "valor preenchido". Base legada pode ter Sim e
     * Nao preenchidos ao mesmo tempo (antes da correcao de exclusividade no
     * formulario); nesse caso Sim vence, para nao esconder um dano existente.
     *
     * @param array{resposta: ?string, faixa: ?string} $item
     */
    private function aplicaCampo(array &$item, object $row): void
    {
        $preenchido = $this->preenchido($row->valor);

        if ($row->tipo === 'select') {
            if ($preenchido) {
                $item['faixa'] = trim((string) $row->valor);
            }

            return;
        }

        if ($row->tipo !== 'radio' || ! $preenchido) {
            return;
        }

        $titulo = self::normaliza((string) $row->campo_titulo);

        if ($titulo === self::CAMPO_SIM) {
            $item['resposta'] = 'Sim';

            return;
        }

        // "Nao" nunca sobrescreve um "Sim" ja aplicado.
        if ($titulo === self::CAMPO_NAO && $item['resposta'] === null) {
            $item['resposta'] = 'Não';
        }
    }

    /** Valor de radio/select conta como preenchido quando nao e vazio nem zero. */
    private function preenchido(mixed $valor): bool
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto !== '' && $texto !== '0';
    }

    /**
     * Chave do item a partir do titulo cadastrado no banco.
     */
    private static function chaveDoItem(string $titulo): ?string
    {
        $normalizado = self::normaliza($titulo);

        if (isset(self::ITENS[$normalizado])) {
            return self::ITENS[$normalizado];
        }

        // Fallback por palavra-chave: cobre variacoes de redacao do cadastro.
        // "ar" fica por ultimo e exige palavra isolada, senao casaria com
        // qualquer titulo que contenha as letras "ar" (parques, area...).
        return match (true) {
            str_contains($normalizado, 'agua')     => 'agua',
            str_contains($normalizado, 'solo')     => 'solo',
            str_contains($normalizado, 'hidric')   => 'hidrico',
            str_contains($normalizado, 'incendio') => 'incendio',
            (bool) preg_match('/\bar\b/', $normalizado) => 'ar',
            default => null,
        };
    }

    /**
     * Tabela de acentos usada por normaliza().
     *
     * Substituicao explicita em vez de iconv//TRANSLIT ou intl: o resultado do
     * iconv varia com a libc (no Alpine/musl acentos viram "?") e o intl pode
     * nao estar habilitado - as duas coisas quebrariam a resolucao por titulo.
     *
     * @var array<string, string>
     */
    private const ACENTOS = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];

    /**
     * Minusculas, sem acento e sem pontuacao - a comparacao de titulos nao pode
     * depender de acento nem de apostrofo ("APA's" vs "APAs").
     */
    private static function normaliza(string $texto): string
    {
        $minusculo = mb_strtolower(trim($texto), 'UTF-8');
        $semAcento = strtr($minusculo, self::ACENTOS);
        $somenteAlnum = preg_replace('/[^a-z0-9 ]/', '', $semAcento) ?? '';

        return trim(preg_replace('/\s+/', ' ', $somenteAlnum) ?? '');
    }
}
