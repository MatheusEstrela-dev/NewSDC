<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoDanosHumanos extends Model
{
    protected $table = 'processo_danos_humanos';

    protected $fillable = [
        'processo_id',
        'municipio_id',
        'obitos',
        'feridos',
        'enfermos',
        'desabrigados',
        'desalojados',
        'desaparecidos',
        'outros_afetados',
        'observacoes',
    ];

    protected $casts = [
        'obitos' => 'integer',
        'feridos' => 'integer',
        'enfermos' => 'integer',
        'desabrigados' => 'integer',
        'desalojados' => 'integer',
        'desaparecidos' => 'integer',
        'outros_afetados' => 'integer',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Municipio::class);
    }

    public function getTotalAfetadosAttribute(): int
    {
        return $this->obitos +
               $this->feridos +
               $this->enfermos +
               $this->desabrigados +
               $this->desalojados +
               $this->desaparecidos +
               $this->outros_afetados;
    }
}
