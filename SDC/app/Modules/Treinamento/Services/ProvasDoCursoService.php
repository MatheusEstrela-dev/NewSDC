<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\Treinamento;

/**
 * Lista de provas do curso para o professor, com a contagem de alunos
 * travados em cada uma (consulta em lote, sem N+1).
 */
class ProvasDoCursoService
{
    public function __construct(private readonly SituacaoProvaService $situacoes) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function resumo(Treinamento $treinamento): array
    {
        $pastas = Modulo::query()->porTreinamento($treinamento->id)->ordenados()->with('itens')->get();

        $itens = [];
        foreach ($pastas as $pasta) {
            foreach ($pasta->itens->where('tipo', TipoItem::PROVA) as $item) {
                $itens[] = [$pasta, $item];
            }
        }

        $provas = Prova::query()
            ->withCount('questoes')
            ->whereIn('modulo_item_id', array_map(fn (array $par) => $par[1]->id, $itens))
            ->get()
            ->keyBy('modulo_item_id');
        $travados = $this->travadosPorProva($treinamento);

        $linhas = [];
        foreach ($itens as [$pasta, $item]) {
            /** @var ModuloItem $item */
            $prova = $provas->get($item->id);
            if ($prova === null) {
                continue;
            }

            $linhas[] = [
                'id' => $prova->id,
                'item_id' => $item->id,
                'pasta' => $pasta->titulo,
                'titulo' => $item->titulo,
                'escala_nota' => Prova::ESCALA_NOTA,
                'nota_minima' => $prova->nota_minima,
                'max_tentativas' => $prova->max_tentativas,
                'total_questoes' => (int) $prova->questoes_count,
                'travados' => $travados[$prova->id] ?? 0,
            ];
        }

        return $linhas;
    }

    /**
     * @return array<int, int> alunos travados por prova_id
     */
    private function travadosPorProva(Treinamento $treinamento): array
    {
        $ids = Inscricao::query()
            ->porTreinamento($treinamento->id)
            ->where('status', StatusInscricao::APROVADA->value)
            ->pluck('id')
            ->all();

        $contagem = [];
        foreach ($this->situacoes->porInscricoes($treinamento->id, $ids) as $situacoes) {
            foreach ($situacoes as $situacao) {
                if ($situacao->travada()) {
                    $contagem[$situacao->provaId] = ($contagem[$situacao->provaId] ?? 0) + 1;
                }
            }
        }

        return $contagem;
    }
}
