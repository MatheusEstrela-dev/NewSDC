<?php

declare(strict_types=1);

namespace App\Http\Controllers\Compdec;

use App\DTOs\Rat\RatDadosGeraisDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rat\RatDadosGeraisRequest;
use App\Models\Rat\RatOcorrencia;
use App\Models\Rat\Relatos\RatRelatoDadosGerais;
use App\Services\Rat\RatHistoricoService;
use App\Services\Rat\RatRelatoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gerencia os Dados Gerais de uma ocorrência RAT.
 *
 * Responsabilidade única: CRUD de RatRelatoDadosGerais vinculado a uma ocorrência.
 * Segue o Padrão Ouro: Controlador → FormRequest/DTO → Serviço → Model → BD.
 *
 * Métodos públicos: exibir · salvar · atualizar
 */
class RatDadosGeraisController extends Controller
{
    public function __construct(
        private readonly RatRelatoService  $relatoService,
        private readonly RatHistoricoService $historicoService,
    ) {}

    /**
     * Exibe o formulário de dados gerais de uma ocorrência.
     */
    public function show(RatOcorrencia $ocorrencia): Response
    {
        $dadosGerais = $ocorrencia->relatosMorph()
            ->get()
            ->map(fn($relato) => $relato->conteudo)
            ->filter(fn($conteudo) => $conteudo instanceof RatRelatoDadosGerais)
            ->first();

        return Inertia::render('Compdec/Rat/DadosGerais/Exibir', [
            'ocorrencia'  => $ocorrencia,
            'dadosGerais' => $dadosGerais,
        ]);
    }

    /**
     * Salva novos dados gerais e vincula à ocorrência via tabela pivô polimórfica.
     */
    public function store(RatDadosGeraisRequest $request, RatOcorrencia $ocorrencia): RedirectResponse
    {
        $dto         = RatDadosGeraisDTO::fromArray($request->validated());
        $dadosGerais = RatRelatoDadosGerais::create(array_merge(
            $dto->toArray(),
            ['created_by' => auth()->id()]
        ));

        $this->relatoService->attachRelato(
            $ocorrencia,
            RatRelatoDadosGerais::class,
            $dadosGerais->id
        );

        $this->historicoService->log(
            $ocorrencia,
            'dados_gerais.registrado',
            ['relato_id' => $dadosGerais->id]
        );

        return redirect()
            ->route('compdec.rat.show', $ocorrencia->id)
            ->with('success', 'Dados gerais registrados com sucesso!');
    }

    /**
     * Atualiza os dados gerais já registrados de uma ocorrência.
     */
    public function update(
        RatDadosGeraisRequest $request,
        RatOcorrencia         $ocorrencia,
        RatRelatoDadosGerais  $dadosGerais
    ): RedirectResponse {
        $dto = RatDadosGeraisDTO::fromArray($request->validated());

        $dadosGerais->update(array_merge(
            $dto->toArray(),
            ['updated_by' => auth()->id()]
        ));

        $this->historicoService->log(
            $ocorrencia,
            'dados_gerais.atualizado',
            ['relato_id' => $dadosGerais->id]
        );

        return redirect()
            ->route('compdec.rat.show', $ocorrencia->id)
            ->with('sucesso', 'Dados gerais atualizados com sucesso!');
    }
}
