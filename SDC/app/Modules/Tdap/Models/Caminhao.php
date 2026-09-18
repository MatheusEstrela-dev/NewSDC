<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Support\Documento;
use App\Modules\Tdap\Support\VigenciaVistoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int     $id
 * @property int     $prestador_id
 * @property string  $placa
 * @property ?string $marca
 * @property ?string $modelo
 * @property ?string $cor
 * @property ?string $ano
 * @property float   $capacidade_m3
 * @property bool    $ativo
 * @property ?string $observacoes
 * @property-read Prestador $prestador
 */
class Caminhao extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_caminhoes';

    protected $fillable = [
        'prestador_id',
        'placa',
        'marca',
        'modelo',
        'cor',
        'ano',
        'capacidade_m3',
        'ativo',
        'observacoes',
    ];

    protected $casts = [
        'prestador_id'  => 'integer',
        'capacidade_m3' => 'decimal:2',
        'ativo'         => 'boolean',
    ];

    public function prestador(): BelongsTo
    {
        return $this->belongsTo(Prestador::class, 'prestador_id');
    }

    public function vistorias(): HasMany
    {
        return $this->hasMany(Vistoria::class, 'placa_id')->orderByDesc('data');
    }

    /**
     * Vistoria aprovada vigente (<= 12 meses), mais recente.
     *
     * A borda sai de VigenciaVistoria, a mesma que Vistoria::scopeVigente e o
     * accessor usam -- antes este `whereDate` era uma quarta copia da regra.
     */
    public function vistoriaVigente(): HasOne
    {
        return $this->hasOne(Vistoria::class, 'placa_id')
            ->where('parecer', ParecerVistoria::Aprovada->value)
            ->whereDate('data', '>=', VigenciaVistoria::dataLimite()->toDateString())
            ->latestOfMany('data');
    }

    /**
     * Vistoria aprovada mais recente, vigente ou nao.
     *
     * E a que o guard de ativacao do cronograma precisa avaliar: a pergunta la
     * nao e "esta vigente hoje" e sim "cobre o fim do cronograma", e so a
     * vistoria aprovada mais nova pode responder isso. Separada de
     * ultimaVistoria() porque aquela inclui reprovada -- util para a tela
     * distinguir "reprovado" de "nunca vistoriado", inutil para o guard.
     */
    public function ultimaVistoriaAprovada(): HasOne
    {
        return $this->hasOne(Vistoria::class, 'placa_id')
            ->where('parecer', ParecerVistoria::Aprovada->value)
            ->latestOfMany('data');
    }

    /**
     * A vistoria mais recente, vigente ou nao.
     *
     * Serve para a tela distinguir "vistoria venceu em tal data" de "nunca foi
     * vistoriado" -- sem ela, os dois casos aparecem como o mesmo vazio, e sao
     * problemas diferentes: um exige renovar, o outro exige cadastrar.
     */
    public function ultimaVistoria(): HasOne
    {
        return $this->hasOne(Vistoria::class, 'placa_id')->latestOfMany('data');
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeDoPrestador(Builder $query, int $prestadorId): Builder
    {
        return $query->where('prestador_id', $prestadorId);
    }

    public function scopeBuscar(Builder $query, ?string $termo): Builder
    {
        if (! $termo) {
            return $query;
        }

        $like = '%'.mb_strtoupper($termo).'%';

        /*
         * O CNPJ do prestador tambem identifica o caminhao.
         *
         * Quem cobra a operacao chega com o CNPJ da empresa em maos (nota,
         * empenho, oficio), nao com a placa. Sem isto a unica saida era abrir
         * Prestadores, achar a empresa e voltar filtrando por prestador_id.
         *
         * `Documento::digitos` porque a coluna guarda SOMENTE DIGITOS -- a busca
         * tem que funcionar tanto com "12.345.678/0001-95" quanto com
         * "12345678000195". Mesma normalizacao de Prestador::scopeBuscar; ver o
         * contrato em Tdap\Support\Documento.
         */
        $digitos = Documento::digitos($termo);

        return $query->where(function (Builder $q) use ($like, $digitos): void {
            $q->whereRaw('UPPER(placa) LIKE ?', [$like])
              ->orWhereRaw('UPPER(modelo) LIKE ?', [$like])
              ->orWhereRaw('UPPER(marca) LIKE ?', [$like])
              ->orWhereHas('prestador', function (Builder $p) use ($like, $digitos): void {
                  $p->whereRaw('UPPER(nome) LIKE ?', [$like]);

                  if ($digitos !== null) {
                      $p->orWhere('cnpj', 'LIKE', '%'.$digitos.'%');
                  }
              });
        });
    }

    /** Caminhoes do prestador identificado pelo CNPJ (com ou sem mascara). */
    public function scopeDoCnpj(Builder $query, ?string $cnpj): Builder
    {
        $digitos = Documento::digitos((string) $cnpj);

        if ($digitos === null) {
            return $query;
        }

        return $query->whereHas('prestador', fn (Builder $p) => $p->where('cnpj', $digitos));
    }
}
