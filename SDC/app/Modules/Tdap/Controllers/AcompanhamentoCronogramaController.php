<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Enums\FaseCronograma;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Requests\TramitarCronogramaRequest;
use App\Modules\Tdap\Services\TramitacaoCronogramaService;
use App\Modules\Tdap\Support\EscopoDeLeitura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Acompanhamento de cronograma: painel das 4 fases da tramitacao e as acoes
 * de cada responsavel (enviar, aprovar, devolver, ativar).
 */
class AcompanhamentoCronogramaController extends Controller
{
    public function __construct(
        private readonly TramitacaoCronogramaService $service,
    ) {}

    public function index(Request $request): Response
    {
        // So passa o que valida: um link antigo ou digitado a mao
        // (`vigencia_de=abc`, `search[]=x`) nao pode virar erro 500 no Postgres.
        // Valor invalido e descartado, a tela abre sem ele.
        $filtros = Validator::make($request->only([
            'ata_id', 'lote_id', 'prestador_id', 'municipio_id', 'search', 'minha_fila', 'fase',
            'vigencia_de', 'vigencia_ate', 'caminhoes', 'parado_ha',
        ]), [
            'ata_id'       => ['integer', 'min:1'],
            'lote_id'      => ['integer', 'min:1'],
            'prestador_id' => ['integer', 'min:1'],
            'municipio_id' => ['integer', 'min:1'],
            'search'       => ['string', 'max:100'],
            'minha_fila'   => ['boolean'],
            'fase'         => [Rule::enum(FaseCronograma::class)],
            'vigencia_de'  => ['date_format:Y-m-d'],
            'vigencia_ate' => ['date_format:Y-m-d'],
            'caminhoes'    => ['in:com,sem'],
            'parado_ha'    => ['integer', 'min:0', 'max:3650'],
        ])->valid();
        $escopo = EscopoDeLeitura::municipioId($request->user());

        return Inertia::render('Tdap/Acompanhamento/Index', [
            // Os filtros por fase (busca, ata, lote...) rodam no navegador, sobre os
            // cards de cada coluna; a URL so leva fase e minha_fila.
            'colunas'     => $this->service->painel($filtros, $request->user(), $escopo),
            'filtros'     => $filtros,
        ]);
    }

    /** JSON: alimenta o painel lateral do card e a secao de tramitacao da ficha. */
    public function detalhe(Cronograma $cronograma, Request $request): JsonResponse
    {
        EscopoDeLeitura::garantir($request->user(), $cronograma->municipio_id);

        return response()->json($this->service->detalhe($cronograma, $request->user()));
    }

    public function tramitar(TramitarCronogramaRequest $request, Cronograma $cronograma): RedirectResponse
    {
        EscopoDeLeitura::garantir($request->user(), $cronograma->municipio_id);

        $acao = $request->acao();

        try {
            $this->service->tramitar(
                $cronograma->id,
                $acao,
                $request->user(),
                $request->validated('observacao'),
            );

            return back()->with('success', "Cronograma {$cronograma->numero}: {$acao->label()} concluído.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
