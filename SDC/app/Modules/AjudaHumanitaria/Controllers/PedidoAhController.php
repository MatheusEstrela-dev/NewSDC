<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\AjudaHumanitaria\DTOs\PedidoAhDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\EtapaParecer;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use App\Modules\AjudaHumanitaria\Enums\TipoDecreto;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhParecer;
use App\Modules\AjudaHumanitaria\Models\PedidoAhTramite;
use App\Modules\AjudaHumanitaria\Models\PrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaEntrega;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaItem;
use App\Modules\AjudaHumanitaria\Requests\StorePedidoAhRequest;
use App\Modules\AjudaHumanitaria\Resources\PedidoAhIndexResource;
use App\Modules\AjudaHumanitaria\Resources\PedidoAhResource;
use App\Modules\AjudaHumanitaria\Services\AnexoPedidoService;
use App\Modules\AjudaHumanitaria\Services\ItemPedidoService;
use App\Modules\AjudaHumanitaria\Services\ParecerService;
use App\Modules\AjudaHumanitaria\Services\PedidoAhService;
use App\Modules\AjudaHumanitaria\Services\PrestacaoContasService;
use App\Modules\AjudaHumanitaria\Services\TramitacaoService;
use App\Modules\AjudaHumanitaria\Support\MunicipioDoUsuario;
use App\Modules\Compdec\Enums\FuncaoEquipe;
use App\Modules\Compdec\Models\CompdecEquipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pedido de Material de Ajuda Humanitaria.
 *
 * Controller fino: nao contem regra de negocio. Toda decisao vive no dominio e
 * nos servicos; aqui so ha traducao entre requisicao e resposta.
 */
class PedidoAhController extends Controller
{
    public function __construct(
        private readonly PedidoAhService $pedidos,
        private readonly ItemPedidoService $itens,
        private readonly TramitacaoService $tramitacao,
        private readonly ParecerService $pareceres,
        private readonly AnexoPedidoService $anexos,
        private readonly PrestacaoContasService $prestacoesContas,
    ) {}

    public function index(Request $request): Response
    {
        $filtros = $request->only(['municipio_id', 'status', 'ano', 'cobrade_id', 'search']);
        $perPage = (int) $request->integer('per_page', 15);

        $pedidos = $this->pedidos->listar($perPage, $filtros);

        return Inertia::render('AjudaHumanitaria/Pedidos/Index', [
            'pedidos'       => PedidoAhIndexResource::collection($pedidos),
            'estatisticas'  => fn (): array => $this->estatisticas(),
            'filtros'       => $filtros,
            'opcoesStatus'  => StatusPedidoAh::options(),
            'canCreate'     => $request->user()?->can('humanitaria.pedidos.create') ?? false,
            'canEdit'       => $request->user()?->can('humanitaria.pedidos.edit') ?? false,
            'canDelete'     => $request->user()?->can('humanitaria.pedidos.delete') ?? false,
            'canExport'     => $request->user()?->can('humanitaria.pedidos.export') ?? false,
        ]);
    }

    public function create(Request $request): Response
    {
        $municipioId = MunicipioDoUsuario::resolver($request->user());

        return Inertia::render('AjudaHumanitaria/Pedidos/Create', [
            'municipios'     => $this->municipios($municipioId),
            'cobrades'       => $this->cobrades(),
            'tiposDecreto'   => TipoDecreto::options(),
            'municipioFixo'  => $municipioId,
            'coordenador'    => $this->coordenadorDoMunicipio($request->user()),
        ]);
    }

    public function store(StorePedidoAhRequest $request): RedirectResponse
    {
        [$pedido, $erro] = $this->pedidos->abrir(
            PedidoAhDTO::fromRequest($request->validated()),
            $request->user()?->id,
        );

        if ($erro !== null) {
            return back()->withInput()->with('error', $erro);
        }

        return redirect()
            ->route('ajuda-humanitaria.pedidos.show', $pedido->id)
            ->with('success', "Pedido {$pedido->identificador} aberto com sucesso.");
    }

