<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\ProvaAlternativa;
use App\Modules\Treinamento\Models\ProvaQuestao;
use App\Modules\Treinamento\Models\ProvaResposta;
use App\Modules\Treinamento\Models\ProvaTentativa;
use App\Modules\Treinamento\Services\ProvaService;
use App\Modules\Treinamento\Services\SituacaoProvaService;
use App\Modules\Treinamento\Services\TrilhaService;
use Closure;
use Illuminate\Http\JsonResponse;

/**
 * Apresentador da prova do aluno, o mesmo nos dois escopos. Monta so o que o
 * aluno pode ver: enunciado e texto das alternativas, nunca o gabarito, nem
 * depois do envio (so acertos e total, para nao vazar para a tentativa
 * seguinte).
 */
final class ProvaDoAluno
{
    public function __construct(
        private readonly ProvaService $provas,
        private readonly SituacaoProvaService $situacoes,
        private readonly TrilhaService $trilha,
    ) {}

    /** Inicia ou retoma: 201 com a tentativa nova, 200 ao retomar a aberta. */
    public function iniciar(Inscricao $inscricao, ModuloItem $item): JsonResponse
    {
        return $this->tentar(function () use ($inscricao, $item) {
            $tentativa = $this->provas->iniciar($inscricao, $item);

            return response()->json($this->visao($inscricao, $item), $tentativa->wasRecentlyCreated ? 201 : 200);
        });
    }

    public function responder(Inscricao $inscricao, ModuloItem $item, int $tentativaId, int $questaoId, int $alternativaId): JsonResponse
    {
        return $this->tentar(function () use ($inscricao, $item, $tentativaId, $questaoId, $alternativaId) {
            $this->provas->responder($inscricao, $item, $tentativaId, $questaoId, $alternativaId);

            return response()->json(null, 204);
        });
    }

    public function enviar(Inscricao $inscricao, ModuloItem $item, int $tentativaId): JsonResponse
    {
        return $this->tentar(function () use ($inscricao, $item, $tentativaId) {
            $tentativa = $this->provas->enviar($inscricao, $item, $tentativaId);

            return response()->json([
                'resultado' => $this->resultado($tentativa),
                'situacao' => $this->situacoes->daProva($inscricao, $this->provas->daItem($item))->toArray(),
                'estado' => $this->trilha->estado($inscricao)->toArray(),
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function visao(Inscricao $inscricao, ModuloItem $item): array
    {
        $prova = $this->provas->daItem($item);
        $situacao = $this->situacoes->daProva($inscricao, $prova);
        $aberta = $situacao->tentativaAbertaId === null ? null : ProvaTentativa::query()->find($situacao->tentativaAbertaId);

        return [
            'item' => ['id' => $item->id, 'titulo' => $item->titulo],
            'liberado' => $this->trilha->itemLiberado($inscricao, $item),
            'situacao' => $situacao->toArray(),
            'tentativa' => $aberta === null ? null : $this->tentativa($aberta),
            'ultimo_resultado' => $this->ultimoResultado($inscricao, $prova->id),
        ];
    }

    /**
     * Tentativa aberta na ordem sorteada, com a resposta ja marcada. Questao
     * ou alternativa excluida depois do inicio continua aparecendo: a
     * tentativa e congelada.
     *
     * @return array<string, mixed>
     */
    public function tentativa(ProvaTentativa $tentativa): array
    {
        $ordem = $tentativa->ordem;
        $questoes = ProvaQuestao::withTrashed()->whereIn('id', array_column($ordem, 'questao_id'))->get(['id', 'enunciado'])->keyBy('id');
        $alternativas = ProvaAlternativa::withTrashed()
            ->whereIn('id', array_merge([], ...array_column($ordem, 'alternativas')))
            ->get(['id', 'texto'])
            ->keyBy('id');
        $respostas = ProvaResposta::query()->where('prova_tentativa_id', $tentativa->id)->pluck('prova_alternativa_id', 'prova_questao_id');

        return [
            'id' => $tentativa->id,
            'numero' => $tentativa->numero,
            'iniciada_em' => $tentativa->iniciada_em->toIso8601String(),
            'questoes' => array_map(fn (array $q) => [
                'id' => $q['questao_id'],
                'enunciado' => $questoes[$q['questao_id']]->enunciado,
                'alternativas' => array_map(fn (int $id) => ['id' => $id, 'texto' => $alternativas[$id]->texto], $q['alternativas']),
                'resposta_alternativa_id' => $respostas[$q['questao_id']] ?? null,
            ], $ordem),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resultado(ProvaTentativa $tentativa): array
    {
        return [
            'numero' => $tentativa->numero,
            'nota' => $tentativa->nota,
            'aprovada' => $tentativa->aprovada,
            'acertos' => ProvaResposta::query()->where('prova_tentativa_id', $tentativa->id)->where('correta', true)->count(),
            'total' => count($tentativa->ordem),
            'finalizada_em' => $tentativa->finalizada_em->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function ultimoResultado(Inscricao $inscricao, int $provaId): ?array
    {
        $ultima = ProvaTentativa::query()->enviadas()
            ->where('prova_id', $provaId)->where('inscricao_id', $inscricao->id)
            ->orderByDesc('numero')->first();

        return $ultima === null ? null : $this->resultado($ultima);
    }

    /**
     * Resposta JSON das escritas: erro de regra vira 422 com a mensagem.
     * Bloqueio (403) e nao encontrado (404) seguem o tratamento do framework.
     *
     * @param  Closure(): JsonResponse  $acao
     */
    private function tentar(Closure $acao): JsonResponse
    {
        try {
            return $acao();
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
