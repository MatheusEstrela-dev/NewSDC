<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Resources\PortalInscricaoResource;
use App\Modules\Treinamento\Services\TrilhaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MinhasInscricoesController extends Controller
{
    public function __construct(private readonly TrilhaService $trilha) {}

    public function __invoke(): Response
    {
        /** @var Cidadao $cidadao */
        $cidadao = Auth::guard('cidadao')->user();

        $inscricoes = $cidadao->inscricoes()
            ->with(['treinamento', 'certificado'])
            ->orderBy('data_inscricao', 'desc')
            ->paginate(10);

        // Antes do Resource: ele troca os itens da pagina por wrappers.
        $salas = $this->salas($inscricoes->getCollection());

        return Inertia::render('Treinamento/Portal/MinhasInscricoes', [
            'inscricoes' => PortalInscricaoResource::collection($inscricoes),
            'salas' => $salas,
        ]);
    }

    /**
     * Percentual e link da sala das inscricoes EAD aprovadas, por id da inscricao.
     *
     * @param  Collection<int, Inscricao>  $inscricoes
     * @return array<int, array{percentual: float, sala_url: string}>
     */
    private function salas(Collection $inscricoes): array
    {
        return $inscricoes
            ->filter(fn (Inscricao $i) => $i->status === StatusInscricao::APROVADA && $i->treinamento?->isEad() && $i->treinamento->situacaoEfetiva()->permiteAcessoDoAluno())
            ->mapWithKeys(fn (Inscricao $i) => [$i->id => [
                'percentual' => $this->trilha->estado($i)->percentual,
                'sala_url' => route('portal.treinamento.cursos.sala', $i->treinamento->link_publico_slug),
            ]])
            ->all();
    }
}
