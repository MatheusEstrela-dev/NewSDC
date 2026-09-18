<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Models\User;
use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Support\VigenciaVistoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     * 28 itens estruturais do checklist. (O docblock dizia 27; a lista sempre
     * teve 28 — o front monta a ficha a partir desta constante, entao o numero
     * errado so enganava quem lia o codigo.)
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

    /**
     * Vigencia em meses.
     *
     * O valor tem um dono so (VigenciaVistoria); a constante segue exposta aqui
     * porque chamadores e mensagens de erro referenciam Vistoria::VIGENCIA_MESES.
     */
    public const VIGENCIA_MESES = VigenciaVistoria::VIGENCIA_MESES;

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

    public function fotos(): HasMany
    {
        return $this->hasMany(VistoriaFoto::class, 'vistoria_id')->latest('id');
    }

    /* Computed */

    /**
     * Vigencia em DIAS, nao em instantes.
     *
     * `data` e coluna DATE (meia-noite) e `now()` traz a hora corrente: a
     * comparacao antiga dava FALSE para a vistoria feita exatamente 12 meses
     * atras, enquanto scopeVigente() -- que usa whereDate -- a considerava
     * vigente. O accessor e o scope divergiam por um dia, e era o scope que o
     * guard de ativacao do cronograma usava. Hoje os dois saem de
     * VigenciaVistoria, entao a borda e a mesma por construcao.
     */
    public function getEstaVigenteAttribute(): bool
    {
        if ($this->parecer !== ParecerVistoria::Aprovada) {
            return false;
        }

        return VigenciaVistoria::estaVigente($this->data);
    }

    /** Ultimo dia de validade da vistoria; null quando nao ha data. */
    public function getValidoAteAttribute(): ?\Carbon\Carbon
    {
        return VigenciaVistoria::validoAte($this->data);
    }

    /**
     * A vistoria cobre a data de referencia?
     *
     * Usado pelo guard de ativacao do cronograma, que precisa saber se a
     * vistoria vale ate o FIM do cronograma -- nao apenas hoje.
     */
    public function cobre(mixed $referencia): bool
    {
        if ($this->parecer !== ParecerVistoria::Aprovada) {
            return false;
        }

        return VigenciaVistoria::cobre($this->data, $referencia);
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
            ->whereDate('data', '>=', VigenciaVistoria::dataLimite()->toDateString());
    }

    /*
     * `scopeCobrindo` vivia aqui como contrapartida SQL de `cobre()`, sem
     * nenhum consumidor desde que nasceu e sem teste. Saiu: scope que ninguem
     * chama nao tem como estar certo -- este, por exemplo, atravessou a
     * correcao da borda bissexta de dataLimite() sem que ninguem notasse que
     * ele tambem dependia dela. Quando um relatorio precisar filtrar no banco
     * por "cobre a data X", o par `cobre()` + `VigenciaVistoria::dataLimite()`
     * esta logo acima, e dessa vez nasce com chamador e com teste.
     */

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
