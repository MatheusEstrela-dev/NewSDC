<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Modules\Treinamento\Enums\TipoItem;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModuloItem extends Model
{
    use SoftDeletes, UsaConexaoDeTreinamento;

    protected $table = 'treinamento.modulo_itens';

    protected $fillable = [
        'modulo_id',
        'tipo',
        'titulo',
        'descricao',
        'ordem',
        'obrigatorio',
        'arquivo_path',
        'arquivo_nome',
        'arquivo_mime',
        'arquivo_tamanho',
        'total_paginas',
    ];

    protected $casts = [
        'tipo' => TipoItem::class,
        'ordem' => 'integer',
        'obrigatorio' => 'boolean',
        'arquivo_tamanho' => 'integer',
        'total_paginas' => 'integer',
    ];

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class);
    }

    public function temArquivo(): bool
    {
        return $this->arquivo_path !== null && $this->arquivo_path !== '';
    }
}
