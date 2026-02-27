<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Models;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\Compdec\Enums\StatusOrgao;
use App\Modules\Compdec\Enums\TipoOrgao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Orgao extends Model
{
    use SoftDeletes;

    protected $table = 'orgaos';

    protected $fillable = [
        'codigo',
        'nome',
        'tipo',
        'municipio_id',
        'orgao_superior_id',
        'status',
        'email',
        'telefone',
        'endereco',
        'responsavel_nome',
        'responsavel_cpf',
        'responsavel_telefone',
        'responsavel_email',
        'latitude',
        'longitude',
        'abrangencia',
        'metadata',
    ];

    protected $casts = [
        'tipo' => TipoOrgao::class,
        'status' => StatusOrgao::class,
        'abrangencia' => 'array',
        'metadata' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    // ========================================
    // RELACIONAMENTOS
    // ========================================

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function orgaoSuperior(): BelongsTo
    {
        return $this->belongsTo(Orgao::class, 'orgao_superior_id');
    }

    public function subordinados(): HasMany
    {
        return $this->hasMany(Orgao::class, 'orgao_superior_id');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'orgao_user')
            ->withPivot('funcao', 'is_principal')
            ->withTimestamps();
    }

    public function coordenadores(): BelongsToMany
    {
        return $this->usuarios()->wherePivot('funcao', 'coordenador');
    }

    // ========================================
    // METODOS DE NEGOCIO
    // ========================================

    /**
     * Valida se pode ter determinado orgao como superior
     *
     * @throws \DomainException
     */
    public function validarOrgaoSuperior(Orgao $superior): void
    {
        if (!$this->tipo->podeSerSubordinadoDe($superior->tipo)) {
            throw new \DomainException(
                "Um {$this->tipo->getLabel()} nao pode ser subordinado a um {$superior->tipo->getLabel()}"
            );
        }

        // COMPDEC deve estar no municipio de abrangencia do REDEC
        if ($this->tipo === TipoOrgao::COMPDEC && $this->municipio_id) {
            if (!$superior->municipioEstaNaAbrangencia($this->municipio_id)) {
                throw new \DomainException(
                    "Municipio fora da abrangencia do orgao superior"
                );
            }
        }
    }

    /**
     * Verifica se municipio esta na abrangencia
     */
    public function municipioEstaNaAbrangencia(int $municipioId): bool
    {
        if (empty($this->abrangencia)) {
            return false;
        }

        return in_array($municipioId, $this->abrangencia, true);
    }

    /**
     * Ativar orgao
     */
    public function ativar(): self
    {
        $this->status = StatusOrgao::ATIVO;
        return $this;
    }

    /**
     * Desativar orgao
     */
    public function desativar(): self
    {
        $this->status = StatusOrgao::INATIVO;
        return $this;
    }

    /**
     * Adiciona usuario ao orgao
     */
    public function adicionarUsuario(User $user, string $funcao = 'agente', bool $isPrincipal = false): void
    {
        if (!$this->status->podeReceberUsuarios()) {
            throw new \DomainException("Orgao nao esta disponivel para receber usuarios");
        }

        $this->usuarios()->attach($user->id, [
            'funcao' => $funcao,
            'is_principal' => $isPrincipal,
        ]);

        if ($isPrincipal) {
            $user->update(['orgao_principal_id' => $this->id]);
        }
    }

    /**
     * Remove usuario do orgao
     */
    public function removerUsuario(User $user): void
    {
        $this->usuarios()->detach($user->id);

        // Se era o orgao principal, limpar
        if ($user->orgao_principal_id === $this->id) {
            $user->update(['orgao_principal_id' => null]);
        }
    }

    /**
     * Obter hierarquia completa (CEDEC - REDEC - COMPDEC)
     */
    public function getHierarquiaCompleta(): array
    {
        $hierarquia = [$this];
        $atual = $this;

        while ($atual->orgaoSuperior) {
            $atual = $atual->orgaoSuperior;
            array_unshift($hierarquia, $atual);
        }

        return $hierarquia;
    }

    /**
     * Obter sigla formatada (ex: COMPDEC-BH, REDEC-MG)
     */
    public function getSiglaFormatada(): string
    {
        $base = $this->tipo->getSigla();

        if ($this->municipio) {
            return "{$base}-{$this->municipio->nome}";
        }

        return $base;
    }

    /**
     * Verifica se tem usuarios vinculados
     */
    public function temUsuarios(): bool
    {
        return $this->usuarios()->count() > 0;
    }

    /**
     * Verifica se tem subordinados
     */
    public function temSubordinados(): bool
    {
        return $this->subordinados()->count() > 0;
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeAtivos($query)
    {
        return $query->where('status', StatusOrgao::ATIVO);
    }

    public function scopePorTipo($query, TipoOrgao $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorMunicipio($query, int $municipioId)
    {
        return $query->where('municipio_id', $municipioId);
    }

    public function scopeComSubordinados($query)
    {
        return $query->with(['subordinados']);
    }

    public function scopeOrfaos($query)
    {
        return $query->whereNull('orgao_superior_id')
            ->where('tipo', '!=', TipoOrgao::CEDEC);
    }

    public function scopePorEstado($query, string $uf)
    {
        return $query->whereHas('municipio', function ($q) use ($uf) {
            $q->where('uf', $uf);
        });
    }

    public function scopeComRelacionamentos($query)
    {
        return $query->with(['municipio', 'orgaoSuperior', 'subordinados', 'usuarios']);
    }
}
