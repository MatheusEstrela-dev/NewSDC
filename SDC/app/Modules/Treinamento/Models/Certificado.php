<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusCertificado;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificado extends Model
{
    use SoftDeletes;
    use UsaConexaoDeTreinamento;

    protected $table = 'treinamento.certificados';

    protected $fillable = [
        'inscricao_id',
        'treinamento_id',
        'status',
        'emitido_em',
        'criado_por',
        'codigo_verificacao',
        'aproveitamento',
        'reemitido_em',
        'cancelado_em',
        'cancelado_por',
        'cancelamento_motivo',
    ];

    protected $casts = [
        'status' => StatusCertificado::class,
        'emitido_em' => 'datetime',
        'aproveitamento' => 'float',
        'reemitido_em' => 'datetime',
        'cancelado_em' => 'datetime',
        'cancelado_por' => 'integer',
    ];

    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }

    public function treinamento(): BelongsTo
    {
        return $this->belongsTo(Treinamento::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }

    public function estaCancelado(): bool
    {
        return $this->status === StatusCertificado::CANCELADO;
    }

    /**
     * O aluno (servidor ou cidadao) so ve e imprime o certificado depois de
     * enviar a avaliacao do curso, etapa final obrigatoria. Vale tambem para o
     * legado (concluido e emitido antes da regra): o certificado existe, mas so
     * e liberado ao dono depois do feedback. A gestao nao tem esse corte.
     */
    public function liberadoAoAluno(): bool
    {
        return $this->status === StatusCertificado::GERADO && $this->temAvaliacao();
    }

    /** Emitido, mas preso a avaliacao do curso que o aluno ainda nao enviou. */
    public function aguardaAvaliacao(): bool
    {
        return $this->status === StatusCertificado::GERADO && ! $this->temAvaliacao();
    }

    private function temAvaliacao(): bool
    {
        $this->loadMissing('inscricao.avaliacao');

        return $this->inscricao?->avaliacao !== null;
    }

    public function marcarComoGerado(): void
    {
        $this->update([
            'status' => StatusCertificado::GERADO,
            'emitido_em' => now(),
        ]);
    }
}
