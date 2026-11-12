<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\DTOs\SituacaoProva;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\ProvaLiberacao;
use App\Modules\Treinamento\Models\ProvaTentativa;
use Illuminate\Database\Eloquent\Collection;

/**
 * Fonte unica de leitura de "como a inscricao esta na prova" (tentativas,
 * nota final, aprovada, travada), usada pela sala de aula, pela area do
 * professor e pela conclusao do curso. Consultas agregadas, sem N+1.
 */
class SituacaoProvaService
{
    /**
     * Provas de itens e pastas ativos do curso da inscricao.
     *
     * @return array<int, SituacaoProva> por modulo_item_id
     */
    public function daInscricao(Inscricao $inscricao): array
    {
        return $this->porInscricoes($inscricao->treinamento_id, [$inscricao->id])[$inscricao->id];
    }

    /**
     * Mesmo que daInscricao() para varias inscricoes do mesmo curso de uma vez
     * (lista de alunos do professor). Toda inscricao pedida aparece na chave,
     * mesmo sem tentativa.
     *
     * @param  list<int>  $inscricaoIds
     * @return array<int, array<int, SituacaoProva>> por inscricao_id e modulo_item_id
     */
    public function porInscricoes(int $treinamentoId, array $inscricaoIds): array
    {
        $provas = Prova::query()
            ->withCount('questoes')
            ->whereIn('modulo_item_id', ModuloItem::query()
                ->whereIn('modulo_id', Modulo::query()->where('treinamento_id', $treinamentoId)->select('id'))
                ->select('id'))
            ->get();

        return $this->montar($provas, $inscricaoIds);
    }

    public function daProva(Inscricao $inscricao, Prova $prova): SituacaoProva
    {
        $provas = Prova::query()->withCount('questoes')->whereKey($prova->id)->get();

        return $this->montar($provas, [$inscricao->id])[$inscricao->id][$prova->modulo_item_id];
    }

    /**
     * @param  Collection<int, Prova>  $provas
     * @param  list<int>  $inscricaoIds
     * @return array<int, array<int, SituacaoProva>>
     */
    private function montar(Collection $provas, array $inscricaoIds): array
    {
        $resultado = array_fill_keys($inscricaoIds, []);
        if ($inscricaoIds === [] || $provas->isEmpty()) {
            return $resultado;
        }

        $provaIds = $provas->modelKeys();
        $tentativas = $this->tentativas($inscricaoIds, $provaIds);
        $liberadas = $this->liberadas($inscricaoIds, $provaIds);

        foreach ($inscricaoIds as $inscricaoId) {
            foreach ($provas as $prova) {
                $chave = $inscricaoId.':'.$prova->id;
                $t = $tentativas[$chave] ?? null;

                $resultado[$inscricaoId][$prova->modulo_item_id] = new SituacaoProva(
                    provaId: $prova->id,
                    moduloItemId: $prova->modulo_item_id,
                    notaMinima: $prova->nota_minima,
                    totalQuestoes: (int) $prova->questoes_count,
                    tentativasPermitidas: $prova->max_tentativas + ($liberadas[$chave] ?? 0),
                    tentativasEnviadas: (int) ($t->enviadas ?? 0),
                    tentativaAbertaId: isset($t->aberta_id) ? (int) $t->aberta_id : null,
                    notaFinal: isset($t->nota_final) ? (float) $t->nota_final : null,
                    aprovada: (bool) ($t->aprovada ?? false),
                );
            }
        }

        return $resultado;
    }

    /**
     * @param  list<int>  $inscricaoIds
     * @param  list<int>  $provaIds
     * @return array<string, object> por "inscricao_id:prova_id"
     */
    private function tentativas(array $inscricaoIds, array $provaIds): array
    {
        return ProvaTentativa::query()
            ->toBase()
            ->selectRaw(<<<'SQL'
                inscricao_id, prova_id,
                COUNT(*) FILTER (WHERE finalizada_em IS NOT NULL) AS enviadas,
                MAX(nota) AS nota_final,
                COALESCE(BOOL_OR(aprovada), false) AS aprovada,
                MAX(id) FILTER (WHERE finalizada_em IS NULL) AS aberta_id
                SQL)
            ->whereIn('inscricao_id', $inscricaoIds)
            ->whereIn('prova_id', $provaIds)
            ->groupBy('inscricao_id', 'prova_id')
            ->get()
            ->keyBy(fn (object $l) => $l->inscricao_id.':'.$l->prova_id)
            ->all();
    }

    /**
     * @param  list<int>  $inscricaoIds
     * @param  list<int>  $provaIds
     * @return array<string, int> extras liberadas por "inscricao_id:prova_id"
     */
    private function liberadas(array $inscricaoIds, array $provaIds): array
    {
        return ProvaLiberacao::query()
            ->toBase()
            ->selectRaw('inscricao_id, prova_id, SUM(quantidade) AS extras')
            ->whereIn('inscricao_id', $inscricaoIds)
            ->whereIn('prova_id', $provaIds)
            ->groupBy('inscricao_id', 'prova_id')
            ->get()
            ->mapWithKeys(fn (object $l) => [$l->inscricao_id.':'.$l->prova_id => (int) $l->extras])
            ->all();
    }
}