    public function show(Request $request, int $id): Response
    {
        $pedido = $this->pedidos->obter($id);

        $this->authorize('view', $pedido);

        $pedido->load([
            'municipio',
            'itensPedido',
            'itensLiberados',
            'tramites.autor:id,name',
        ]);

        $usuario = $request->user();

        return Inertia::render('AjudaHumanitaria/Pedidos/Show', [
            'pedido'    => new PedidoAhResource($pedido),
            'tramites'  => $this->tramites($pedido),
            'pareceres' => $this->pareceresDoPedido($pedido->id),
            'anexos'    => $this->anexos->listar($pedido->id),
            'prestacao' => $this->prestacaoDoPedido($pedido->id),
            'materiais' => $this->itens->materiaisDisponiveis(),
            'situacoesParecer' => SituacaoParecer::options(),
            'etapasParecer'    => EtapaParecer::options(),

            // Destinos que existem no grafo. A validade final continua sendo do
            // workflow, no momento da transicao: aqui e so a lista de opcoes.
            'destinos' => array_map(
                static fn (StatusPedidoAh $s): array => [
                    'value' => $s->value,
                    'label' => $s->label(),
                ],
                $this->tramitacao->destinosPossiveis($pedido->id),
            ),

            'canEdit'          => $usuario?->can('update', $pedido) ?? false,
            'canDelete'        => $usuario?->can('delete', $pedido) ?? false,
            'canTramitar'      => $usuario?->can('tramitar', $pedido) ?? false,
            'canLiberarItens'  => $usuario?->can('liberarItens', $pedido) ?? false,
            'canParecer'       => $usuario?->can('parecer', $pedido) ?? false,
            'canAnexos'        => $usuario?->can('anexos', $pedido) ?? false,
            'canVerPrestacao'  => $usuario?->can('verPrestacao', $pedido) ?? false,
            'canLancarEntrega' => $usuario?->can('lancarEntrega', $pedido) ?? false,
            'canHomologar'     => $usuario?->can('homologar', $pedido) ?? false,
        ]);
    }

    /**
     * Prestacao de contas com o saldo de cada item.
     *
     * O saldo (RN-18) e o numero que o operador precisa ver: quanto ainda
     * falta entregar antes de a homologacao ser possivel.
     *
     * @return ?array<string, mixed>
     */
    private function prestacaoDoPedido(int $pedidoId): ?array
    {
        $prestacao = PrestacaoConta::with(['itens.entregas'])
            ->where('pedido_ah_id', $pedidoId)
            ->first();

        if ($prestacao === null) {
            return null;
        }

        $itens = $prestacao->itens->map(function (PrestacaoContaItem $item): array {
            $saldo = $this->prestacoesContas->saldoDoItem($item->id);

            return [
                'id'            => $item->id,
                'nome_material' => $item->nome_material,
                'qtd'           => $item->qtd,
                'entregue'      => $item->qtd - $saldo,
                'saldo'         => $saldo,
                'entregas'      => $item->entregas
                    ->map(static fn (PrestacaoContaEntrega $e): array => [
                        'id'                => $e->id,
                        'nome_beneficiario' => $e->nome_beneficiario,
                        'rg'                => $e->rg,
                        'comunidade'        => $e->comunidade,
                        'qtd'               => $e->qtd,
                        'data_entrega'      => $e->data_entrega?->toDateString(),
                    ])
                    ->all(),
            ];
        })->all();

        return [
            'id'           => $prestacao->id,
            'status'       => $prestacao->status?->value,
            'status_label' => $prestacao->status?->label(),
            'data_limite'  => $prestacao->data_limite?->toDateString(),
            'vencida'      => $this->prestacoesContas->estaVencida($prestacao->id),
            'homologada'   => $prestacao->homologado_em !== null,
            'itens'        => $itens,
            'saldo_total'  => array_sum(array_column($itens, 'saldo')),
        ];
    }

