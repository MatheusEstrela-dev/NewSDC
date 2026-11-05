<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Tdap\Contracts\PontosCaptacaoDoPmda;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Services\CronogramaService;
use App\Modules\Tdap\Support\EscopoDeLeitura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Consulta, dentro do cronograma, do PMDA que autoriza os pontos.
 *
 * Nao grava nada: o cadastro do plano continua no modulo PMDA. Aqui o TDAP
 * mostra o mesmo registro (plano, pontos ativos e cronogramas ligados a ele).
 */
class CronogramaPmdaController extends Controller
{
    public function __construct(
        private readonly PontosCaptacaoDoPmda $pmda,
        private readonly CronogramaService $cronogramas,
    ) {}

    public function show(Request $request): Response
    {
        $municipioId = (int) $request->integer('municipio_id');
        abort_unless($municipioId > 0, 404);

        EscopoDeLeitura::garantir($request->user(), $municipioId);

        $municipio = Municipio::query()->findOrFail($municipioId, ['id', 'nome', 'uf']);

        $pmdaPlanoId = $request->filled('pmda_plano_id') ? (int) $request->integer('pmda_plano_id') : null;
        abort_if($pmdaPlanoId !== null && $pmdaPlanoId < 1, 404);

        if ($pmdaPlanoId !== null) {
            $this->garantirPlanoVisivel($request, $pmdaPlanoId, $municipioId);
        }

        $resumo = $this->pmda->resumoParaCronograma($municipioId, $pmdaPlanoId);

        $podeAbrirCadastro = false;
        if ($resumo !== null && ! $resumo->excluido) {
            $plano = PmdaPlano::query()->find($resumo->id);
            $podeAbrirCadastro = $plano !== null && ($request->user()?->can('update', $plano) ?? false);
        }

        $cronogramaId = $request->filled('cronograma') ? (int) $request->integer('cronograma') : null;
        $origem = (string) $request->input('origem', '');

        return Inertia::render('Tdap/Cronogramas/Pmda', [
            'municipio' => [
                'id'   => $municipio->id,
                'nome' => $municipio->nome,
                'uf'   => $municipio->uf,
            ],
            'plano' => $resumo?->toArray(),
            'cronogramas' => $resumo === null
                ? []
                : $this->cronogramas->vinculadosAoPmda($resumo->id, $municipioId),
            'podeAbrirCadastro' => $podeAbrirCadastro,
            'voltar' => $this->voltar($origem, $cronogramaId, $municipioId),
            'consulta' => [
                'cronograma' => ($cronogramaId !== null && $cronogramaId > 0) ? $cronogramaId : null,
                'origem'     => in_array($origem, ['create', 'edit', 'show'], true) ? $origem : null,
            ],
        ]);
    }

    /**
     * Plano pedido por id: a rota so exige `tdap.cronogramas.view`, que nao e
     * permissao de ler PMDA. Por aqui o TDAP mostra apenas o plano que ja
     * passou pela CEDEC (aprovado/atendido) ou que algum cronograma do
     * municipio usa. Rascunho, em analise, devolvido etc. so para quem pode ver
     * o plano no proprio PMDA (PmdaPlanoPolicy::view). Fora disso, 404 -- nem
     * confirma que o id existe.
     */
    private function garantirPlanoVisivel(Request $request, int $pmdaPlanoId, int $municipioId): void
    {
        $plano = PmdaPlano::withTrashed()->find($pmdaPlanoId);
        abort_if($plano === null || (int) $plano->municipio_id !== $municipioId, 404);

        $aprovado = in_array($plano->status, [PmdaStatus::APROVADO, PmdaStatus::ATENDIDO], true);

        $ligadoACronograma = DB::table('tdap_cronograma_ponto as cp')
            ->join('tdap_cronogramas as c', 'c.id', '=', 'cp.cronograma_id')
            ->where('cp.pmda_plano_id', $plano->id)
            ->where('c.municipio_id', $municipioId)
            ->whereNull('c.deleted_at')
            ->exists();

        $podeVerNoPmda = $request->user()?->can('view', $plano) ?? false;

        abort_unless($aprovado || $ligadoACronograma || $podeVerNoPmda, 404);
    }

    /**
     * @return array{rotulo: string, rota: string, parametros: array<string, int>}
     */
    private function voltar(string $origem, ?int $cronogramaId, int $municipioId): array
    {
        $temCronograma = $cronogramaId !== null && $cronogramaId > 0;

        if ($origem === 'create' || ($origem === '' && ! $temCronograma)) {
            return [
                'rotulo'     => 'Voltar ao cadastro',
                'rota'       => 'tdap.cronogramas.create',
                'parametros' => [],
            ];
        }

        if (! $temCronograma) {
            return $this->voltarALista();
        }

        $cronograma = Cronograma::query()->find($cronogramaId);
        if ($cronograma === null || (int) $cronograma->municipio_id !== $municipioId) {
            return $this->voltarALista();
        }

        if ($origem === 'edit') {
            return [
                'rotulo'     => 'Voltar à edição',
                'rota'       => 'tdap.cronogramas.edit',
                'parametros' => ['cronograma' => $cronograma->id],
            ];
        }

        return [
            'rotulo'     => 'Voltar ao cronograma',
            'rota'       => 'tdap.cronogramas.show',
            'parametros' => ['cronograma' => $cronograma->id],
        ];
    }

    /**
     * @return array{rotulo: string, rota: string, parametros: array<string, int>}
     */
    private function voltarALista(): array
    {
        return [
            'rotulo'     => 'Voltar aos cronogramas',
            'rota'       => 'tdap.cronogramas.index',
            'parametros' => [],
        ];
    }
}
