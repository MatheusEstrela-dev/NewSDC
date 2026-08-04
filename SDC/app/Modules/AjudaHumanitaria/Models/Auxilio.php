<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Modules\AjudaHumanitaria\Enums\TipoAuxilio;
use App\Modules\AjudaHumanitaria\Enums\StatusAuxilio;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model: Auxilio Distribuido
 *
 * Aggregate Root para auxilios entregues a beneficiarios
 */
class Auxilio extends Model implements Rastreavel
{
    use HasFactory, SoftDeletes, TrilhaDeAcoes;

    protected $table = 'auxilios';

    protected $fillable = [
        'tipo_auxilio',
        'descricao',
        'data_distribuicao',
        'beneficiario_id',
        'abrigo_id',
        'valor_monetario',
        'responsavel_entrega',
        'comprovante_path',
        'observacoes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'data_distribuicao' => 'date',
        'valor_monetario' => 'decimal:2',
        'tipo_auxilio' => TipoAuxilio::class,
        'status' => StatusAuxilio::class,
    ];

    /**
     * Relacionamento: Beneficiario que recebeu
     */
    public function beneficiario(): BelongsTo
    {
        return $this->belongsTo(Beneficiario::class, 'beneficiario_id');
    }

    /**
     * Relacionamento: Abrigo onde foi distribuido
     */
    public function abrigo(): BelongsTo
    {
        return $this->belongsTo(Abrigo::class, 'abrigo_id');
    }

    /**
     * Relacionamento: Itens do auxilio (para kits)
     */
    public function itens(): HasMany
    {
        return $this->hasMany(ItemAuxilio::class, 'auxilio_id');
    }

    /**
     * Relacionamento: Movimentacoes de estoque geradas
     */
    public function movimentacoesEstoque(): HasMany
    {
        return $this->hasMany(MovimentacaoEstoque::class, 'auxilio_id');
    }

    /**
     * Scope: Filtrar por status
     */
    public function scopeStatus($query, StatusAuxilio|string $status)
    {
        $statusValue = $status instanceof StatusAuxilio ? $status->value : $status;
        return $query->where('status', $statusValue);
    }

    /**
     * Scope: Filtrar por tipo
     */
    public function scopeTipo($query, TipoAuxilio|string $tipo)
    {
        $tipoValue = $tipo instanceof TipoAuxilio ? $tipo->value : $tipo;
        return $query->where('tipo_auxilio', $tipoValue);
    }

    /**
     * Scope: Auxilios entregues
     */
    public function scopeEntregues($query)
    {
        return $query->where('status', StatusAuxilio::ENTREGUE->value);
    }

    /**
     * Scope: Auxilios planejados
     */
    public function scopePlanejados($query)
    {
        return $query->where('status', StatusAuxilio::PLANEJADO->value);
    }

    /**
     * Scope: Filtrar por periodo
     */
    public function scopePeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_distribuicao', [$dataInicio, $dataFim]);
    }

    /**
     * Business Logic: Pode ser entregue?
     */
    public function podeSerEntregue(): bool
    {
        return $this->status === StatusAuxilio::PLANEJADO
            && $this->beneficiario->pode_receber_auxilio;
    }

    /**
     * Business Logic: Marcar como entregue
     */
    public function marcarComoEntregue(string $comprovantePath): void
    {
        if (!$this->podeSerEntregue()) {
            throw new \RuntimeException('Auxilio nao pode ser marcado como entregue');
        }

        $this->update([
            'status' => StatusAuxilio::ENTREGUE,
            'comprovante_path' => $comprovantePath,
        ]);
    }

    /**
     * Business Logic: Cancelar auxilio
     */
    public function cancelar(): void
    {
        if ($this->status === StatusAuxilio::ENTREGUE) {
            throw new \RuntimeException('Nao e possivel cancelar auxilio ja entregue');
        }

        $this->update(['status' => StatusAuxilio::CANCELADO]);
    }

    /**
     * Business Logic: E auxilio monetario?
     */
    public function isMonetario(): bool
    {
        return $this->tipo_auxilio === TipoAuxilio::MONETARIO;
    }

    /**
     * Business Logic: Total de itens (para kits)
     */
    public function getTotalItens(): int
    {
        return $this->itens()->sum('quantidade');
    }

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'ajuda-humanitaria';
    }

    /**
     * Sem coluna de protocolo: o auxilio se identifica pela data de distribuicao, que e
     * o que o operador reconhece na tela.
     */
    public function rotuloProtocolo(): string
    {
        $data = $this->data_distribuicao?->format('d/m/Y')
            ?? $this->created_at?->format('d/m/Y');

        return $data === null ? 'Auxilio #'.$this->getKey() : 'Auxilio de '.$data;
    }

    /**
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        return $this->created_by === null ? [] : [(int) $this->created_by];
    }

    /**
     * O modulo so expoe rotas de beneficiarios; nao existe tela de um auxilio. Null tira
     * o botao do card em vez de gerar um link que cai em 404.
     */
    public function urlNotificacao(): ?string
    {
        return null;
    }

    /**
     * Null forcado, e nao escolha de desenho: o cast de status aponta para
     * App\Modules\AjudaHumanitaria\Enums\StatusAuxilio, que NAO existe no projeto (nem
     * TipoAuxilio). Ler $this->status estoura. Enquanto os dois enums nao forem criados,
     * a trilha cobre edicao, vinculo e exclusao, que nao tocam nessas colunas.
     */
    public function campoSituacao(): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return array_merge($this->camposBaseIgnoradosNaTrilha(), [
            // Ver campoSituacao(): colunas com cast para enum inexistente.
            'status',
            'tipo_auxilio',
        ]);
    }
}
