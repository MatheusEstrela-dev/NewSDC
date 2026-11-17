<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Gestao;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\CursoCronograma;
use App\Modules\Treinamento\Models\CursoEvento;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\Turma;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Lado de LEITURA do fluxo do gestor. So consulta: nenhum metodo aqui escreve.
 *
 * Sem estado entre chamadas - o service e singleton sob Octane e guardar o
 * resultado de um curso vazaria para a proxima requisicao. Quem precisar de
 * memo por requisicao faz como IndicadoresConsulta: um objeto por request.
 *
 * O nome do autor vem de users, que vive no banco sdc: e resolvido em UMA
 * consulta por pagina (pluck por lote), nunca uma por linha.
 */
class GestaoCursoConsulta
{
    /**
     * Cartao do curso na tela do gestor.
     *
     * @return array{
     *     id: int,
     *     titulo: string,
     *     tipo: string,
     *     tipo_label: string,
     *     status: string,
     *     status_label: string,
     *     status_exibicao: string,
     *     status_exibicao_label: string,
     *     status_exibicao_variant: string,
     *     situacao: string,
     *     situacao_label: string,
     *     publicado: bool,
     *     publicado_em: string|null,
     *     usa_turma: bool,
     *     data_inicio: string|null,
     *     data_fim: string|null,
     *     numero_vagas: int|null,
     *     vagas_ocupadas: int,
     *     vagas_restantes: int|null,
     *     total_turmas: int,
     *     total_inscricoes: int,
     *     inscricoes_pendentes: int
     * }
     */
    public function resumo(Treinamento $curso): array
    {
        $exibicao = $curso->statusDeExibicao();
        $situacao = $curso->situacaoEfetiva();

        $porStatus = Inscricao::query()
            ->porTreinamento((int) $curso->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $aprovadas = (int) ($porStatus[StatusInscricao::APROVADA->value] ?? 0);
        $pendentes = (int) ($porStatus[StatusInscricao::PENDENTE->value] ?? 0);

        return [
            'id' => (int) $curso->id,
            'titulo' => (string) $curso->titulo,
            'tipo' => $curso->tipo->value,
            'tipo_label' => $curso->tipo->getLabel(),
            'status' => $curso->status->value,
            'status_label' => $curso->status->getLabel(),
            'status_exibicao' => $exibicao->value,
            'status_exibicao_label' => $exibicao->getLabel(),
            'status_exibicao_variant' => $exibicao->getVariant(),
            'situacao' => $situacao->value,
            'situacao_label' => $situacao->getLabel(),
            'publicado' => $curso->estaPublicado(),
            'publicado_em' => $curso->publicado_em?->toIso8601String(),
            'usa_turma' => $curso->usaTurma(),
            'data_inicio' => $curso->data_inicio?->toDateString(),
            'data_fim' => $curso->data_fim?->toDateString(),
            'numero_vagas' => $curso->numero_vagas === null ? null : (int) $curso->numero_vagas,
            'vagas_ocupadas' => $aprovadas,
            'vagas_restantes' => $curso->numero_vagas === null ? null : max(0, (int) $curso->numero_vagas - $aprovadas),
            'total_turmas' => Turma::query()->porTreinamento((int) $curso->id)->count(),
            'total_inscricoes' => (int) $porStatus->sum(),
            'inscricoes_pendentes' => $pendentes,
        ];
    }

    /**
     * Turmas do curso com ocupacao. Uma consulta so: a contagem de aprovadas
     * vem por withCount, nao por COUNT dentro do laco.
     *
     * @return list<array{
     *     id: int,
     *     nome: string,
     *     data_inicio: string,
     *     data_fim: string,
     *     numero_vagas: int,
     *     vagas_ocupadas: int,
     *     vagas_restantes: int,
     *     inscricoes_pendentes: int,
     *     local: string|null,
     *     municipio_id: int|null,
     *     encerrou_inscricoes: bool
     * }>
     */
    public function turmas(Treinamento $curso): array
    {
        return Turma::query()
            ->porTreinamento((int) $curso->id)
            ->withCount([
                'inscricoes as vagas_ocupadas' => fn ($q) => $q->where('status', StatusInscricao::APROVADA->value),
                'inscricoes as inscricoes_pendentes' => fn ($q) => $q->where('status', StatusInscricao::PENDENTE->value),
            ])
            ->orderBy('data_inicio')
            ->get()
            ->map(fn (Turma $turma) => [
                'id' => (int) $turma->getKey(),
                'nome' => (string) $turma->nome,
                'data_inicio' => $turma->data_inicio->toDateString(),
                'data_fim' => $turma->data_fim->toDateString(),
                'numero_vagas' => (int) $turma->numero_vagas,
                'vagas_ocupadas' => (int) $turma->vagas_ocupadas,
                'vagas_restantes' => max(0, (int) $turma->numero_vagas - (int) $turma->vagas_ocupadas),
                'inscricoes_pendentes' => (int) $turma->inscricoes_pendentes,
                'local' => $turma->local,
                'municipio_id' => $turma->municipio_id === null ? null : (int) $turma->municipio_id,
                'encerrou_inscricoes' => $turma->encerrouInscricoes(),
            ])
            ->values()
            ->all();
    }

    /**
     * Itens do cronograma que COMECAM dentro da janela, para o calendario.
     * turma_id null no filtro = todas as turmas e os itens do curso inteiro.
     *
     * @return list<array{
     *     id: int,
     *     titulo: string,
     *     descricao: string|null,
     *     tipo: string,
     *     tipo_label: string,
     *     inicio: string,
     *     fim: string|null,
     *     local: string|null,
     *     turma_id: int|null,
     *     turma_nome: string|null
     * }>
     */
    public function cronograma(Treinamento $curso, string|\DateTimeInterface $de, string|\DateTimeInterface $ate, ?int $turmaId = null): array
    {
        $inicio = CarbonImmutable::parse($de)->startOfDay();
        $fim = CarbonImmutable::parse($ate)->endOfDay();

        return CursoCronograma::query()
            ->porTreinamento((int) $curso->id)
            ->noIntervalo($inicio, $fim)
            ->when($turmaId !== null, fn ($q) => $q->where(fn ($sub) => $sub->where('turma_id', $turmaId)->orWhereNull('turma_id')))
            ->with('turma:id,nome')
            ->orderBy('inicio')
            ->get()
            ->map(fn (CursoCronograma $item) => [
                'id' => (int) $item->getKey(),
                'titulo' => (string) $item->titulo,
                'descricao' => $item->descricao,
                'tipo' => $item->tipo->value,
                'tipo_label' => $item->tipo->getLabel(),
                'inicio' => $item->inicio->toIso8601String(),
                'fim' => $item->fim?->toIso8601String(),
                'local' => $item->local,
                'turma_id' => $item->turma_id === null ? null : (int) $item->turma_id,
                'turma_nome' => $item->turma?->nome,
            ])
            ->values()
            ->all();
    }

    /**
     * Historico paginado, do mais recente para o mais antigo.
     *
     * Os itens da pagina sao arrays:
     * array{
     *     id: int,
     *     tipo: string,
     *     tipo_label: string,
     *     variant: string,
     *     descricao: string,
     *     dados: array<string, mixed>,
     *     turma_id: int|null,
     *     autor_id: int|null,
     *     autor_nome: string|null,
     *     created_at: string
     * }
     */
    public function historico(Treinamento $curso, int $porPagina = 15, int $pagina = 1): LengthAwarePaginator
    {
        $paginador = CursoEvento::query()
            ->porTreinamento((int) $curso->id)
            ->orderByDesc('id')
            ->paginate($porPagina, ['*'], 'page', $pagina);

        $autores = $this->nomesDosAutores(
            collect($paginador->items())->pluck('autor_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all(),
        );

        $paginador->setCollection(
            $paginador->getCollection()->map(function (CursoEvento $evento) use ($autores) {
                $tipo = $evento->tipoEnum();

                return [
                    'id' => (int) $evento->getKey(),
                    'tipo' => (string) $evento->tipo,
                    'tipo_label' => $tipo?->getLabel() ?? (string) $evento->tipo,
                    'variant' => $tipo?->getVariant() ?? 'info',
                    'descricao' => (string) $evento->descricao,
                    'dados' => is_array($evento->dados) ? $evento->dados : [],
                    'turma_id' => $evento->turma_id === null ? null : (int) $evento->turma_id,
                    'autor_id' => $evento->autor_id === null ? null : (int) $evento->autor_id,
                    'autor_nome' => $evento->autor_id === null ? null : ($autores[(int) $evento->autor_id] ?? null),
                    'created_at' => $evento->created_at?->toIso8601String() ?? '',
                ];
            }),
        );

        return $paginador;
    }

    /**
     * users vive no banco sdc: uma consulta por pagina do historico.
     *
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function nomesDosAutores(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return User::query()->whereIn('id', $ids)->pluck('name', 'id')->all();
    }
}
