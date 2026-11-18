<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Modules\Treinamento\Enums\TipoItem;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'video_id',
        'origem_item_id',
        'alterado_desde_copia',
    ];

    protected $casts = [
        'tipo' => TipoItem::class,
        'ordem' => 'integer',
        'obrigatorio' => 'boolean',
        'arquivo_tamanho' => 'integer',
        'total_paginas' => 'integer',
        'video_id' => 'integer',
        'origem_item_id' => 'integer',
        'alterado_desde_copia' => 'boolean',
    ];

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class);
    }

    /** Item de onde este foi copiado numa nova versao do curso (NULL se nao e copia). */
    public function origem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'origem_item_id');
    }

    /** Configuracao da prova; existe so em itens PROVA. */
    public function prova(): HasOne
    {
        return $this->hasOne(Prova::class);
    }

    public function temArquivo(): bool
    {
        return $this->arquivo_path !== null && $this->arquivo_path !== '';
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function temVideoPublicado(): bool
    {
        return $this->tipo === TipoItem::VIDEO && $this->video?->temVersaoPublicada() === true;
    }

    /**
     * Teto da posicao de progresso: paginas no PDF, segundos no video com versao publicada;
     * null quando o item nao registra progresso por posicao.
     */
    public function totalProgresso(): ?int
    {
        return match ($this->tipo) {
            TipoItem::PDF => max(1, (int) $this->total_paginas),
            TipoItem::VIDEO => $this->temVideoPublicado() ? $this->video->duracao_segundos : null,
            default => null,
        };
    }
}
