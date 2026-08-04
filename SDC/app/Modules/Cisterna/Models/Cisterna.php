<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Models\Municipio;
use App\Modules\Cisterna\Enums\StatusCisterna;
use App\Modules\Cisterna\Enums\TipoCisterna;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use Database\Factories\CisternaFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int             $id
 * @property int             $municipio_id
 * @property string          $codigo
 * @property string          $nome
 * @property ?string         $endereco
 * @property ?float          $latitude
 * @property ?float          $longitude
 * @property ?int            $capacidade_litros
 * @property TipoCisterna    $tipo
 * @property StatusCisterna  $status
 * @property ?\Carbon\Carbon $data_instalacao
 * @property ?string         $responsavel_nome
 * @property ?string         $responsavel_telefone
 * @property ?string         $observacoes
 * @property ?int            $legacy_id
 */
class Cisterna extends Model implements Rastreavel
{
    use HasFactory;
    use SoftDeletes;
    use TrilhaDeAcoes;

    protected $table = 'cisternas';

    protected $fillable = [
        'municipio_id',
        'codigo',
        'nome',
        'endereco',
        'latitude',
        'longitude',
        'capacidade_litros',
        'tipo',
        'status',
        'data_instalacao',
        'responsavel_nome',
        'responsavel_telefone',
        'observacoes',
        'legacy_id',
        'created_by',
    ];

    protected $casts = [
        'tipo' => TipoCisterna::class,
        'status' => StatusCisterna::class,
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'capacidade_litros' => 'integer',
        'data_instalacao' => 'date',
        'legacy_id' => 'integer',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    /* Scopes */

    public function scopeAtiva(Builder $query): Builder
    {
        return $query->where('status', StatusCisterna::ATIVA->value);
    }

    public function scopeDoMunicipio(Builder $query, int $municipioId): Builder
    {
        return $query->where('municipio_id', $municipioId);
    }

    public function scopeDoTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeBuscarPorTermo(Builder $query, ?string $termo): Builder
    {
        if (! $termo) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($termo): void {
            $q->where('codigo', 'ilike', "%{$termo}%")
                ->orWhere('nome', 'ilike', "%{$termo}%");
        });
    }

    protected static function newFactory(): CisternaFactory
    {
        return CisternaFactory::new();
    }

    /**
     * Mesmo padrao de Decretacoes\Processo: o dono e quem cadastrou. Sem isso a coluna
     * nunca se preenche e a trilha nao acha destinatario. Nao sobrescreve valor que ja
     * veio (import ETL do legado, seeder).
     */
    protected static function booted(): void
    {
        static::creating(function (self $cisterna): void {
            if ($cisterna->created_by === null && Auth::id() !== null) {
                $cisterna->created_by = Auth::id();
            }
        });
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'cisterna';
    }

    public function rotuloProtocolo(): string
    {
        foreach ([$this->codigo, $this->nome] as $identificador) {
            $valor = trim((string) $identificador);

            if ($valor !== '') {
                return 'Cisterna '.$valor;
            }
        }

        return 'Cisterna #'.$this->getKey();
    }

    /**
     * responsavel_nome e responsavel_telefone sao texto livre do beneficiario, nao FK de
     * usuario: nao servem para notificar. O dono e quem cadastrou.
     *
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        return $this->created_by === null ? [] : [(int) $this->created_by];
    }

    public function urlNotificacao(): ?string
    {
        return '/cisternas/'.$this->getKey();
    }

    public function campoSituacao(): ?string
    {
        return 'status';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->status instanceof StatusCisterna ? $this->status->label() : null;
    }

    public function tipoSituacaoNotificacao(): ?string
    {
        return match ($this->status) {
            StatusCisterna::ATIVA => 'success',
            StatusCisterna::INATIVA => 'warning',
            default => 'info',
        };
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), [
            // Chave de idempotencia do ETL, nao dado do cadastro.
            'legacy_id',
        ]);
    }
}
