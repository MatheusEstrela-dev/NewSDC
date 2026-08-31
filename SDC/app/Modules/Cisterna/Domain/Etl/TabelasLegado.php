<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

/**
 * Mapa das tabelas do legado `sdc` e a ordem de carga.
 *
 * A ordem respeita as dependencias do dominio novo: comunidades e lotes
 * primeiro, porque o beneficiario referencia os dois; o beneficiario antes
 * das vistorias; a vistoria do fornecedor antes da conferencia do COMPDEC,
 * porque sinc_cisterna_rel_compdec.instalacao_id aponta para
 * sinc_cisterna_rel_fornecedor.id, nao para a cisterna.
 *
 * `sinc_cisterna_relatorio` (89 campos, sem rota nem controller) e
 * `sinc_cisterna_old` (schema anterior) estao deliberadamente ausentes.
 */
final class TabelasLegado
{
    /**
     * @var array<int, string>
     */
    public const ORDEM_DE_CARGA = [
        'sinc_cisterna_com',
        'sinc_cisterna_lotes',
        'sinc_cisterna_ordem_servico',
        'sinc_cisterna',
        'sinc_cisterna_rel_fornecedor',
        'sinc_cisterna_rel_compdec',
        'sinc_cisterna_rel_cedec',
        'sinc_cisterna_notificacoes',
    ];

    /**
     * @var array<string, string>
     */
    public const CHAVE_PRIMARIA = [
        'sinc_cisterna_com' => 'id',
        'sinc_cisterna_lotes' => 'id',
        'sinc_cisterna_ordem_servico' => 'id',
        'sinc_cisterna' => 'id',
        'sinc_cisterna_rel_fornecedor' => 'id',
        'sinc_cisterna_rel_compdec' => 'id',
        'sinc_cisterna_rel_cedec' => 'id',
        'sinc_cisterna_notificacoes' => 'id',
    ];

    /**
     * Rotulo do recurso no cisterna_etl_log.
     *
     * @var array<string, string>
     */
    public const RECURSO = [
        'sinc_cisterna_com' => 'comunidades',
        'sinc_cisterna_lotes' => 'lotes',
        'sinc_cisterna_ordem_servico' => 'os',
        'sinc_cisterna' => 'beneficiarios',
        'sinc_cisterna_rel_fornecedor' => 'vistorias',
        'sinc_cisterna_rel_compdec' => 'vistorias',
        'sinc_cisterna_rel_cedec' => 'vistorias',
        'sinc_cisterna_notificacoes' => 'notificacoes',
    ];

    /**
     * @return array<int, string>
     */
    public static function resolverSelecao(?string $only): array
    {
        if ($only === null || trim($only) === '') {
            return self::ORDEM_DE_CARGA;
        }

        $pedidas = array_map('trim', explode(',', $only));

        return array_values(array_filter(
            self::ORDEM_DE_CARGA,
            fn (string $tabela): bool => in_array($tabela, $pedidas, true)
        ));
    }
}
