<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Services;

use App\Models\User;
use App\Policies\NotaTecnicaPolicy;
use App\Modules\NotasTecnicas\DTOs\NotaTecnicaFiltroDTO;
use App\Modules\NotasTecnicas\Enums\NotaTecnicaStatus;
use App\Modules\NotasTecnicas\Models\NotaTecnica;
use App\Modules\NotasTecnicas\Models\NotaTecnicaCategoria;
use App\Modules\NotasTecnicas\Models\NotaTecnicaLeitura;
use App\Modules\NotasTecnicas\Models\NotaTecnicaVersao;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Leitura do workspace: arvore de categorias, lista de notas, contadores dos
 * cards e o detalhe da nota aberta no leitor.
 *
 * Toda consulta parte de visiveis(): rascunho so existe para quem edita.
 */
final class NotaTecnicaConsultaService
{
    /**
     * Id da versao corrente da nota da linha externa. A corrente e a mais
     * recente; leitura e por versao, entao "lida" compara contra ela.
     */
    private const VERSAO_ATUAL_SQL = '(select v.id from nota_tecnica_versoes v'
        . ' where v.nota_tecnica_id = notas_tecnicas.id'
        . ' order by v.created_at desc, v.id desc limit 1)';

    public function __construct(private readonly NotaTecnicaColecaoService $colecoes) {}

    public function listar(NotaTecnicaFiltroDTO $filtro, User $user): LengthAwarePaginator
    {
        $query = $this->visiveis($user)
            ->select('notas_tecnicas.*')
            ->selectRaw(
                'exists(select 1 from nota_tecnica_leituras l where l.user_id = ?'
                . ' and l.nota_tecnica_versao_id = ' . self::VERSAO_ATUAL_SQL . ') as lida',
                [$user->id],
            )
            ->selectRaw($this->colecoes->favoritaSql() . ' as favorita', [$user->id])
            ->with([
                'categoria:id,nome',
                // Colunas qualificadas: o ofMany() junta a tabela com ela mesma, e
                // `id` solto fica ambiguo. Lista explicita para nao trazer conteudo_texto.
                'versaoAtual' => fn ($q) => $q->select([
                    'nota_tecnica_versoes.id',
                    'nota_tecnica_versoes.nota_tecnica_id',
                    'nota_tecnica_versoes.numero',
                    'nota_tecnica_versoes.created_at',
                ]),
            ])
            ->withCount('versoes')
            ->orderByDesc('codigo');

        $this->aplicarStatus($query, $filtro->status);

        if ($filtro->busca !== null) {
            $this->aplicarBusca($query, $filtro->busca);
        }

        if ($filtro->categoriaId !== null) {
            $query->whereIn('notas_tecnicas.categoria_id', $this->idsDaSubarvore($filtro->categoriaId));
        }

        if ($filtro->naoLidas) {
            $this->apenasNaoLidas($query, $user);
        }

        // Por ultimo: recentes troca a ordenacao padrao por codigo.
        $this->colecoes->aplicar($query, $filtro->colecao, $user);

        return $query->paginate($filtro->perPage)->withQueryString();
    }

    /**
     * Contadores dos cards, que tambem sao os filtros rapidos da lista.
     *
     * @return array{total: int, nao_lidas: int, revogadas: int, rascunhos: ?int}
     */
    public function estatisticas(User $user): array
    {
        // Uma consulta so, com FILTER por card. Os limites de cada contador sao os
        // mesmos de aplicarStatus() e apenasNaoLidas(), que filtram a lista.
        $linha = $this->visiveis($user)
            ->selectRaw(
                'count(*) filter (where status <> ?) as total,'
                . ' count(*) filter (where status = ? and ' . self::VERSAO_ATUAL_SQL . ' is not null'
                . ' and not exists(select 1 from nota_tecnica_leituras l where l.user_id = ?'
                . ' and l.nota_tecnica_versao_id = ' . self::VERSAO_ATUAL_SQL . ')) as nao_lidas,'
                . ' count(*) filter (where status = ?) as revogadas,'
                . ' count(*) filter (where status = ?) as rascunhos',
                [
                    NotaTecnicaStatus::Revogada->value,
                    NotaTecnicaStatus::Publicada->value,
                    $user->id,
                    NotaTecnicaStatus::Revogada->value,
                    NotaTecnicaStatus::Rascunho->value,
                ],
            )
            ->toBase()
            ->first();

        return [
            'total' => (int) $linha->total,
            'nao_lidas' => (int) $linha->nao_lidas,
            'revogadas' => (int) $linha->revogadas,
            'rascunhos' => NotaTecnicaPolicy::veRascunho($user) ? (int) $linha->rascunhos : null,
        ];
    }