    /**
     * RN-10: pareceres emitidos, do mais recente para o mais antigo.
     *
     * @return array<int, array<string, mixed>>
     */
    private function pareceresDoPedido(int $pedidoId): array
    {
        return $this->pareceres->doPedido($pedidoId)
            ->map(static fn (PedidoAhParecer $p): array => [
                'id'             => $p->id,
                'data_parecer'   => $p->data_parecer?->toDateString(),
                'parecer'        => $p->parecer,
                'situacao'       => $p->situacao?->value,
                'situacao_label' => $p->situacao?->label(),
                'favoravel'      => $p->situacao?->ehFavoravel() ?? false,
                'etapa_label'    => $p->etapa?->label(),
                'autor'          => $p->autor?->name,
            ])
            ->all();
    }

    /**
     * RN-14: trilha de tramitacao do pedido.
     *
     * @return array<int, array<string, mixed>>
     */
    private function tramites(PedidoAh $pedido): array
    {
        return $pedido->tramites
            ->map(static fn (PedidoAhTramite $t): array => [
                'id'         => $t->id,
                'de'         => $t->status_anterior?->label(),
                'para'       => $t->status_novo?->label(),
                'para_cor'   => $t->status_novo?->cor(),
                'observacao' => $t->observacao,
                'autor'      => $t->autor?->name,
                'quando'     => $t->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Municipios oferecidos no formulario. Quem tem lotacao municipal so ve o
     * proprio, o que espelha a RN-24 ja na origem em vez de so barrar depois.
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function municipios(?int $municipioFixo): array
    {
        return Municipio::query()
            ->when($municipioFixo !== null, fn ($q) => $q->whereKey($municipioFixo))
            ->orderBy('nome')
            ->get(['id', 'nome', 'uf'])
            ->map(static fn (Municipio $m): array => [
                'value' => (int) $m->id,
                'label' => $m->uf ? "{$m->nome} - {$m->uf}" : (string) $m->nome,
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function cobrades(): array
    {
        return DB::table('dec_cobrade')
            ->orderBy('descricao')
            ->get(['id', 'codigo', 'descricao'])
            ->map(static fn (object $c): array => [
                'value' => (int) $c->id,
                'label' => trim(($c->codigo ? "{$c->codigo} - " : '') . (string) $c->descricao),
            ])
            ->all();
    }

    /**
     * RN-05: os dados do coordenador vem da equipe COMPDEC do orgao do usuario.
     *
     * @return array<string, ?string>
     */
    private function coordenadorDoMunicipio(?User $user): array
    {
        $vazio = ['nome' => null, 'telefone' => null, 'celular' => null, 'email' => null];

        if ($user === null) {
            return $vazio;
        }

        $orgao = MunicipioDoUsuario::orgaoDe($user);

        if ($orgao === null) {
            return $vazio;
        }

        $membro = CompdecEquipe::query()
            ->where('orgao_id', $orgao->id)
            ->where('funcao', FuncaoEquipe::COORDENADOR->value)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->first();

        if ($membro === null) {
            return $vazio;
        }

        return [
            'nome'     => $membro->nome,
            'telefone' => $membro->telefone,
            'celular'  => $membro->celular,
            'email'    => $membro->email,
        ];
    }

    /**
     * Totais por fase do processo, para os cartoes do topo.
     *
     * @return array<string, int>
     */
    private function estatisticas(): array
    {
        $porStatus = PedidoAh::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $contar = static fn (array $statuses): int => array_sum(
            array_map(static fn (int $s): int => (int) ($porStatus[$s] ?? 0), $statuses)
        );

        return [
            'total' => array_sum($porStatus),
            'em_edicao' => $contar([StatusPedidoAh::EdicaoCompdec->value]),
            'em_analise' => $contar([
                StatusPedidoAh::AnaliseDlog->value,
                StatusPedidoAh::AnaliseDiretorDlog->value,
            ]),
            'em_atendimento' => $contar([
                StatusPedidoAh::Aprovado->value,
                StatusPedidoAh::AguardandoDisponibilidade->value,
                StatusPedidoAh::AguardandoRetirada->value,
            ]),
            'atendidos' => $contar([StatusPedidoAh::Atendido->value]),
            'finalizados' => $contar([StatusPedidoAh::Finalizado->value]),
            'encerrados_sem_atendimento' => $contar([
                StatusPedidoAh::Cancelado->value,
                StatusPedidoAh::Reprovado->value,
            ]),
        ];
    }
}
