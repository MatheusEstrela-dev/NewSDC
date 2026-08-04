<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Support\TrilhaNoProtocoloPai;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model: Item de Auxilio
 *
 * Representa um item especifico dentro de um auxilio (kit)
 */
class ItemAuxilio extends Model
{
    use HasFactory;
    use TrilhaNoProtocoloPai;

    protected $table = 'itens_auxilio';

    protected $fillable = [
        'auxilio_id',
        'descricao',
        'quantidade',
        'unidade_medida',
        'valor_unitario',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'valor_unitario' => 'decimal:2',
    ];

    /**
     * Relacionamento: Auxilio pai
     */
    public function auxilio(): BelongsTo
    {
        return $this->belongsTo(Auxilio::class, 'auxilio_id');
    }

    // ─── Trilha de acoes no protocolo pai ───────────────────────────────────

    public function protocoloDaTrilhaClasse(): string
    {
        return Auxilio::class;
    }

    public function protocoloDaTrilhaChave(): int|string|null
    {
        return $this->auxilio_id;
    }

    /**
     * Editado, e nao Relacionado: os itens SAO o conteudo do auxilio (o que foi
     * entregue), nao algo pendurado nele. Mexer nos itens e mexer no auxilio.
     */
    public function acaoNaTrilhaDoProtocolo(): AcaoTrilha
    {
        return AcaoTrilha::Editado;
    }
}
