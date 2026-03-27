<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Relatos;

/**
 * Recursos Empregados em uma ocorrência RAT.
 * 
 * Campos conforme a aba "Recursos Empregados" do formulário:
 * - Nome do Recurso
 * - Tipo (Helicóptero, Bombeiro, Viaturas, etc)
 * - Placa/Identificação
 * - Capacidade
 * - Compatibilidade
 */
class RatRelatoRecurso extends RatRelato
{
    protected $table = 'rat_relato_recursos';

    protected $fillable = [
        'nome_recurso',
        'tipo_recurso',
        'placa_identificacao',
        'capacidade',
        'compatibilidade',
        'valor_diaria',
        'quantidade',
        'periodo_utilizacao_inicio',
        'periodo_utilizacao_fim',
        'observacoes',
        'proprietario',
        'contato_proprietario',
        'telefone_proprietario',
        'situacao_recurso',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'capacidade' => 'integer',
        'quantidade' => 'integer',
        'valor_diaria' => 'float',
        'periodo_utilizacao_inicio' => 'datetime',
        'periodo_utilizacao_fim' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
