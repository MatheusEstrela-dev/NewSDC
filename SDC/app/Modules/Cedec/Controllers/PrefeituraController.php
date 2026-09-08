<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Cedec\DTOs\PrefeituraFiltroDTO;
use App\Modules\Cedec\Enums\Macrorregiao;
use App\Modules\Cedec\Requests\UpdatePrefeituraRequest;
use App\Modules\Cedec\Resources\PrefeituraListaResource;
use App\Modules\Cedec\Services\CedecPrefeituraService;
use App\Modules\Compdec\DTOs\PrefeituraDTO;
use App\Modules\Decretacoes\Services\RedecService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as PaginadorConcreto;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tela estadual de cadastro de prefeituras (CEDEC). A navegacao e por Municipio, nao
 * por Prefeitura: os 853 municipios aparecem, inclusive os que ainda nao tem linha em
 * compdec_prefeituras.
 */
final class PrefeituraController extends Controller
{
    public function __construct(private readonly CedecPrefeituraService $service) {}

    public function index(Request $request): Response
    {
        $filtro = PrefeituraFiltroDTO::fromRequest($request);

        return Inertia::render('Cedec/Prefeituras/Index', [
            'prefeituras' => $this->paraResource($this->service->listar($filtro)),
            'filtros' => $filtro->toArray(),
            // Fechados em closure: numa visita PARCIAL (only: ['prefeituras','filtros'],
            // que a fase 3 usa a cada troca de filtro) o Inertia nunca invoca estes
            // callbacks, entao os agregados sobre todos os municipios nao sao
            // recalculados a cada tecla.
            'estatisticas' => fn () => $this->service->estatisticas(),
            'redecs' => fn () => $this->redecsParaSelect(),
            'macrorregioes' => fn () => Macrorregiao::opcoes(),
        ]);
    }

    public function edit(Municipio $municipio): Response
    {
        $prefeitura = $this->service->obterPorMunicipio($municipio->id);

        return Inertia::render('Cedec/Prefeituras/Edit', [
            'municipio' => [
                'id' => $municipio->id,
                'nome' => $municipio->nome,
                'codigo_ibge' => $municipio->codigo_ibge,
                'uf' => $municipio->uf,
            ],
            'prefeitura' => $prefeitura === null ? null : array_merge(
                $prefeitura->toArray(),
                ['foto_prefeito_url' => $prefeitura->fotoPrefeitoUrl],
            ),
            'indicadores' => $this->service->indicadoresMunicipais($municipio->id),
        ]);
    }

    public function update(UpdatePrefeituraRequest $request, Municipio $municipio): RedirectResponse
    {
        $this->service->upsertPorMunicipio(
            $municipio->id,
            PrefeituraDTO::fromRequest($municipio->id, $request->validated()),
        );

        return back()->with('success', 'Prefeitura atualizada.');
    }

    public function uploadFoto(Request $request, Municipio $municipio): RedirectResponse
    {
        $request->validate([
            'foto' => [
                'required',
                'file',
                'mimes:jpeg,png,webp',
                'max:' . (int) (config('compdec.upload_limits.foto_prefeito', 307200) / 1024),
            ],
        ]);

        $this->service->uploadFoto($municipio->id, $request->file('foto'));

        return back()->with('success', 'Foto do prefeito atualizada.');
    }

    public function removerFoto(Municipio $municipio): RedirectResponse
    {
        $this->service->removerFoto($municipio->id);

        return back()->with('success', 'Foto do prefeito removida.');
    }

    /**
     * O toArray() do paginador ja produz {data, current_page, last_page, per_page,
     * total, from, to, ...}, o formato achatado que o contrato exige. So falta
     * traduzir cada linha crua para a forma do Resource antes de devolver.
     */
    private function paraResource(PaginadorConcreto $paginador): PaginadorConcreto
    {
        $paginador->getCollection()->transform(
            fn (object $linha): array => (new PrefeituraListaResource($linha))->resolve(),
        );

        return $paginador;
    }

    /**
     * Catalogo de REDECs do modulo Decretacoes, mapeado para {value,label}.
     *
     * O Cedec nao duplica o catalogo: reusa o RedecService, que ja mantem dec_redecs
     * cacheado, do mesmo jeito que outros modulos reusam Municipio::catalogo().
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function redecsParaSelect(): array
    {
        return array_map(
            static fn (array $redec): array => ['value' => $redec['id'], 'label' => $redec['label']],
            RedecService::toSelectOptions(),
        );
    }
}
