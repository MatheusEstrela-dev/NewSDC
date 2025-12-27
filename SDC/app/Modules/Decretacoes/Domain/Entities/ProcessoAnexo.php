<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entity: Anexos/Documentos do Processo
 */
class ProcessoAnexo extends Model
{
    protected $table = 'processo_anexos';

    protected $fillable = [
        'processo_id',
        'tipo',
        'nome_arquivo',
        'caminho',
        'tamanho',
        'mime_type',
        'descricao',
    ];

    protected $casts = [
        'tamanho' => 'integer',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    /**
     * Retorna URL pública do arquivo
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->caminho);
    }

    /**
     * Retorna tamanho formatado
     */
    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = $this->tamanho;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
