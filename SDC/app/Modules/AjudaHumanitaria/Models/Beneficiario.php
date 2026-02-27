<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use App\Modules\AjudaHumanitaria\Enums\TipoCadastroBeneficiario;
use App\Modules\AjudaHumanitaria\Enums\StatusBeneficiario;
use App\Modules\AjudaHumanitaria\Enums\SituacaoVulnerabilidade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * Model: Beneficiario de Ajuda Humanitaria
 *
 * Aggregate Root do modulo Ajuda Humanitaria
 * Representa pessoa ou familia afetada por desastre que recebe assistencia
 */
class Beneficiario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'beneficiarios';

    protected $fillable = [
        'tipo_cadastro',
        'cpf',
        'nome_responsavel',
        'rg',
        'data_nascimento',
        'telefone',
        'email',
        'endereco_completo',
        'municipio_id',
        'bairro',
        'cep',
        'numero_membros_familia',
        'situacao_vulnerabilidade',
        'desastre_id',
        'data_cadastro',
        'status',
        'observacoes',
        'abrigo_atual_id',
        'data_entrada_abrigo',
        'created_by',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'data_cadastro' => 'date',
        'data_entrada_abrigo' => 'date',
        'numero_membros_familia' => 'integer',
        'tipo_cadastro' => TipoCadastroBeneficiario::class,
        'status' => StatusBeneficiario::class,
        'situacao_vulnerabilidade' => SituacaoVulnerabilidade::class,
    ];

    protected $appends = [
        'idade',
        'dias_desde_cadastro',
        'esta_em_abrigo',
        'pode_receber_auxilio',
    ];

    /**
     * Relacionamento: Membros da Familia
     */
    public function membrosFamilia(): HasMany
    {
        return $this->hasMany(MembroFamilia::class, 'beneficiario_id');
    }

    /**
     * Relacionamento: Auxilios Recebidos
     */
    public function auxilios(): HasMany
    {
        return $this->hasMany(Auxilio::class, 'beneficiario_id')
                    ->orderBy('data_distribuicao', 'desc');
    }

    /**
     * Relacionamento: Abrigo Atual
     */
    public function abrigoAtual(): BelongsTo
    {
        return $this->belongsTo(Abrigo::class, 'abrigo_atual_id');
    }

    /**
     * Relacionamento: Historico de Abrigos (Many-to-Many)
     */
    public function abrigosHistorico(): BelongsToMany
    {
        return $this->belongsToMany(
            Abrigo::class,
            'beneficiario_abrigo',
            'beneficiario_id',
            'abrigo_id'
        )->withPivot(['data_entrada', 'data_saida', 'motivo_saida'])
          ->withTimestamps()
          ->orderBy('data_entrada', 'desc');
    }

    /**
     * Accessor: Idade do beneficiario
     */
    public function getIdadeAttribute(): ?int
    {
        if (!$this->data_nascimento) {
            return null;
        }

        return $this->data_nascimento->age;
    }

    /**
     * Accessor: Dias desde o cadastro
     */
    public function getDiasDesdeCadastroAttribute(): int
    {
        return (int) Carbon::today()->diffInDays($this->data_cadastro);
    }

    /**
     * Accessor: Esta em abrigo?
     */
    public function getEstaEmAbrigoAttribute(): bool
    {
        return $this->abrigo_atual_id !== null;
    }

    /**
     * Accessor: Pode receber auxilio?
     */
    public function getPodeReceberAuxilioAttribute(): bool
    {
        return $this->status === StatusBeneficiario::ATIVO;
    }

    /**
     * Scope: Filtrar por status ATIVO
     */
    public function scopeAtivos($query)
    {
        return $query->where('status', StatusBeneficiario::ATIVO->value);
    }

    /**
     * Scope: Filtrar por status FALECIDO
     */
    public function scopeFalecidos($query)
    {
        return $query->where('status', StatusBeneficiario::FALECIDO->value);
    }

    /**
     * Scope: Filtrar por situacao DESABRIGADO
     */
    public function scopeDesabrigados($query)
    {
        return $query->where('situacao_vulnerabilidade', SituacaoVulnerabilidade::DESABRIGADO->value);
    }

    /**
     * Scope: Filtrar por situacao DESALOJADO
     */
    public function scopeDesalojados($query)
    {
        return $query->where('situacao_vulnerabilidade', SituacaoVulnerabilidade::DESALOJADO->value);
    }

    /**
     * Scope: Filtrar por beneficiarios em abrigos
     */
    public function scopeEmAbrigo($query)
    {
        return $query->whereNotNull('abrigo_atual_id');
    }

    /**
     * Scope: Filtrar por beneficiarios fora de abrigos
     */
    public function scopeForaDeAbrigo($query)
    {
        return $query->whereNull('abrigo_atual_id');
    }

    /**
     * Scope: Filtrar por tipo de cadastro
     */
    public function scopeTipoCadastro($query, TipoCadastroBeneficiario|string $tipo)
    {
        $tipoValue = $tipo instanceof TipoCadastroBeneficiario ? $tipo->value : $tipo;
        return $query->where('tipo_cadastro', $tipoValue);
    }

    /**
     * Scope: Filtrar por municipio
     */
    public function scopeMunicipio($query, int $municipioId)
    {
        return $query->where('municipio_id', $municipioId);
    }

    /**
     * Business Logic: Pode receber auxilio do tipo especificado?
     *
     * Verifica se o beneficiario esta apto a receber um tipo de auxilio
     * considerando o periodo minimo de 30 dias entre auxilios do mesmo tipo
     */
    public function podeReceberAuxilioDoTipo(string $tipoAuxilio): bool
    {
        if ($this->status === StatusBeneficiario::FALECIDO) {
            return false;
        }

        if ($this->status === StatusBeneficiario::INATIVO) {
            return false;
        }

        $ultimoAuxilioDoTipo = $this->auxilios()
            ->where('tipo_auxilio', $tipoAuxilio)
            ->where('status', 'ENTREGUE')
            ->where('data_distribuicao', '>=', Carbon::today()->subDays(30))
            ->first();

        return $ultimoAuxilioDoTipo === null;
    }

    /**
     * Business Logic: Validar cadastro familiar
     *
     * Se tipo_cadastro = FAMILIAR, deve ter numero_membros_familia >= 2
     */
    public function validarCadastroFamiliar(): bool
    {
        if ($this->tipo_cadastro === TipoCadastroBeneficiario::FAMILIAR) {
            return $this->numero_membros_familia >= 2;
        }

        return true;
    }

    /**
     * Business Logic: Pode ser transferido para outro abrigo?
     */
    public function podeSerTransferido(): bool
    {
        return $this->status === StatusBeneficiario::ATIVO
            && $this->abrigo_atual_id !== null;
    }

    /**
     * Business Logic: Pode receber alta do abrigo?
     */
    public function podeReceberAlta(): bool
    {
        return $this->status === StatusBeneficiario::ATIVO
            && $this->abrigo_atual_id !== null;
    }

    /**
     * Business Logic: Total de auxilios recebidos
     */
    public function getTotalAuxiliosRecebidos(): int
    {
        return $this->auxilios()
            ->where('status', 'ENTREGUE')
            ->count();
    }

    /**
     * Business Logic: Valor total de auxilios monetarios recebidos
     */
    public function getValorTotalAuxiliosMonetarios(): float
    {
        return $this->auxilios()
            ->where('tipo_auxilio', 'MONETARIO')
            ->where('status', 'ENTREGUE')
            ->sum('valor_monetario') ?? 0.0;
    }

    /**
     * Business Logic: Total de membros da familia (incluindo responsavel)
     */
    public function getTotalMembrosFamilia(): int
    {
        if ($this->tipo_cadastro === TipoCadastroBeneficiario::INDIVIDUAL) {
            return 1;
        }

        return $this->membrosFamilia()->count() + 1;
    }
}
