<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Modules\Tdap\Enums\SituacaoAta;
use App\Modules\Tdap\Support\VigenciaAta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int             $id
 * @property string          $numero
 * @property \Carbon\Carbon  $dt_inicio
 * @property \Carbon\Carbon  $dt_final
 * @property ?string         $historico
 * @property bool            $ativo
 * @property ?string         $observacoes
 *
 * Atributos calculados (nao existem como coluna):
 * @property-read SituacaoAta $situacao
 * @property-read ?int         $dias_restantes
 * @property-read bool         $proxima_vencer
 */
class Ata extends Model
{
    use SoftDeletes;

    protected $table = 'tdap_atas';

    protected $fillable = [
        'numero',
        'dt_inicio',
        'dt_final',
        'historico',
        'ativo',
        'observacoes',
    ];

    protected $casts = [
        'dt_inicio' => 'date',
        'dt_final'  => 'date',
        'ativo'     => 'boolean',
    ];

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class, 'ata_id');
    }

    /**
     * Situacao derivada das datas de vigencia (accessor, nao e coluna).
     *
     * Delega em VigenciaAta para que Resources, export e front leiam sempre a
     * MESMA regra. Use este accessor para EXIBIR uma ata ja carregada; para
     * FILTRAR muitas atas use os scopes abaixo (a conta acontece no SQL).
     */
    protected function situacao(): Attribute
    {
        return Attribute::get(fn (): SituacaoAta => VigenciaAta::situacao(
            (bool) $this->ativo,
            $this->dt_inicio,
            $this->dt_final,
        ));
    }

    /** Dias ate o fim da vigencia: negativo = vencida, 0 = vence hoje, null = sem dt_final. */
    protected function diasRestantes(): Attribute
    {
        return Attribute::get(fn (): ?int => VigenciaAta::diasRestantes($this->dt_final));
    }

    /** Ata vigente que expira dentro da janela de alerta (30 dias). */
    protected function proximaVencer(): Attribute
    {
        return Attribute::get(fn (): bool => VigenciaAta::isProximaVencer(
            (bool) $this->ativo,
            $this->dt_inicio,
            $this->dt_final,
        ));
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeVigente(Builder $query): Builder
    {
        return $query->where('ativo', true)
            ->whereDate('dt_inicio', '<=', now())
            ->whereDate('dt_final', '>=', now());
    }

    /**
     * Ata ligada cuja vigencia expirou — o indicador novo.
     *
     * Complemento de scopeVigente() no eixo do vencimento: `ativo` continua
     * TRUE (nao foi desligada na mao), mas dt_final ja passou.
     */
    public function scopeVencida(Builder $query): Builder
    {
        return $query->where('ativo', true)
            ->whereDate('dt_final', '<', now());
    }

    /** Ata ligada cuja vigencia ainda nao comecou. */
    public function scopeAgendada(Builder $query): Builder
    {
        return $query->where('ativo', true)
            ->whereDate('dt_inicio', '>', now());
    }

    /** Ata desligada manualmente. */
    public function scopeInativa(Builder $query): Builder
    {
        return $query->where('ativo', false);
    }

    /** Ata vigente que expira nos proximos 30 dias (alerta de renovacao). */
    public function scopeProximaVencer(Builder $query): Builder
    {
        return $query->vigente()
            ->whereDate('dt_final', '<=', now()->addDays(VigenciaAta::JANELA_PROXIMO_VENCER_DIAS));
    }

    /**
     * Filtra pela situacao (valor de SituacaoAta), traduzindo para o scope certo.
     *
     * Valor desconhecido ou vazio nao filtra nada — mesma tolerancia dos
     * outros filtros da listagem, que ignoram parametro invalido em silencio.
     */
    public function scopeSituacao(Builder $query, ?string $situacao): Builder
    {
        $caso = $situacao !== null && $situacao !== ''
            ? SituacaoAta::tryFrom($situacao)
            : null;

        return match ($caso) {
            SituacaoAta::Vigente  => $query->vigente(),
            SituacaoAta::Vencida  => $query->vencida(),
            SituacaoAta::Agendada => $query->agendada(),
            SituacaoAta::Inativa  => $query->inativa(),
            null                  => $query,
        };
    }

    public function scopeBuscar(Builder $query, ?string $termo): Builder
    {
        if (! $termo) {
            return $query;
        }

        $like = '%'.mb_strtoupper($termo).'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->whereRaw('UPPER(numero) LIKE ?', [$like])
              ->orWhereRaw('UPPER(historico) LIKE ?', [$like]);
        });
    }
}
