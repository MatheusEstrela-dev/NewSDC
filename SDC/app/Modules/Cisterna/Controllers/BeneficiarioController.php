<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Cisterna\DTOs\BeneficiarioDTO;
use App\Modules\Cisterna\Enums\CoberturaTelhado;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Enums\TipoMoradia;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Requests\AcaoEmMassaRequest;
use App\Modules\Cisterna\Requests\StoreBeneficiarioRequest;
use App\Modules\Cisterna\Requests\UpdateBeneficiarioRequest;
use App\Modules\Cisterna\Resources\BeneficiarioIndexResource;
use App\Modules\Cisterna\Resources\BeneficiarioResource;
use App\Modules\Cisterna\Services\BeneficiarioExportService;
use App\Modules\Cisterna\Services\BeneficiarioService;
use App\Modules\Cisterna\Services\ComunidadeService;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BeneficiarioController extends Controller
{
    public function __construct(
        private readonly BeneficiarioService $service,
        private readonly ComunidadeService $comunidades,
        private readonly BeneficiarioExportService $export,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CisternaBeneficiario::class);

        $perfil = PerfilCisterna::deUsuario($request->user());
        $filtros = $this->filtros($request);

        $beneficiarios = $this->service->listar(
            $perfil,
            $filtros,
            (int) $request->integer('per_page', BeneficiarioService::PORTE_PADRAO_PAGINA),
        );

        return Inertia::render('Cisterna/Beneficiarios/Index', [
            'beneficiarios' => BeneficiarioIndexResource::collection($beneficiarios),
            // Lazy: o painel de indicadores nao precisa recarregar em toda
            // troca de filtro.
            'indicadores' => fn (): array => $this->service->indicadores($perfil),
            'filtros' => $filtros,
            'opcoes' => $this->opcoes(),
            'perfil' => [
                'e_cedec' => $perfil->eCedec(),
                'e_compdec' => $perfil->eCompdec(),
                'e_fornecedor' => $perfil->eFornecedor(),
            ],
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaBeneficiario::class) ?? false,
                'exportar' => $request->user()?->can('export', CisternaBeneficiario::class) ?? false,
                // Flags de coluna de acao. Sao da PERMISSAO, nao da instancia: a
                // policy tambem checa territorio, e a checagem por linha exigiria
                // 25 autorizacoes por pagina. Nao afrouxa nada -- a listagem ja
                // vem com escopo do perfil, e o servidor reavalia na acao.
                'editar' => $request->user()?->can('cisternas.beneficiarios.edit') ?? false,
                'excluir' => $request->user()?->can('cisternas.beneficiarios.delete') ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', CisternaBeneficiario::class);

        return Inertia::render('Cisterna/Beneficiarios/Create', [
            'opcoes' => $this->opcoes(),
        ]);
    }

    public function store(StoreBeneficiarioRequest $request): RedirectResponse
    {
        $beneficiario = $this->service->criar(
            BeneficiarioDTO::deValidados($request->validated())
        );

        $this->anexarArquivos($request, $beneficiario);

        return redirect()
            ->route('cisternas.beneficiarios.show', $beneficiario->id)
            ->with('success', "Beneficiario {$beneficiario->nome} cadastrado com sucesso.");
    }

    public function show(CisternaBeneficiario $beneficiario, Request $request): Response
    {
        $this->authorize('view', $beneficiario);

        $completo = $this->service->obter($beneficiario->id);

        return Inertia::render('Cisterna/Beneficiarios/Show', [
            'beneficiario' => BeneficiarioResource::make($completo)->resolve(),
            'permissoes' => [
                'editar' => $request->user()?->can('update', $beneficiario) ?? false,
                'excluir' => $request->user()?->can('delete', $beneficiario) ?? false,
            ],
        ]);
    }

    public function edit(CisternaBeneficiario $beneficiario): Response
    {
        $this->authorize('update', $beneficiario);

        return Inertia::render('Cisterna/Beneficiarios/Edit', [
            'beneficiario' => BeneficiarioResource::make($this->service->obter($beneficiario->id))->resolve(),
            'opcoes' => array_merge($this->opcoes(), [
                'comunidades' => $this->comunidades->doMunicipio((int) $beneficiario->municipio_id),
            ]),
        ]);
    }

    public function update(UpdateBeneficiarioRequest $request, CisternaBeneficiario $beneficiario): RedirectResponse
    {
        $atualizado = $this->service->atualizar(
            $beneficiario,
            BeneficiarioDTO::deValidados($request->validated())
        );

        $this->anexarArquivos($request, $atualizado);

        return redirect()
            ->route('cisternas.beneficiarios.show', $atualizado->id)
            ->with('success', "Beneficiario {$atualizado->nome} atualizado.");
    }

    public function destroy(CisternaBeneficiario $beneficiario): RedirectResponse
    {
        $this->authorize('delete', $beneficiario);

        $nome = $beneficiario->nome;
        $this->service->deletar($beneficiario);

        return redirect()
            ->route('cisternas.beneficiarios.index')
            ->with('success', "Beneficiario {$nome} excluido.");
    }

    public function acaoEmMassa(AcaoEmMassaRequest $request): RedirectResponse
    {
        $dados = $request->validated();
        $ids = array_map('intval', $dados['ids']);

        // O perfil vai ao service para a acao em massa respeitar o recorte
        // territorial: a policy updateEmMassa() so verifica permissao.
        $perfil = PerfilCisterna::deUsuario($request->user());

        $afetados = match ($dados['acao']) {
            'alocar_em_ordem_servico' => $this->service->alocarEmOrdemServico(
                $perfil,
                $ids,
                (int) $dados['ordem_servico_id']
            ),
            'remover_de_ordem_servico' => $this->service->removerDeOrdemServico($perfil, $ids),
            'alterar_situacao_obra' => $this->service->alterarSituacaoObra(
                $perfil,
                $ids,
                SituacaoObra::from((string) $dados['situacao_obra'])
            ),
        };

        return back()->with('success', "{$afetados} registro(s) atualizado(s).");
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', CisternaBeneficiario::class);

        return $this->export->streamCsv(
            PerfilCisterna::deUsuario($request->user()),
            $this->filtros($request),
        );
    }

    /* Internos */

    /**
     * @return array<string, mixed>
     */
    private function filtros(Request $request): array
    {
        $filtros = $request->only([
            'municipio_id', 'comunidade_id', 'situacao_analise', 'situacao_obra',
            'ordem_servico_id', 'lote_id', 'cpf', 'search', 'numero_instalacao',
            'etapa_concluida', 'etapa_pendente',
            // Faixa de cadastro. E o que o modal de exportacao do projeto envia
            // quando o usuario escolhe "Periodo Especifico": sem ler estas duas
            // chaves, o modal ofereceria um recorte que o export ignora.
            'data_inicio', 'data_fim',
        ]);

        // Booleanos precisam de tratamento explicito: 'false' em query string
        // e uma string verdadeira.
        if ($request->has('atendido_por_pipa')) {
            $filtros['atendido_por_pipa'] = $request->boolean('atendido_por_pipa');
        }

        if ($request->boolean('ranqueamento')) {
            $filtros['ranqueamento'] = true;
        }

        return $filtros;
    }

    /**
     * @return array<string, mixed>
     */
    private function opcoes(): array
    {
        return [
            'tipos_moradia' => TipoMoradia::options(),
            'coberturas_telhado' => CoberturaTelhado::options(),
            'situacoes_analise' => SituacaoAnalise::options(),
            'situacoes_obra' => SituacaoObra::options(),
            'etapas_vistoria' => EtapaVistoria::options(),
            // O formulario precisa dos rotulos, e eles moram no enum:
            // duplicar em Vue faria a lista divergir na primeira mudanca.
            'responsaveis_pipa' => ResponsavelPipa::options(),
            // Somente os municipios habilitados no programa: o legado fazia
            // Municipio::where('at_cisterna', 1) em nove pontos.
            'municipios' => Municipio::habilitadosCisterna(),
        ];
    }

    private function anexarArquivos(Request $request, CisternaBeneficiario $beneficiario): void
    {
        $comprovantes = [
            'comprovante_deficiencia' => 'deficiencia',
            'comprovante_chefia_mulher' => 'chefia_mulher',
            'comprovante_observacao' => 'observacao',
        ];

        foreach ($comprovantes as $campo => $tipo) {
            if (! $request->hasFile($campo)) {
                continue;
            }

            // Substitui o comprovante daquele tipo, se ja houver.
            $beneficiario->getMedia('comprovantes')
                ->filter(fn ($m): bool => $m->getCustomProperty('tipo') === $tipo)
                ->each(fn ($m) => $m->delete());

            $beneficiario->addMedia($request->file($campo))
                ->withCustomProperties(['tipo' => $tipo])
                ->toMediaCollection('comprovantes');
        }

        foreach ((array) $request->input('fotos_imovel', []) as $indice => $foto) {
            $arquivo = $request->file("fotos_imovel.{$indice}.arquivo");

            if ($arquivo === null) {
                continue;
            }

            $beneficiario->addMedia($arquivo)
                ->withCustomProperties([
                    'angulo' => $foto['angulo'] ?? null,
                    'observacao' => $foto['observacao'] ?? null,
                ])
                ->toMediaCollection('fotos_imovel');
        }
    }
}
