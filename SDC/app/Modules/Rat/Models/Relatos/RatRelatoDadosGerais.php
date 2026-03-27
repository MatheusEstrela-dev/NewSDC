<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Relatos;

/**
 * Dados Gerais de uma ocorrência RAT.
 * 
 * Campos conforme a aba "Dados Gerais" do formulário:
 * - Tipo de Recurso
 * - Categoria
 * - Descrição
 * - Data/Hora de Deslocamento
 * - Local de Origem
 * - Local de Destino
 * - Quilometragem
 */
class RatRelatoDadosGerais extends RatRelato
{
    protected $table = 'rat_relato_dados_gerais';

    protected $fillable = [
        'tipo_recurso',
        'categoria',
        'descricao',
        'data_deslocamento',
        'hora_deslocamento',
        'local_origem',
        'local_destino',
        'quilometragem',
        'data_chegada',
        'hora_chegada',
        'local_fato',
        'endereco_fato',
        'lat_long_fato',
        'km_percorrido',
        'valor_km',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data_deslocamento' => 'date',
        'hora_deslocamento' => 'datetime',
        'data_chegada' => 'date',
        'hora_chegada' => 'datetime',
        'quilometragem' => 'float',
        'km_percorrido' => 'float',
        'valor_km' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
