<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\BeneficiarioDTO;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Listagem, CRUD e acoes em massa do beneficiario.
 *
 * O legado replicava a regra de visibilidade em quatro metodos do controller
 * (index, rank, aplicarFiltros e menu), com um `codmundv` literal no meio
 * (3104452). Aqui existe um caminho unico: aplicarEscopoDoPerfil().
 */
class BeneficiarioService
{
    /**
     * O legado paginava 400 por pagina e gerava um QR Code por linha dentro
     * de um map(). O QR passou a ser sob demanda e a pagina tem teto.
     */
    public const PORTE_MAXIMO_PAGINA = 100;

    public const PORTE_PADRAO_PAGINA = 25;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(
        PerfilCisterna $perfil,
        array $filtros = [],
        int $porPagina = self::PORTE_PADRAO_PAGINA,
    ): LengthAwarePaginator {
        $porPagina = max(1, min($porPagina, self::PORTE_MAXIMO_PAGINA));

        $query = CisternaBeneficiario::query()
            ->with([
                'municipio:id,nome,uf',
                'comunidade:id,nome',
                'ordemServico:id,nome,lote_id',
                'ordemServico.lote:id,nome',
                // A listagem desenha a coluna de etapas concluidas e o numero de
                // instalacao, e as duas saem desta relacao. Sem carregar,
                // `relationLoaded('vistorias')` e false no resource: a coluna de
                // etapas volta sempre vazia e a de numero desaparece da payload
                // -- a tela mostraria "todas pendentes" para as 791 que tem
                // vistoria de fornecedor concluida, sem erro nenhum aparecer.
                //
                // Restrito as colunas usadas: a vistoria tem 24, e a listagem
                // precisa de 5.
                'vistorias:id,beneficiario_id,etapa,concluida_em,numero_instalacao',
            ]);

        $this->aplicarEscopoDoPerfil($query, $perfil);
        $this->aplicarFiltros($query, $filtros);

        // Ranqueamento e uma ordenacao alternativa, nao um filtro: quando
        // pedido, substitui a ordenacao pedida no cabecalho da tabela.
        if (($filtros['ranqueamento'] ?? false) === true) {
            $query->ranqueados();
        } else {
            $this->aplicarOrdenacao($query, $filtros);
        }

        return $query->paginate($porPagina)->withQueryString();
    }

    /**
     * Colunas que o cabecalho da tabela pode ordenar, mapeadas para a coluna
     * real.
     *
     * A whitelist e obrigatoria, nao defensiva: `sort` vem da query string e
     * iria direto para o ORDER BY. A chave e o nome publico usado pelo front.
     *
     * Municipio e comunidade ficam de fora daqui porque nao sao colunas desta
     * tabela -- sao tratadas por subquery em aplicarOrdenacao(). Lote, numero de
     * instalacao, etapas e ranqueamento nao entram: os dois primeiros saem de
     * relacao a dois saltos, etapas e derivada das vistorias, e ranqueamento ja
     * tem toggle proprio. Cabecalho que promete ordenar e nao ordena e pior que
     * cabecalho que nao promete.
     */
    private const ORDENACAO_PERMITIDA = [
        'nome' => 'nome',
        'cpf' => 'cpf',
        'situacao_analise' => 'situacao_analise',
        'situacao_obra' => 'situacao_obra',
    ];

    private const ORDENACAO_PADRAO = 'nome';

