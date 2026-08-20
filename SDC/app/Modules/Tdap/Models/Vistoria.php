<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Models\User;
use App\Modules\Tdap\Enums\ParecerVistoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int             $id
 * @property string          $nome
 * @property ?string         $edital
 * @property ?string         $lote
 * @property int             $placa_id
 * @property ?string         $modelo
 * @property ?string         $cor
 * @property \Carbon\Carbon  $data
 * @property ?string         $ano
 * @property float           $capacidade
 * @property ParecerVistoria $parecer
 * @property ?string         $ficha
 * @property ?string         $lacre
 * @property ?int            $user_id
 * @property ?string         $observacoes
 */
class Vistoria extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_vistorias';

    /**
     * 27 itens estruturais.
     *
     * @var array<int, string>
     */
    public const ITENS_ESTRUTURAIS = [
        'documento', 'para_choque_d', 'para_choque_t', 'placa_d', 'placa_t', 'selo_placa_t',
        'espel_ret_ex', 'motor_arr_bat', 'para_brisa', 'bancos_fixos', 'farois_d',
        'faroletes', 'trans_farois', 'lant_set_d', 'lant_set_e', 'pisca_alerta',
        'luz_re', 'luz_freio', 'freio_est', 'cond_pneus', 'cond_pneus_e',
        'buzina', 'extintor', 'cinto_seg', 'macanetas', 'tri_rod_mac',
        'aus_vaz_comb', 'aus_prop_pol',
    ];

    /**
     * 7 itens do tanque.
     *
     * @var array<int, string>
     */
    public const ITENS_TANQUE = [
        'pintura_ext', 'pintura_int', 'vazamento', 'mangote',
        'valv_expul', 'tampa_ved', 'agua_pot',
    ];

    /** Vigencia em meses. */
    public const VIGENCIA_MESES = 12;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'placa_id'   => 'integer',
        'data'       => 'date',
        'capacidade' => 'decimal:2',
        'user_id'    => 'integer',
        'parecer'    => ParecerVistoria::class,
    ];

    public function caminhao(): BelongsTo
    {
        return $this->belongsTo(Caminhao::class, 'placa_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* Computed */

    public function getEstaVigenteAttribute(): bool
    {
        return $this->parecer === ParecerVistoria::Aprovada
            && $this->data?->gte(now()->subMonths(self::VIGENCIA_MESES));
    }

    /* Scopes */

    public function scopeAprovada(Builder $query): Builder
    {
        return $query->where('parecer', ParecerVistoria::Aprovada->value);
    }

    public function scopeReprovada(Builder $query): Builder
    {
        return $query->where('parecer', ParecerVistoria::Reprovada->value);
    }

    public function scopeVigente(Builder $query): Builder
    {
        return $query
            ->where('parecer', ParecerVistoria::Aprovada->value)
            ->whereDate('data', '>=', now()->subMonths(self::VIGENCIA_MESES)->toDateString());
    }

    public function scopeDoCaminhao(Builder $query, int $caminhaoId): Builder
    {
        return $query->where('placa_id', $caminhaoId);
    }

    public function scopeBuscar(Builder $query, ?string $termo): Builder
    {
        if (! $termo) return $query;
        $like = '%'.mb_strtoupper($termo).'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->whereRaw('UPPER(nome) LIKE ?', [$like])
              ->orWhereRaw('UPPER(edital) LIKE ?', [$like])
              ->orWhereRaw('UPPER(lote) LIKE ?', [$like])
              ->orWhereRaw('UPPER(ficha) LIKE ?', [$like])
              // O numero do lacre e outro identificador que o fiscal tem em
              // maos na conferencia do caminhao.
              ->orWhereRaw('UPPER(lacre) LIKE ?', [$like]);
        });
    }
}
