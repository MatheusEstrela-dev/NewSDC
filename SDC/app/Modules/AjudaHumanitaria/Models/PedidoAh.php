<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Models\Municipio;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoDecreto;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Raiz do agregado do pedido de material de ajuda humanitaria.
 *
 * Model fino de proposito: a regra de negocio vive em
 * App\Modules\AjudaHumanitaria\Domain. Aqui ficam apenas relacoes, casts e
 * leitura derivada de apresentacao.
 */
class PedidoAh extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    public const MEDIA_ANEXOS = 'anexos_pedido';

    protected $table = 'pedidos_ah';

    protected $fillable = [
        'numero',
        'ano',
        'municipio_id',
        'cobrade_id',
        'pop_atendida',
        'decreto_se_ecp_vig',
        'tipo_decreto',
        'numero_decreto',
        'vigencia_decreto',
        'esforcos_realizados',
        'nome_coordenador',
        'tel_coordenador',
        'cel_coordenador',
        'email_coordenador',
        'nome_prefeito',
        'tel_prefeito',
        'cel_prefeito',
        'email_prefeito',
        'status',
        'analista_id',
        'diretor_id',
        'data_entrada_sistema',
        'data_hora_envio',
        'data_aprovacao',
        'created_by',
    ];

    protected $casts = [
        'numero'               => 'integer',
        'ano'                  => 'integer',
        'pop_atendida'         => 'integer',
        'decreto_se_ecp_vig'   => 'boolean',
        'tipo_decreto'         => TipoDecreto::class,
        'vigencia_decreto'     => 'date',
        'status'               => StatusPedidoAh::class,
        'data_entrada_sistema' => 'datetime',
        'data_hora_envio'      => 'datetime',
        'data_aprovacao'       => 'datetime',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PedidoAhItem::class, 'pedido_ah_id');
    }

    public function itensPedido(): HasMany
    {
        return $this->itens()->where('tipo', TipoItemPedido::Pedido->value);
    }

    public function itensLiberados(): HasMany
    {
        return $this->itens()->where('tipo', TipoItemPedido::Liberado->value);
    }

    public function pareceres(): HasMany
    {
        return $this->hasMany(PedidoAhParecer::class, 'pedido_ah_id');
    }

    public function tramites(): HasMany
    {
        return $this->hasMany(PedidoAhTramite::class, 'pedido_ah_id')->orderBy('created_at');
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(PedidoAhAgendamento::class, 'pedido_ah_id');
    }

    public function prestacaoConta(): HasOne
    {
        return $this->hasOne(PrestacaoConta::class, 'pedido_ah_id');
    }

    /**
     * RN-01: identificador exibido ao usuario.
     */
    public function getIdentificadorAttribute(): string
    {
        return "{$this->numero}/{$this->ano}";
    }

    /**
     * RN-22: anexos do pedido, somente PDF.
     *
     * Nao e singleFile, ao contrario das demais collections do projeto: o
     * legado grava varios documentos por pedido, em pedido/{id}.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_ANEXOS)
            ->useDisk((string) config('ajuda-humanitaria.disk', 'local'))
            ->acceptsFile(fn ($file): bool => $file->mimeType === 'application/pdf' || (
                $file->mimeType === 'application/x-empty'
                && mb_strtolower(pathinfo($file->name, PATHINFO_EXTENSION)) === 'pdf'
            ));
    }
}
