<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Entities;

use App\Modules\AjudaHumanitaria\Domain\ValueObjects\TipoCadastroBeneficiario;
use App\Modules\AjudaHumanitaria\Domain\ValueObjects\StatusBeneficiario;
use App\Modules\AjudaHumanitaria\Domain\ValueObjects\SituacaoVulnerabilidade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * Entity: Beneficiário de Ajuda Humanitária
 *
 * Aggregate Root do módulo Ajuda Humanitária
 * Representa pessoa ou família afetada por desastre que recebe assistência
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
     * Relacionamento: Membros da Família
     */
    public function membrosFamilia(): HasMany
    {
        return $this->hasMany(MembroFamilia::class, 'beneficiario_id');
    }

    /**
     * Relacionamento: Auxílios Recebidos
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
     * Relacionamento: Histórico de Abrigos (Many-to-Many)
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
     * Accessor: Idade do beneficiário
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
     * Accessor: Está em abrigo?
     */
    public function getEstaEmAbrigoAttribute(): bool
    {
        return $this->abrigo_atual_id !== null;
    }

    /**
     * Accessor: Pode receber auxílio?
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
     * Scope: Filtrar por situação DESABRIGADO
     */
    public function scopeDesabrigados($query)
    {
        return $query->where('situacao_vulnerabilidade', SituacaoVulnerabilidade::DESABRIGADO->value);
    }

    /**
     * Scope: Filtrar por situação DESALOJADO
     */
    public function scopeDesalojados($query)
    {
        return $query->where('situacao_vulnerabilidade', SituacaoVulnerabilidade::DESALOJADO->value);
    }

    /**
     * Scope: Filtrar por beneficiários em abrigos
     */
    public function scopeEmAbrigo($query)
    {
        return $query->whereNotNull('abrigo_atual_id');
    }

    /**
     * Scope: Filtrar por beneficiários fora de abrigos
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
     * Scope: Filtrar por município
     */
    public function scopeMunicipio($query, int $municipioId)
    {
        return $query->where('municipio_id', $municipioId);
    }

    /**
     * Business Logic: Pode receber auxílio do tipo especificado?
     *
     * Verifica se o beneficiário está apto a receber um tipo de auxílio
     * considerando o período mínimo de 30 dias entre auxílios do mesmo tipo
     */
    public function podeReceberAuxilioDoTipo(string $tipoAuxilio): bool
    {
        // Beneficiário falecido não pode receber auxílio
        if ($this->status === StatusBeneficiario::FALECIDO) {
            return false;
        }

        // Beneficiário inativo não pode receber auxílio
        if ($this->status === StatusBeneficiario::INATIVO) {
            return false;
        }

        // Verifica se recebeu auxílio do mesmo tipo nos últimos 30 dias
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
     * Business Logic: Total de auxílios recebidos
     */
    public function getTotalAuxiliosRecebidos(): int
    {
        return $this->auxilios()
            ->where('status', 'ENTREGUE')
            ->count();
    }

    /**
     * Business Logic: Valor total de auxílios monetários recebidos
     */
    public function getValorTotalAuxiliosMonetarios(): float
    {
        return $this->auxilios()
            ->where('tipo_auxilio', 'MONETARIO')
            ->where('status', 'ENTREGUE')
            ->sum('valor_monetario') ?? 0.0;
    }

    /**
     * Business Logic: Total de membros da família (incluindo responsável)
     */
    public function getTotalMembrosFamilia(): int
    {
        if ($this->tipo_cadastro === TipoCadastroBeneficiario::INDIVIDUAL) {
            return 1;
        }

        return $this->membrosFamilia()->count() + 1; // +1 para incluir o responsável
    }
}