    /**
     * Aplica a ordenacao pedida no cabecalho, caindo no padrao quando o
     * parametro esta ausente ou fora da whitelist.
     *
     * A ordenacao e no banco, e nao no cliente: a listagem e paginada em 25 e
     * ordenar no front reordenaria apenas a pagina visivel.
     *
     * O desempate por `id` mantem a paginacao estavel. Sem ele, linhas com o
     * mesmo valor na coluna ordenada podem trocar de pagina entre requests, e o
     * usuario ve o mesmo beneficiario duas vezes (ou nenhuma) -- com 8 mil
     * cadastros e nome repetido, isso acontece de verdade.
     */
    private function aplicarOrdenacao(Builder $query, array $filtros): void
    {
        $chave = (string) ($filtros['sort'] ?? '');
        $direcao = strtolower((string) ($filtros['direction'] ?? '')) === 'desc' ? 'desc' : 'asc';

        // Relacao ordenada por subquery correlacionada, e nao por join: join
        // aqui mudaria o shape da query paginada (e, com relacao a muitos,
        // duplicaria linha), enquanto a subquery so acrescenta uma expressao ao
        // ORDER BY.
        $subquery = match ($chave) {
            'municipio' => DB::table('municipios')
                ->select('nome')
                ->whereColumn('municipios.id', 'cisterna_beneficiarios.municipio_id')
                ->limit(1),
            'comunidade' => DB::table('cisterna_comunidades')
                ->select('nome')
                ->whereColumn('cisterna_comunidades.id', 'cisterna_beneficiarios.comunidade_id')
                ->limit(1),
            // Etapas nao e coluna nem texto: ordena pelo QUANTO andou, ou seja
            // pelo numero de vistorias concluidas (0 a 3). E o que a coluna
            // desenha com os selos F/C/CD, e o que responde "quem esta parado".
            // A condicao de concluida acompanha CisternaVistoria::concluidas().
            'etapas' => DB::table('cisterna_vistorias')
                ->selectRaw('count(*)')
                ->whereColumn('cisterna_vistorias.beneficiario_id', 'cisterna_beneficiarios.id')
                ->whereNotNull('concluida_em'),
            default => null,
        };

        if ($subquery !== null) {
            // NULLS LAST explicito: beneficiario sem comunidade cadastrada nao
            // deve encabecar a lista ao ordenar por essa coluna. Contagem nao
            // entra aqui: count(*) devolve 0, nunca NULL, e a clausula seria
            // sempre falsa.
            if ($chave !== 'etapas') {
                $query->orderByRaw(
                    '('.$subquery->toSql().') IS NULL',
                    $subquery->getBindings()
                );
            }

            $query->orderBy($subquery, $direcao)->orderBy('id');

            return;
        }

        $coluna = self::ORDENACAO_PERMITIDA[$chave] ?? null;

        if ($coluna === null) {
            $coluna = self::ORDENACAO_PERMITIDA[self::ORDENACAO_PADRAO];
            $direcao = 'asc';
        }

        $query->orderByRaw("{$coluna} IS NULL")
            ->orderBy($coluna, $direcao)
            ->orderBy('id');
    }

    /** Colunas ordenaveis expostas para a interface montar os cabecalhos. */
    public static function colunasOrdenaveis(): array
    {
        return array_merge(array_keys(self::ORDENACAO_PERMITIDA), ['municipio', 'comunidade', 'etapas']);
    }

    public function obter(int $id): CisternaBeneficiario
    {
        return CisternaBeneficiario::query()
            ->with([
                'municipio:id,nome,uf',
                'comunidade:id,nome',
                'ordemServico.lote:id,nome',
                'atendimentosPipa',
                'vistorias',
                'notificacoes',
                'media',
            ])
            ->findOrFail($id);
    }

    public function criar(BeneficiarioDTO $dto): CisternaBeneficiario
    {
        return DB::transaction(function () use ($dto): CisternaBeneficiario {
            $beneficiario = CisternaBeneficiario::create($dto->toArray());

            $this->sincronizarAtendimentosPipa($beneficiario, $dto);

            return $beneficiario->load('atendimentosPipa');
        });
    }

    public function atualizar(CisternaBeneficiario $beneficiario, BeneficiarioDTO $dto): CisternaBeneficiario
    {
        return DB::transaction(function () use ($beneficiario, $dto): CisternaBeneficiario {
            $beneficiario->update($dto->toArray());

            $this->sincronizarAtendimentosPipa($beneficiario, $dto);

            return $beneficiario->fresh(['atendimentosPipa', 'municipio', 'comunidade']);
        });
    }

