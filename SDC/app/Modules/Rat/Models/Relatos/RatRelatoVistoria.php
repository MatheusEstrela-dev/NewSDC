<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Relatos;

/**
 * Vistoria Técnica de uma ocorrência RAT.
 *
 * Campos conforme a aba "Vistoria" do formulário:
 * - Tipo de Documento
 * - Verso
 * - Página
 * - Patrimônio Bruto
 * - Documentação de Identificação
 * - Compatibilidade
 */
class RatRelatoVistoria extends RatRelato
{
    protected $table = 'rat_relato_vistorias';

    protected $fillable = [
        'tipo_documento',
        'verso',
        'pagina',
        'numero_pagina',
        'patrimonio_bruto',
        'patrimonio_liquido',
        'documentacao_identificacao',
        'compatibilidade',
        'distribuicao',
        'valor_avaliado',
        'data_vistoria',
        'hora_inicio',
        'hora_fim',
        'responsavel_vistoria',
        'matricula_responsavel',
        'assinatura',
        'observacoes_tecnicas',
        'conclusoes',
        'recomendacoes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'patrimonio_bruto' => 'float',
        'patrimonio_liquido' => 'float',
        'valor_avaliado' => 'float',
        'data_vistoria' => 'date',
        'hora_inicio' => 'datetime',
        'hora_fim' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
