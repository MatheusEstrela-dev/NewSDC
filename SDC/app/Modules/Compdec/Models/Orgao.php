<?php

namespace App\Modules\Compdec\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

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
        'abrangencia' => 'array',
        'metadata' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Órgão superior (CEDEC → REDEC → COMPDEC)
     */
    public function orgaoSuperior(): BelongsTo
    {
        return $this->belongsTo(Orgao::class, 'orgao_superior_id');
    }

    /**
     * Órgãos subordinados
     */
    public function orgaosSubordinados(): HasMany
    {
        return $this->hasMany(Orgao::class, 'orgao_superior_id');
    }

    /**
     * Usuários associados a este órgão
     */
    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'orgao_user', 'orgao_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Escopos úteis
     */
    public function scopeCedec($query)
    {
        return $query->where('tipo', 'cedec');
    }

    public function scopeRedec($query)
    {
        return $query->where('tipo', 'redec');
    }

    public function scopeCompdec($query)
    {
        return $query->where('tipo', 'compdec');
    }

    public function scopeAtivo($query)
    {
        return $query->where('status', 'ativo');
    }

    public function scopeComHierarquia($query)
    {
        return $query->with(['orgaoSuperior', 'orgaosSubordinados']);
    }

    /**
     * Métodos auxiliares
     */
    public function isCedec(): bool
    {
        return $this->tipo === 'cedec';
    }

    public function isRedec(): bool
    {
        return $this->tipo === 'redec';
    }

    public function isCompdec(): bool
    {
        return $this->tipo === 'compdec';
    }

    public function isAtivo(): bool
    {
        return $this->status === 'ativo';
    }

    /**
     * Obter caminho hierárquico de órgãos (CEDEC > REDEC > COMPDEC)
     */
    public function getCaminhoHierarquico(): array
    {
        $caminho = [$this];
        $current = $this;

        while ($current->orgaoSuperior) {
            $current = $current->orgaoSuperior;
            array_unshift($caminho, $current);
        }

        return $caminho;
    }

    /**
     * Obter nível na hierarquia (cedec=0, redec=1, compdec=2)
     */
    public function getNivelHierarquico(): int
    {
        return match ($this->tipo) {
            'cedec' => 0,
            'redec' => 1,
            'compdec' => 2,
            default => 3,
        };
    }
}
