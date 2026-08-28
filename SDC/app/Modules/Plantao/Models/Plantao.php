<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Models;

use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use App\Modules\Plantao\Enums\StatusPlantao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plantao extends Model implements Rastreavel
{
    use HasFactory;
    use TrilhaDeAcoes;

    protected $table = 'plantoes';

    protected $fillable = [
        'data',
        'plantonista_id',
        'plantonista_nome',
        'plantonista_saida_id',
        'plantonista_saida_nome',
        'localizacao',
        'ocorrencias_destaque',
        'encerrado_em',
        'encerrado_por_id',
        'aceito_em',
        'aceito_por_id',
        'divergencia',
        'periodo',
        'escala_item_id',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'data' => 'date',
        'status' => StatusPlantao::class,
        'encerrado_em' => 'datetime',
        'aceito_em' => 'datetime',
    ];

    protected static function newFactory(): \Database\Factories\Plantao\PlantaoFactory
    {
        return \Database\Factories\Plantao\PlantaoFactory::new();
    }

    public function plantonista(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'plantonista_id');
    }

    public function plantonistaSaida(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'plantonista_saida_id');
    }

    public function encerradoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'encerrado_por_id');
    }

    public function aceitoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'aceito_por_id');
    }

    /**
     * O horario deste turno, resolvido pelo codigo guardado em `periodo`.
     *
     * Chave local `periodo` e nao `tipo_turno_id`: a coluna ja existia com o
     * codigo em texto e continua legivel sozinha no banco, sem join, o que
     * mantem o historico compreensivel se a tabela de tipos mudar.
     */
    public function tipoTurno(): BelongsTo
    {
        return $this->belongsTo(TipoTurno::class, 'periodo', 'codigo');
    }

    /**
     * A vaga da escala que este turno cumpriu. Null quando o turno foi aberto
     * fora de escala -- caso legitimo (EXTRAORDINARIO) e tambem o sinal de que
     * alguem assumiu sem estar escalado.
     */
    public function escalaItem(): BelongsTo
    {
        return $this->belongsTo(EscalaItem::class, 'escala_item_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ViaturaSnapshot::class, 'plantao_id');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(ViaturaMovimentacao::class, 'plantao_id');
    }

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'plantao';
    }

    public function rotuloProtocolo(): string
    {
        $data = $this->data?->format('d/m/Y');
        $periodo = $this->tipoTurno?->label();

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
            'plantonista_saida_nome',
        ]);
    }
}