    /**
     * Contadores das colecoes pessoais da sidebar. Mesma regra da lista sem
     * status: so notas em vigor visiveis ao usuario (revogadas ficam fora).
     *
     * @return array{favoritas: int, recentes: int}
     */
    public function colecoes(User $user): array
    {
        $base = function () use ($user): Builder {
            $query = $this->visiveis($user);
            $this->aplicarStatus($query, null);

            return $query;
        };

        return [
            'favoritas' => $this->colecoes->contar($base(), NotaTecnicaFiltroDTO::COLECAO_FAVORITAS, $user),
            'recentes' => $this->colecoes->contar($base(), NotaTecnicaFiltroDTO::COLECAO_RECENTES, $user),
        ];
    }

    /**
     * Arvore de pastas com a contagem de notas em vigor de cada uma, ja somando
     * as subpastas -- o numero ao lado da pasta e o que o clique vai mostrar.
     *
     * @return list<array{id: int, nome: string, total: int, filhas: list<array<string, mixed>>}>
     */
    public function arvoreCategorias(User $user): array
    {
        $contagem = $this->visiveis($user);
        $this->aplicarStatus($contagem, null);

        $porCategoria = $contagem
            ->selectRaw('categoria_id, count(*) as total')
            ->groupBy('categoria_id')
            ->pluck('total', 'categoria_id');

        $categorias = NotaTecnicaCategoria::query()
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get(['id', 'parent_id', 'nome']);

        return $this->montarArvore($categorias, null, $porCategoria);
    }

    /**
     * Detalhe da nota aberta: versoes, responsaveis e a versao escolhida (a
     * corrente, salvo quando o leitor abriu uma anterior pela lista).
     *
     * @return array{nota: NotaTecnica, versao: ?NotaTecnicaVersao, lida_em: ?Carbon, favorita: bool, rotas: list<array{rotulo: string, url: string}>}
     */
    public function detalhe(NotaTecnica $nota, ?int $versaoId, User $user): array
    {
        $nota->load([
            'categoria:id,nome,parent_id',
            'categoria.pai:id,nome',
            'elaboradoPor:id,name',
            'aprovadoPor:id,name',
            'versoes:id,nota_tecnica_id,numero,arquivo_nome,arquivo_tamanho,changelog,publicada_em,publicada_por,created_at',
            'versoes.publicadaPor:id,name',
        ]);

        $versao = $versaoId !== null
            ? $nota->versoes->firstWhere('id', $versaoId) ?? $nota->versoes->first()
            : $nota->versoes->first();

        $lidaEm = $versao === null ? null : NotaTecnicaLeitura::query()
            ->where('user_id', $user->id)
            ->where('nota_tecnica_versao_id', $versao->id)
            ->value('lida_em');

        return [
            'nota' => $nota,
            'versao' => $versao,
            'lida_em' => $lidaEm === null ? null : Carbon::parse($lidaEm),
            'favorita' => $this->colecoes->ehFavorita($nota, $user),
            'rotas' => $this->rotasRelacionadas($nota),
        ];
    }

    /** @return Builder<NotaTecnica> */
    private function visiveis(User $user): Builder
    {
        $query = NotaTecnica::query();

        if (! NotaTecnicaPolicy::veRascunho($user)) {
            $query->visiveisParaLeitor();
        }

        return $query;
    }

    /**
     * Sem status pedido: notas em vigor, e rascunhos para quem edita. Revogada
     * so aparece quando pedida explicitamente.
     *
     * @param  Builder<NotaTecnica>  $query
     */
    private function aplicarStatus(Builder $query, ?NotaTecnicaStatus $status): void
    {
        if ($status !== null) {
            $query->where('notas_tecnicas.status', $status->value);

            return;
        }

        $query->where('notas_tecnicas.status', '!=', NotaTecnicaStatus::Revogada->value);
    }

