<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\AjudaHumanitaria\DTOs\PedidoAhDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoDecreto;
use App\Modules\AjudaHumanitaria\Requests\StorePedidoAhRequest;
use App\Modules\AjudaHumanitaria\Resources\PedidoAhIndexResource;
use App\Modules\AjudaHumanitaria\Resources\PedidoAhResource;
use App\Modules\AjudaHumanitaria\Services\PedidoAhService;
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

        $pedido->load(['municipio', 'itensPedido', 'itensLiberados']);

        return Inertia::render('AjudaHumanitaria/Pedidos/Show', [
            'pedido'    => new PedidoAhResource($pedido),
            'canEdit'   => $request->user()?->can('update', $pedido) ?? false,
            'canDelete' => $request->user()?->can('delete', $pedido) ?? false,
        ]);
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
        $porStatus = \App\Modules\AjudaHumanitaria\Models\PedidoAh::query()
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
