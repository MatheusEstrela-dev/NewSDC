<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Jobs\EmitirCertificadoDaInscricaoJob;
use App\Modules\Treinamento\Models\Inscricao;
use Throwable;

/**
 * Reavalia a conclusao do curso EAD fora da transicao de item: trilha que
 * ficou completa porque o professor excluiu item ou pasta, ou marcou item como
 * opcional, e inscricoes que terminaram antes da Fase 3. Idempotente e barata:
 * conclusao ja congelada (8.7) encerra no primeiro teste, sem consulta extra.
 */
class ReavaliacaoDeConclusaoService
{
    private const LOTE = 200;

    public function __construct(private readonly ConclusaoService $conclusao) {}

    /**
     * Avalia uma inscricao e, se concluiu agora, enfileira a emissao do
     * certificado. Devolve true so quando a conclusao foi gravada nesta chamada.
     */
    public function avaliar(Inscricao $inscricao): bool
    {
        if ($inscricao->concluido_em !== null) {
            return false;
        }

        $inscricao->loadMissing('treinamento');
        if (! $inscricao->treinamento->isEad()) {
            return false;
        }

        $resultado = $this->conclusao->registrar($inscricao);
        if ($resultado === null) {
            return false;
        }

        EmitirCertificadoDaInscricaoJob::dispatch($inscricao->id);

        return true;
    }

    /**
     * Ao abrir a sala: a reavaliacao nunca pode derrubar a pagina do aluno.
     */
    public function aoAbrirSala(Inscricao $inscricao): void
    {
        try {
            $this->avaliar($inscricao);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Inscricoes aprovadas e ainda sem conclusao dos cursos EAD (todos ou um).
     *
     * @return int quantas inscricoes concluiram nesta passada
     */
    public function doCurso(?int $treinamentoId = null): int
    {
        $concluidas = 0;

        Inscricao::query()
            ->aprovadas()
            ->whereNull('concluido_em')
            ->whereHas('treinamento', fn ($q) => $q->where('tipo', TipoTreinamento::EAD->value))
            ->when($treinamentoId !== null, fn ($q) => $q->porTreinamento($treinamentoId))
            ->with('treinamento')
            ->chunkById(self::LOTE, function ($lote) use (&$concluidas) {
                foreach ($lote as $inscricao) {
                    $concluidas += $this->avaliar($inscricao) ? 1 : 0;
                }
            });

        return $concluidas;
    }
}