    /**
     * Codigo, titulo e resumo por ILIKE (trigram); o PDF da versao vigente por
     * full-text na config pt_sem_acento (ver a migration do modulo). Quando o termo aparece no PDF, a linha
     * leva um `trecho` com o termo marcado entre [[ ]] -- o front destaca sem
     * v-html.
     *
     * @param  Builder<NotaTecnica>  $query
     */
    private function aplicarBusca(Builder $query, string $busca): void
    {
        // Escapa \ % _ do termo: o usuario busca texto literal, nao curinga LIKE.
        $termo = '%' . addcslashes($busca, '\%_') . '%';
        $noPdf = 'exists(select 1 from nota_tecnica_versoes vb where vb.id = ' . self::VERSAO_ATUAL_SQL
            . " and vb.conteudo_busca @@ websearch_to_tsquery('pt_sem_acento', ?))";

        $query->where(function (Builder $q) use ($termo, $noPdf, $busca): void {
            $q->where('notas_tecnicas.titulo', 'ILIKE', $termo)
                ->orWhere('notas_tecnicas.codigo', 'ILIKE', $termo)
                ->orWhere('notas_tecnicas.resumo', 'ILIKE', $termo)
                ->orWhereRaw($noPdf, [$busca]);
        });

        // ts_headline so roda nas linhas da pagina, e so onde o PDF casou.
        $query->selectRaw(
            "(select ts_headline('pt_sem_acento', left(vt.conteudo_texto, " . NotaTecnicaIndexacaoService::LIMITE_VETOR . "), websearch_to_tsquery('pt_sem_acento', ?),"
            . " 'MaxFragments=1, MaxWords=22, MinWords=10, StartSel=[[, StopSel=]]')"
            . ' from nota_tecnica_versoes vt where vt.id = ' . self::VERSAO_ATUAL_SQL
            . " and vt.conteudo_busca @@ websearch_to_tsquery('pt_sem_acento', ?)) as trecho",
            [$busca, $busca],
        );
    }

    /** @param  Builder<NotaTecnica>  $query */
    private function apenasNaoLidas(Builder $query, User $user): void
    {
        $query->whereRaw(self::VERSAO_ATUAL_SQL . ' is not null')
            ->whereRaw(
                'not exists(select 1 from nota_tecnica_leituras l where l.user_id = ?'
                . ' and l.nota_tecnica_versao_id = ' . self::VERSAO_ATUAL_SQL . ')',
                [$user->id],
            );
    }

    /** @return list<int> */
    private function idsDaSubarvore(int $categoriaId): array
    {
        $filhasPorPai = NotaTecnicaCategoria::query()
            ->whereNotNull('parent_id')
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        $ids = [];
        $pendentes = [$categoriaId];

        while ($pendentes !== []) {
            $id = array_pop($pendentes);
            $ids[] = $id;

            foreach ($filhasPorPai->get($id, collect()) as $filha) {
                $pendentes[] = (int) $filha->id;
            }
        }

        return $ids;
    }

    /**
     * @param  Collection<int, NotaTecnicaCategoria>  $categorias
     * @param  Collection<int|string, mixed>  $porCategoria
     * @return list<array{id: int, nome: string, total: int, filhas: list<array<string, mixed>>}>
     */
    private function montarArvore(Collection $categorias, ?int $paiId, Collection $porCategoria): array
    {
        return $categorias
            ->filter(fn (NotaTecnicaCategoria $c): bool => $c->parent_id === $paiId)
            ->map(function (NotaTecnicaCategoria $categoria) use ($categorias, $porCategoria): array {
                $filhas = $this->montarArvore($categorias, $categoria->id, $porCategoria);

                return [
                    'id' => $categoria->id,
                    'nome' => $categoria->nome,
                    'total' => (int) ($porCategoria[$categoria->id] ?? 0)
                        + array_sum(array_column($filhas, 'total')),
                    'filhas' => $filhas,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Telas do sistema ligadas ao fluxo. Rota que deixou de existir some em
     * silencio, em vez de quebrar a pagina com erro do Ziggy.
     *
     * @return list<array{rotulo: string, url: string}>
     */
    private function rotasRelacionadas(NotaTecnica $nota): array
    {
        return collect($nota->rotas_relacionadas ?? [])
            ->filter(fn (mixed $rota): bool => is_array($rota)
                && isset($rota['nome'], $rota['rotulo'])
                && Route::has((string) $rota['nome']))
            ->map(fn (array $rota): array => [
                'rotulo' => (string) $rota['rotulo'],
                'url' => route((string) $rota['nome']),
            ])
            ->values()
            ->all();
    }
}
