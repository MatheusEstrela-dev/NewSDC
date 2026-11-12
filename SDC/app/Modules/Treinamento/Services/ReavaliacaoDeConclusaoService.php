<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\DTOs\EstadoTrilha;
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
     * certificado (uma vez: quem perde a corrida pela gravacao nao enfileira).
     * Devolve true so quando a conclusao foi gravada nesta chamada. Se o
     * enfileiramento falhar depois da gravacao, o certificado fica pendente e
     * o comando treinamento:avaliar-conclusoes o recupera (emissoesPendentes).
     */
    public function avaliar(Inscricao $inscricao): bool
    {
        if ($inscricao->concluido_em !== null) {
            return false;
        }

        $inscricao->loadMissing('treinamento');
        if (! $inscricao->treinamento->isEad() || ! $inscricao->treinamento->situacaoEfetiva()->permiteConclusao()) {
            return false;
        }

        $resultado = $this->conclusao->registrar($inscricao);
        if ($resultado === null || ! $resultado->gravadaAgora) {
            return false;
        }

        EmitirCertificadoDaInscricaoJob::dispatch($inscricao->id);

        return true;
    }

    /**
     * Para quem reage a outra acao (abrir a sala, enviar a prova, concluir item):
     * falhar aqui nunca pode derrubar a requisicao que ja gravou o fato. A
     * avaliacao e idempotente e o comando treinamento:avaliar-conclusoes refaz
     * o que ficou para tras.
     */
    public function avaliarSemDerrubar(Inscricao $inscricao): void
    {
        try {
            $this->avaliar($inscricao);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Ao abrir a sala: so avalia quando o estado da trilha ja calculado indica
     * trilha completa e a conclusao ainda nao foi gravada. Visita normal nao
     * gasta consulta nem transacao.
     */
    public function avaliarSeTrilhaCompleta(Inscricao $inscricao, EstadoTrilha $estado): void
    {
        if ($inscricao->concluido_em === null && $estado->total > 0 && $estado->concluidos >= $estado->total) {
            $this->avaliarSemDerrubar($inscricao);
        }
    }

    /**
     * Inscricoes aprovadas e ainda sem conclusao dos cursos EAD publicados
     * (todos ou um). Curso fora do ar nao conclui ninguem: a conclusao congela
     * e emite certificado, irreversivel. Uma inscricao com erro nao impede as
     * demais (o erro e reportado).
     *
     * @return int quantas inscricoes concluiram nesta passada
     */
    public function doCurso(?int $treinamentoId = null): int
    {
        $concluidas = 0;

        Inscricao::query()
            ->aprovadas()
            ->whereNull('concluido_em')
            ->whereHas('treinamento', fn ($q) => $q->where('tipo', TipoTreinamento::EAD->value)
                ->publicado()->ativo())
            ->when($treinamentoId !== null, fn ($q) => $q->porTreinamento($treinamentoId))
            ->with('treinamento')
            ->chunkById(self::LOTE, function ($lote) use (&$concluidas) {
                foreach ($lote as $inscricao) {
                    try {
                        $concluidas += $this->avaliar($inscricao) ? 1 : 0;
                    } catch (Throwable $e) {
                        report($e);
                    }
                }
            });

        return $concluidas;
    }

    /**
     * Inscricoes EAD ja concluidas, mas sem certificado (a emissao falhou ou
     * nao chegou a ser enfileirada depois da conclusao): reenfileira a emissao,
     * que e idempotente. Certificado cancelado conta como existente.
     *
     * @return int quantas emissoes foram enfileiradas
     */
    public function emissoesPendentes(?int $treinamentoId = null): int
    {
        $enfileiradas = 0;

        Inscricao::query()
            ->aprovadas()
            ->whereNotNull('concluido_em')
            ->whereDoesntHave('certificado')
            ->whereHas('treinamento', fn ($q) => $q->where('tipo', TipoTreinamento::EAD->value))
            ->when($treinamentoId !== null, fn ($q) => $q->porTreinamento($treinamentoId))
            ->chunkById(self::LOTE, function ($lote) use (&$enfileiradas) {
                foreach ($lote as $inscricao) {
                    EmitirCertificadoDaInscricaoJob::dispatch($inscricao->id);
                    $enfileiradas++;
                }
            });

        return $enfileiradas;
    }
}
