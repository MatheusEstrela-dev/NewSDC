<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Support\NormalizaEntrada;

/**
 * Traduz o checklist do legado para linhas de cisterna_itens_conferidos.
 *
 * As tres tabelas de relatorio nomeavam os mesmos 13 itens de formas
 * diferentes:
 *
 *  - fornecedor: {item}_opcao com 'sim'/'nao', mais qtd_calha,
 *    qtd_tubulacao, te_90_pbv_qtd, joelho_90_pbv_qtd, luva_pvc_qtd,
 *    cap_pvc_qtd
 *  - compdec: {item} booleano, mais calha_metros, tubulacao_metros,
 *    {peca}_qtd, e fixacao desdobrada em fix_abracadeira, fix_bucha,
 *    fix_parafuso
 *  - cedec: {item} booleano puro, sem quantidade
 *
 * O nome do item tambem divergia: `cisterna_opcao` no fornecedor e
 * `cisterna_logo` no COMPDEC e no CEDEC.
 */
final class MapaItensLegado
{
    /**
     * Coluna de quantidade por item, na etapa do fornecedor.
     *
     * A tabela tem DOIS pares de colunas para calha e tubulacao, de geracoes
     * diferentes do formulario: calha_metros/qtd_calha e
     * tubulacao_metros/qtd_tubulacao. Verificado em producao: os `*_metros`
     * estao SEMPRE nulos nesta tabela (0 de 856) e so os `qtd_*` sao usados
     * (827 e 828). Por isso o mapa aponta para os `qtd_*`.
     *
     * No COMPDEC e o inverso: la `calha_metros` e que esta preenchido (679 de
     * 858) — ver QTD_COMPDEC.
     *
     * @var array<string, string>
     */
    private const QTD_FORNECEDOR = [
        'calha' => 'qtd_calha',
        'tubulacao' => 'qtd_tubulacao',
        'fixacao' => 'qtd_fixacao',
        'te_pvc' => 'te_90_pbv_qtd',
        'joelho_pvc' => 'joelho_90_pbv_qtd',
        'luva_pvc' => 'luva_pvc_qtd',
        'cap_pvc' => 'cap_pvc_qtd',
    ];

    /**
     * Coluna de quantidade por item, na etapa do COMPDEC.
     *
     * @var array<string, string>
     */
    private const QTD_COMPDEC = [
        'calha' => 'calha_metros',
        'tubulacao' => 'tubulacao_metros',
        'te_pvc' => 'te_pvc_qtd',
        'joelho_pvc' => 'joelho_pvc_qtd',
        'luva_pvc' => 'luva_pvc_qtd',
        'cap_pvc' => 'cap_pvc_qtd',
    ];

    /**
     * @param  array<string, mixed>  $doc
     * @return array<int, array{item: string, conferido: bool, quantidade: ?float, unidade: ?string, detalhes: ?array<string, string>}>
     */
    public static function paraEtapa(EtapaVistoria $etapa, array $doc): array
    {
        $linhas = [];

        foreach (ItemInstalacao::cases() as $item) {
            $conferido = self::conferido($etapa, $item, $doc);

            if ($conferido === null) {
                // Coluna ausente no doc: o item nao foi avaliado nesta etapa.
                continue;
            }

            $quantidade = self::quantidade($etapa, $item, $doc);

            $linhas[] = [
                'item' => $item->value,
                'conferido' => $conferido,
                'quantidade' => $quantidade,
                'unidade' => $quantidade === null ? null : $item->unidadePadrao()?->value,
                'detalhes' => self::detalhes($etapa, $item, $doc),
            ];
        }

        return $linhas;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private static function conferido(EtapaVistoria $etapa, ItemInstalacao $item, array $doc): ?bool
    {
        foreach (self::colunasDeConferencia($etapa, $item) as $coluna) {
            if (! array_key_exists($coluna, $doc)) {
                continue;
            }

            return NormalizaEntrada::booleanoSimNao($doc[$coluna]) ?? false;
        }

        // Na etapa do fornecedor, as quatro pecas de PVC (te, joelho, luva,
        // cap) NAO tem coluna booleana: a tabela so tem te_90_pbv_qtd,
        // joelho_90_pbv_qtd, luva_pvc_qtd e cap_pvc_qtd. Verificado em
        // producao: 827 das 856 linhas tem essas quantidades preenchidas.
        //
        // Sem este fallback o item seria descartado por ausencia de booleano,
        // e a carga perderia 827 x 4 = 3.308 registros de item.
        $quantidade = self::quantidade($etapa, $item, $doc);

        if ($quantidade !== null) {
            return $quantidade > 0;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private static function colunasDeConferencia(EtapaVistoria $etapa, ItemInstalacao $item): array
    {
        if ($etapa === EtapaVistoria::FORNECEDOR) {
            // O fornecedor chamava o primeiro item de `cisterna_opcao`, nao
            // `cisterna_logo_opcao`.
            $base = $item === ItemInstalacao::CISTERNA_LOGO ? 'cisterna' : $item->value;

            return [$base.'_opcao', $item->value];
        }

        return [$item->value];
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private static function quantidade(EtapaVistoria $etapa, ItemInstalacao $item, array $doc): ?float
    {
        $mapa = match ($etapa) {
            EtapaVistoria::FORNECEDOR => self::QTD_FORNECEDOR,
            EtapaVistoria::COMPDEC => self::QTD_COMPDEC,
            // O CEDEC so conferia, sem quantidade.
            EtapaVistoria::CEDEC => [],
        };

        $coluna = $mapa[$item->value] ?? null;

        return $coluna === null ? null : NormalizaEntrada::decimal($doc[$coluna] ?? null);
    }

    /**
     * Somente fixacao no COMPDEC tem subquantidades. No legado eram tres
     * colunas soltas; aqui viram uma chave cada em detalhes jsonb.
     *
     * @param  array<string, mixed>  $doc
     * @return array<string, string>|null
     */
    private static function detalhes(EtapaVistoria $etapa, ItemInstalacao $item, array $doc): ?array
    {
        if (! $item->aceitaDetalhes() || $etapa !== EtapaVistoria::COMPDEC) {
            return null;
        }

        $detalhes = [];

        foreach (['abracadeira' => 'fix_abracadeira', 'bucha' => 'fix_bucha', 'parafuso' => 'fix_parafuso'] as $chave => $coluna) {
            $valor = trim((string) ($doc[$coluna] ?? ''));

            if ($valor !== '') {
                $detalhes[$chave] = $valor;
            }
        }

        return $detalhes === [] ? null : $detalhes;
    }
}
