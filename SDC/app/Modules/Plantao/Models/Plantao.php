<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use App\Modules\Plantao\Enums\PeriodoPlantao;
use App\Modules\Plantao\Enums\StatusPlantao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plantao extends Model implements Rastreavel
{
    use TrilhaDeAcoes;

    protected $table = 'plantoes';

    protected $fillable = [
        'data',
        'plantonista_id',
        'plantonista_nome',
        'periodo',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'data' => 'date',
        'status' => StatusPlantao::class,
        'periodo' => PeriodoPlantao::class,
    ];

    public function plantonista(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'plantonista_id');
    }

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'plantao';
    }

    public function rotuloProtocolo(): string
    {
        $data = $this->data?->format('d/m/Y');
        $periodo = $this->periodo instanceof PeriodoPlantao ? $this->periodo->label() : null;

        if ($data === null) {
            return 'Plantao #'.$this->getKey();
        }

        return $periodo === null ? 'Plantao de '.$data : "Plantao de {$data} ({$periodo})";
    }

    /**
     * plantonista_id ja e FK para users e e obrigatoria: o plantonista e o dono da escala.
     * Nao foi preciso criar coluna de dono neste modulo.
     *
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        return $this->plantonista_id === null ? [] : [(int) $this->plantonista_id];
    }

    public function urlNotificacao(): ?string
    {
        return '/plantao';
    }

    public function campoSituacao(): ?string
    {
        return 'status';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->status instanceof StatusPlantao ? $this->status->label() : null;
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), [
            // Espelho do nome do plantonista, gravado junto com plantonista_id.
            'plantonista_nome',
        ]);
    }
}