    public function deletar(CisternaBeneficiario $beneficiario): bool
    {
        return (bool) $beneficiario->delete();
    }

    /**
     * Consulta com escopo de perfil e filtros aplicados, sem paginacao nem eager
     * loading — para o export streamar com lazy().
     *
     * Reaproveita o mesmo escopo da listagem de proposito: no legado o export
     * tambem passava pelo aplicarFiltros(), e nada seria pior que a planilha
     * mostrar mais do que a tela.
     *
     * @param  array<string, mixed>  $filtros
     * @return Builder<CisternaBeneficiario>
     */
    public function consultaParaExport(PerfilCisterna $perfil, array $filtros = []): Builder
    {
        $query = CisternaBeneficiario::query();

        $this->aplicarEscopoDoPerfil($query, $perfil);
        $this->aplicarFiltros($query, $filtros);

        return $query;
    }

    /* Acoes em massa — legado: updateEstadoMass, CisternaController.php:1473 */

    /**
     * @param  array<int, int>  $ids
     */
    public function alocarEmOrdemServico(PerfilCisterna $perfil, array $ids, int $ordemServicoId): int
    {
        return $this->moverParaOrdemServico($perfil, $ids, $ordemServicoId);
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function removerDeOrdemServico(PerfilCisterna $perfil, array $ids): int
    {
        return $this->moverParaOrdemServico($perfil, $ids, null);
    }

    /**
     * `->update()` no query builder NAO dispara observer, entao o
     * CisternaBeneficiarioObserver (Task 11) nao veria a movimentacao em massa
     * -- e alocar em lote e o caminho principal de uso. Sem gravar o log aqui,
     * a timeline da ordem de servico ficaria cega justamente para o evento
     * mais comum.
     *
     * Le o estado anterior antes de atualizar, porque depois do update ele se
     * perde. Uma consulta a mais em troca de um historico correto.
     *
     * @param  array<int, int>  $ids
     */
    private function moverParaOrdemServico(PerfilCisterna $perfil, array $ids, ?int $ordemServicoId): int
    {
        return DB::transaction(function () use ($perfil, $ids, $ordemServicoId): int {
            $anteriores = $this->consultaEmMassa($perfil, $ids)
                ->pluck('ordem_servico_id', 'id');

            $afetados = $this->consultaEmMassa($perfil, $ids)
                ->update(['ordem_servico_id' => $ordemServicoId]);

            $agora = now();
            $registros = [];

            foreach ($anteriores as $id => $anterior) {
                // Quem ja estava onde deveria nao gera evento.
                if ((int) $anterior === (int) $ordemServicoId) {
                    continue;
                }

                $registros[] = [
                    'user_id' => Auth::id(),
                    // 'update', nao 'updated': audit_logs tem CHECK que
                    // aceita apenas insert|update|delete|login|logout.
                    'event' => 'update',
                    'table_name' => 'cisterna_beneficiarios',
                    'row_id' => $id,
                    'old_values' => json_encode(['ordem_servico_id' => $anterior]),
                    'new_values' => json_encode(['ordem_servico_id' => $ordemServicoId]),
                    'created_at' => $agora,
                ];
            }

            if ($registros !== []) {
                DB::table('audit_logs')->insert($registros);
            }

            return $afetados;
        });
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function alterarSituacaoObra(PerfilCisterna $perfil, array $ids, SituacaoObra $situacao): int
    {
        return $this->consultaEmMassa($perfil, $ids)
            ->update(['situacao_obra' => $situacao->value]);
    }

    /**
     * Base das acoes em massa: os ids pedidos, INTERSECTADOS com o escopo do
     * perfil.
     *
     * E aqui que o recorte territorial da acao em massa acontece. A policy
     * updateEmMassa() so verifica a permissao, porque nao ha instancia unica
     * para checar; sem este escopo, um usuario COMPDEC conseguiria mover em
     * lote beneficiarios de outro municipio -- exatamente o que o update() por
     * instancia impede no caminho individual.
     *
     * @param  array<int, int>  $ids
     * @return Builder<CisternaBeneficiario>
     */
    private function consultaEmMassa(PerfilCisterna $perfil, array $ids): Builder
    {
        $query = CisternaBeneficiario::whereIn('id', $ids);

        $this->aplicarEscopoDoPerfil($query, $perfil);

        return $query;
    }

    /**
     * Indicadores do painel. O legado carregava colecoes inteiras com ->get()
     * so para contar, em nove consultas (CisternaController.php:1843-1853).
     * Aqui e uma consulta agregada mais uma por etapa de vistoria.
     *
     * @return array{
     *     total: int,
     *     por_analise: array<string, int>,
     *     por_obra: array<string, int>,
     *     municipios: int,
     *     com_vistoria_fornecedor: int,
     *     com_vistoria_compdec: int,
     *     com_vistoria_cedec: int
     * }
     */
    public function indicadores(PerfilCisterna $perfil): array
    {
        $base = CisternaBeneficiario::query();
        $this->aplicarEscopoDoPerfil($base, $perfil);

        $selects = ['COUNT(*) AS total', 'COUNT(DISTINCT municipio_id) AS municipios'];
        $bindings = [];

        foreach (SituacaoAnalise::valores() as $valor) {
            $selects[] = "COUNT(*) FILTER (WHERE situacao_analise = ?) AS analise_{$valor}";
            $bindings[] = $valor;
        }

        foreach (SituacaoObra::valores() as $valor) {
            $selects[] = "COUNT(*) FILTER (WHERE situacao_obra = ?) AS obra_{$valor}";
            $bindings[] = $valor;
        }

        $linha = $base->clone()->selectRaw(implode(', ', $selects), $bindings)->first();

        $porAnalise = [];
        foreach (SituacaoAnalise::valores() as $valor) {
            $porAnalise[$valor] = (int) ($linha->{'analise_'.$valor} ?? 0);
        }

        $porObra = [];
        foreach (SituacaoObra::valores() as $valor) {
            $porObra[$valor] = (int) ($linha->{'obra_'.$valor} ?? 0);
        }

        return [
            'total' => (int) ($linha->total ?? 0),
            'por_analise' => $porAnalise,
            'por_obra' => $porObra,
            'municipios' => (int) ($linha->municipios ?? 0),
            'com_vistoria_fornecedor' => $this->contarComEtapaConcluida($base, EtapaVistoria::FORNECEDOR),
            'com_vistoria_compdec' => $this->contarComEtapaConcluida($base, EtapaVistoria::COMPDEC),
            'com_vistoria_cedec' => $this->contarComEtapaConcluida($base, EtapaVistoria::CEDEC),
        ];
    }

    /* Internos */

    /**
     * Perfil CEDEC ve todos os municipios habilitados; COMPDEC so o proprio;
     * fornecedor ve qualquer municipio, mas so obras em envio ou instaladas.
     *
     * @param  Builder<CisternaBeneficiario>  $query
     */
    private function aplicarEscopoDoPerfil(Builder $query, PerfilCisterna $perfil): void
    {
        $municipioId = $perfil->municipioId();

        if ($municipioId !== null) {
            $query->doMunicipio($municipioId);
        }

        if ($perfil->eFornecedor()) {
            $query->comSituacaoObra(SituacaoObra::visiveisAoFornecedor());
        }
    }

    /**
     * @param  Builder<CisternaBeneficiario>  $query
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltros(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['municipio_id'] ?? null, fn (Builder $q, $id) => $q->doMunicipio((int) $id))
            ->when($filtros['comunidade_id'] ?? null, function (Builder $q, $ids): void {
                $q->whereIn('comunidade_id', is_array($ids) ? $ids : [$ids]);
            })
            ->when($filtros['situacao_analise'] ?? null, function (Builder $q, $valores): void {
                $q->whereIn('situacao_analise', is_array($valores) ? $valores : [$valores]);
            })
            ->when($filtros['situacao_obra'] ?? null, function (Builder $q, $valores): void {
                $q->whereIn('situacao_obra', is_array($valores) ? $valores : [$valores]);
            })
            ->when($filtros['ordem_servico_id'] ?? null, function (Builder $q, $ids): void {
                $q->whereIn('ordem_servico_id', is_array($ids) ? $ids : [$ids]);
            })
            ->when($filtros['lote_id'] ?? null, function (Builder $q, $loteId): void {
                $q->whereHas('ordemServico', fn (Builder $os) => $os->where('lote_id', (int) $loteId));
            })
            ->when($filtros['cpf'] ?? null, function (Builder $q, $cpf): void {
                $digitos = preg_replace('/\D/', '', (string) $cpf) ?? '';
                $q->where('cpf', 'like', $digitos.'%');
            })
            ->when($filtros['search'] ?? null, fn (Builder $q, $termo) => $q->buscarPorNome((string) $termo))
            // Faixa pela data do cadastro. `whereDate` para a ponta final incluir
            // o dia inteiro: com comparacao de timestamp, tudo que foi cadastrado
            // depois de 00:00 do ultimo dia ficaria fora do recorte.
            ->when($filtros['data_inicio'] ?? null, function (Builder $q, $inicio): void {
                $q->whereDate('cisterna_beneficiarios.created_at', '>=', $inicio);
            })
            ->when($filtros['data_fim'] ?? null, function (Builder $q, $fim): void {
                $q->whereDate('cisterna_beneficiarios.created_at', '<=', $fim);
            })
            ->when(($filtros['atendido_por_pipa'] ?? null) !== null, function (Builder $q) use ($filtros): void {
                $q->where('atendido_por_pipa', (bool) $filtros['atendido_por_pipa']);
            })
            ->when($filtros['numero_instalacao'] ?? null, function (Builder $q, $numero): void {
                $q->whereHas('vistorias', function (Builder $v) use ($numero): void {
                    $v->where('numero_instalacao', (int) $numero);
                });
            });

        // Substitui os tres whereHas aninhados do legado (validFornecedor,
        // validCompdec, validCedec) por um EXISTS sobre o par unico
        // (beneficiario_id, etapa) com concluida_em preenchido.
        if (isset($filtros['etapa_concluida'])) {
            $etapa = EtapaVistoria::tryFrom((string) $filtros['etapa_concluida']);

            if ($etapa !== null) {
                $query->whereHas('vistorias', function (Builder $v) use ($etapa): void {
                    $v->daEtapa($etapa)->concluidas();
                });
            }
        }

        if (isset($filtros['etapa_pendente'])) {
            $etapa = EtapaVistoria::tryFrom((string) $filtros['etapa_pendente']);

            if ($etapa !== null) {
                $query->whereDoesntHave('vistorias', function (Builder $v) use ($etapa): void {
                    $v->daEtapa($etapa)->concluidas();
                });
            }
        }
    }

    /**
     * @param  Builder<CisternaBeneficiario>  $base
     */
    private function contarComEtapaConcluida(Builder $base, EtapaVistoria $etapa): int
    {
        return $base->clone()
            ->whereHas('vistorias', fn (Builder $v) => $v->daEtapa($etapa)->concluidas())
            ->count();
    }

    /**
     * Substitui, nao acumula: o formulario envia sempre o conjunto completo
     * de responsaveis marcados.
     */
    private function sincronizarAtendimentosPipa(CisternaBeneficiario $beneficiario, BeneficiarioDTO $dto): void
    {
        $beneficiario->atendimentosPipa()->delete();

        foreach ($dto->atendimentosPipa() as $linha) {
            $beneficiario->atendimentosPipa()->create($linha);
        }
    }
}
