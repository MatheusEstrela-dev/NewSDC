<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models\Relatos;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Support\TrilhaNoProtocoloPai;
use App\Modules\Rat\Models\RatOcorrencia;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RatRelato - Model Base para relatos polimórficos RAT
 *
 * A trilha de acoes e ligada AQUI, na base, e nao no RatOcorrencia, porque o conteudo
 * do RAT mora nos relatos: editar dados gerais, recursos, envolvidos ou vistoria
 * escreve nestas tabelas e nem toca em rat_ocorrencias (RatWriteService::saveDraft
 * ainda faz mass-update no pai, que nao dispara evento algum). Era por isso que editar
 * um RAT nao gerava aviso nenhum para quem o abriu.
 *
 * Como as quatro subclasses herdam daqui, uma declaracao cobre as quatro. O
 * RegistroDeAcao deduplica por requisicao, entao um salvamento que grava dados gerais
 * mais tres recursos e dois envolvidos gera UM card, nao seis.
 */
abstract class RatRelato extends Model
{
    use HasFactory, HasUuids, TrilhaNoProtocoloPai;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'ocorrencia_id',
        'usuario_id',
        'type',
        'data_criacao',
        'data_atualizacao',
    ];

    protected $casts = [
        'data_criacao' => 'datetime',
        'data_atualizacao' => 'datetime',
    ];

    /**
     * Relação: Ocorrência
     */
    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(
            \App\Modules\Rat\Models\RatOcorrencia::class,
            'ocorrencia_id'
        );
    }

    /**
     * Relação: Usuário
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // ─── Trilha de acoes ────────────────────────────────────────────────────

    public function protocoloDaTrilhaClasse(): string
    {
        return RatOcorrencia::class;
    }

    public function protocoloDaTrilhaChave(): int|string|null
    {
        return $this->ocorrencia_id;
    }

    /**
     * Editado, e nao Relacionado: o relato NAO e algo pendurado no RAT, ele e o proprio
     * conteudo do RAT. Salvar um relato e editar o protocolo.
     */
    public function acaoNaTrilhaDoProtocolo(): AcaoTrilha
    {
        return AcaoTrilha::Editado;
    }
}
