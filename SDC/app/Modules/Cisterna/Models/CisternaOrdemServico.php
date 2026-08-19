<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use Database\Factories\Cisterna\CisternaOrdemServicoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Ordem de servico dentro de um lote de contratacao.
 *
 * Declara Rastreavel porque usa TrilhaDeAcoes: o trait chama
 * RegistroDeAcao::registrar(Rastreavel&Model), entao um model com o trait e
 * sem o contrato quebraria com TypeError em qualquer update.
 */
class CisternaOrdemServico extends Model implements HasMedia, Rastreavel
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    use TrilhaDeAcoes;

    protected $table = 'cisterna_ordens_servico';

    protected $fillable = ['lote_id', 'nome', 'observacao', 'legacy_id'];

    protected $casts = ['legacy_id' => 'integer'];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(CisternaLote::class, 'lote_id');
    }

    public function beneficiarios(): HasMany
    {
        return $this->hasMany(CisternaBeneficiario::class, 'ordem_servico_id');
    }

    public function registerMediaCollections(): void
    {
        // Legado: coluna link_doc.
        $this->addMediaCollection('documento_os')->singleFile();
    }

    protected static function newFactory(): CisternaOrdemServicoFactory
    {
        return CisternaOrdemServicoFactory::new();
    }

    /* Trilha de acoes */

    public function moduloNotificacao(): string
    {
        return 'cisterna';
    }

    public function rotuloProtocolo(): string
    {
        $nome = trim((string) $this->nome);

        return $nome !== ''
            ? 'Ordem de servico '.$nome
            : 'Ordem de servico #'.$this->getKey();
    }

    /**
     * Vazio de proposito: `cisterna_ordens_servico` nao tem `created_by` — a OS
     * e artefato de gestao do lote, nao protocolo de um dono. A trilha fica
     * ligada para o historico da OS; destinatario nenhum significa que a
     * alteracao nao vira card no sino de ninguem.
     *
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        return [];
    }

    public function urlNotificacao(): ?string
    {
        return '/cisternas/ordens-servico/'.$this->getKey();
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), ['legacy_id']);
    }
}
