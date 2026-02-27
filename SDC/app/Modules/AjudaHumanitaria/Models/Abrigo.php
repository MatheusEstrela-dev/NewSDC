<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Modules\AjudaHumanitaria\Enums\TipoLocalAbrigo;
use App\Modules\AjudaHumanitaria\Enums\StatusAbrigo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * Model: Abrigo Temporario
 *
 * Representa um local utilizado para abrigar pessoas afetadas por desastres
 */
class Abrigo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'abrigos';

    protected $fillable = [
        'nome',
        'tipo_local',
        'endereco_completo',
        'municipio_id',
        'bairro',
        'cep',
        'coordenadas_lat',
        'coordenadas_lng',
        'capacidade_maxima',
        'ocupacao_atual',
        'responsavel_nome',
        'responsavel_telefone',
        'responsavel_cargo',
        'status',
        'data_abertura',
        'data_fechamento',
        'infraestrutura',
        'recursos_disponiveis',
        'observacoes',
        'created_by',
    ];

    protected $casts = [
        'data_abertura' => 'date',
        'data_fechamento' => 'date',
        'capacidade_maxima' => 'integer',
        'ocupacao_atual' => 'integer',
        'coordenadas_lat' => 'decimal:8',
        'coordenadas_lng' => 'decimal:8',
        'infraestrutura' => 'array',
        'recursos_disponiveis' => 'array',
        'tipo_local' => TipoLocalAbrigo::class,
        'status' => StatusAbrigo::class,
    ];

    protected $appends = [
        'taxa_ocupacao',
        'disponibilidade',
        'esta_lotado',
        'dias_em_operacao',
    ];

    /**
     * Relacionamento: Beneficiarios atualmente no abrigo
     */
    public function beneficiariosAtuais(): HasMany
    {
        return $this->hasMany(Beneficiario::class, 'abrigo_atual_id');
    }

    /**
     * Relacionamento: Historico de beneficiarios (Many-to-Many)
     */
    public function beneficiariosHistorico(): BelongsToMany
    {
        return $this->belongsToMany(
            Beneficiario::class,
            'beneficiario_abrigo',
            'abrigo_id',
            'beneficiario_id'
        )->withPivot(['data_entrada', 'data_saida', 'motivo_saida'])
          ->withTimestamps();
    }

    /**
     * Relacionamento: Auxilios distribuidos no abrigo
     */
    public function auxilios(): HasMany
    {
        return $this->hasMany(Auxilio::class, 'abrigo_id');
    }

    /**
     * Accessor: Taxa de ocupacao (%)
     */
    public function getTaxaOcupacaoAttribute(): float
    {
        if ($this->capacidade_maxima === 0) {
            return 0.0;
        }

        return round(($this->ocupacao_atual / $this->capacidade_maxima) * 100, 2);
    }

    /**
     * Accessor: Vagas disponiveis
     */
    public function getDisponibilidadeAttribute(): int
    {
        return max(0, $this->capacidade_maxima - $this->ocupacao_atual);
    }

    /**
     * Accessor: Esta lotado?
     */
    public function getEstaLotadoAttribute(): bool
    {
        return $this->ocupacao_atual >= $this->capacidade_maxima;
    }

    /**
     * Accessor: Dias em operacao
     */
    public function getDiasEmOperacaoAttribute(): int
    {
        $dataFim = $this->data_fechamento ?? Carbon::today();
        return (int) $this->data_abertura->diffInDays($dataFim);
    }

    /**
     * Scope: Filtrar por status ATIVO
     */
    public function scopeAtivos($query)
    {
        return $query->where('status', StatusAbrigo::ATIVO->value);
    }

    /**
     * Scope: Filtrar por abrigos LOTADOS
     */
    public function scopeLotados($query)
    {
        return $query->whereRaw('ocupacao_atual >= capacidade_maxima');
    }

    /**
     * Scope: Filtrar por abrigos com vagas disponiveis
     */
    public function scopeComVagas($query)
    {
        return $query->whereRaw('ocupacao_atual < capacidade_maxima')
                     ->whereIn('status', [StatusAbrigo::ATIVO->value]);
    }

    /**
     * Scope: Filtrar por abrigos proximos a lotacao (>80% ocupacao)
     */
    public function scopeProximosLotacao($query)
    {
        return $query->whereRaw('(ocupacao_atual / capacidade_maxima) >= 0.8')
                     ->whereRaw('ocupacao_atual < capacidade_maxima');
    }

    /**
     * Scope: Filtrar por municipio
     */
    public function scopeMunicipio($query, int $municipioId)
    {
        return $query->where('municipio_id', $municipioId);
    }

    /**
     * Scope: Filtrar por tipo de local
     */
    public function scopeTipoLocal($query, TipoLocalAbrigo|string $tipo)
    {
        $tipoValue = $tipo instanceof TipoLocalAbrigo ? $tipo->value : $tipo;
        return $query->where('tipo_local', $tipoValue);
    }

    /**
     * Scope: Abrigos em operacao (abertos e nao fechados)
     */
    public function scopeEmOperacao($query)
    {
        return $query->whereNull('data_fechamento')
                     ->whereNotIn('status', [StatusAbrigo::DESATIVADO->value]);
    }

    /**
     * Business Logic: Pode receber novos beneficiarios?
     */
    public function podeReceberBeneficiarios(): bool
    {
        return $this->status === StatusAbrigo::ATIVO
            && $this->ocupacao_atual < $this->capacidade_maxima;
    }

    /**
     * Business Logic: Adicionar beneficiario ao abrigo
     *
     * Incrementa ocupacao_atual e atualiza status se necessario
     */
    public function adicionarBeneficiario(): void
    {
        if (!$this->podeReceberBeneficiarios()) {
            throw new \RuntimeException('Abrigo nao pode receber novos beneficiarios');
        }

        $this->increment('ocupacao_atual');

        if ($this->ocupacao_atual >= $this->capacidade_maxima) {
            $this->update(['status' => StatusAbrigo::LOTADO]);
        }
    }

    /**
     * Business Logic: Remover beneficiario do abrigo
     *
     * Decrementa ocupacao_atual e atualiza status se necessario
     */
    public function removerBeneficiario(): void
    {
        if ($this->ocupacao_atual <= 0) {
            throw new \RuntimeException('Nao ha beneficiarios no abrigo para remover');
        }

        $this->decrement('ocupacao_atual');

        if ($this->status === StatusAbrigo::LOTADO && $this->ocupacao_atual < $this->capacidade_maxima) {
            $this->update(['status' => StatusAbrigo::ATIVO]);
        }
    }

    /**
     * Business Logic: Pode ser desativado?
     *
     * So pode desativar se nao houver beneficiarios
     */
    public function podeSerDesativado(): bool
    {
        return $this->ocupacao_atual === 0;
    }

    /**
     * Business Logic: Fechar abrigo
     */
    public function fechar(): void
    {
        if (!$this->podeSerDesativado()) {
            throw new \RuntimeException('Nao e possivel fechar abrigo com beneficiarios');
        }

        $this->update([
            'status' => StatusAbrigo::DESATIVADO,
            'data_fechamento' => Carbon::today(),
        ]);
    }

    /**
     * Business Logic: Total de beneficiarios que ja passaram pelo abrigo
     */
    public function getTotalBeneficiariosHistorico(): int
    {
        return $this->beneficiariosHistorico()->count();
    }

    /**
     * Business Logic: Total de auxilios distribuidos no abrigo
     */
    public function getTotalAuxiliosDistribuidos(): int
    {
        return $this->auxilios()
            ->where('status', 'ENTREGUE')
            ->count();
    }

    /**
     * Business Logic: Verificar se possui infraestrutura especifica
     */
    public function possuiInfraestrutura(string $tipo): bool
    {
        $infraestrutura = $this->infraestrutura ?? [];
        return in_array($tipo, $infraestrutura);
    }

    /**
     * Business Logic: Verificar se possui recurso disponivel
     */
    public function possuiRecurso(string $recurso): bool
    {
        $recursos = $this->recursos_disponiveis ?? [];
        return in_array($recurso, $recursos);
    }
}
