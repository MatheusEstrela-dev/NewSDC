<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\VistoriaDTO;
use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Support\VigenciaVistoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VistoriaService
{
    /**
     * Filtros da listagem aplicados em UM lugar so — listagem, exportacao e
     * cards usam este metodo (antes cada um repetia as clausulas).
     *
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltros(Builder $query, array $filtros): Builder
    {
        return $query
            ->when($filtros['parecer'] ?? null, fn ($q, $p) => $q->where('parecer', (string) $p))
            ->when($filtros['placa_id'] ?? null, fn ($q, $id) => $q->doCaminhao((int) $id))
            ->when(! empty($filtros['vigente']), fn ($q) => $q->vigente())
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo));
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return $this->aplicarFiltros(Vistoria::query(), $filtros)
            ->with(['caminhao:id,placa,marca,modelo,prestador_id', 'caminhao.prestador:id,nome'])
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Linhas planas para exportacao CSV (respeita os filtros da listagem).
     *
     * @param  array<string, mixed>  $filtros
     * @return array<int, array<string, mixed>>
     */
    public function exportar(array $filtros = []): array
    {
        $rows = $this->aplicarFiltros(Vistoria::query(), $filtros)
            ->with(['caminhao:id,placa,marca,modelo,capacidade_m3,prestador_id', 'caminhao.prestador:id,nome'])
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->get();

        return $rows->map(fn (Vistoria $v) => [
            'Placa'        => $v->caminhao?->placa,
            'Modelo'       => $v->caminhao?->modelo ?? $v->modelo,
            'Prestador'    => $v->caminhao?->prestador?->nome,
            'Data'         => $v->data?->format('d/m/Y'),
            'Vistoriador'  => $v->nome,
            'Edital'       => $v->edital,
            'Ficha'        => $v->ficha,
            'Lacre'        => $v->lacre,
            'Parecer'      => $v->parecer?->label(),
            'Vigente'      => $v->esta_vigente ? 'Sim' : 'Nao',
            'Validade'     => VigenciaVistoria::validoAte($v->data)?->format('d/m/Y'),
            'Capacidade (m3)' => number_format((float) $v->capacidade, 2, ',', '.'),
        ])->all();
    }

    public function obter(int $id): Vistoria
    {
        return Vistoria::query()
            ->with([
                'caminhao:id,placa,marca,modelo,cor,ano,capacidade_m3,prestador_id',
                'caminhao.prestador:id,nome,cnpj',
                'user:id,name,email',
            ])
            ->findOrFail($id);
    }

    public function criar(VistoriaDTO $dto): Vistoria
    {
        return DB::transaction(function () use ($dto): Vistoria {
            $payload = $dto->toArray();
            $payload['user_id'] = Auth::id();

            return Vistoria::create($payload);
        });
    }

    public function atualizar(int $id, VistoriaDTO $dto): Vistoria
    {
        return DB::transaction(function () use ($id, $dto): Vistoria {
            $vistoria = Vistoria::findOrFail($id);
            $vistoria->update($dto->toArray());

            return $vistoria->fresh();
        });
    }

    /**
     * Guard de integridade: vistoria que sustenta cronograma ATIVO nao sai.
     *
     * Mesmo motivo do guard de FrotaService::deletar -- a FK
     * `restrictOnDelete` de tdap_vistorias nunca dispara, porque o delete daqui
     * e soft. So que o dano e outro: nao e um vinculo apontando para registro
     * invisivel, e um caminhao que perde a aptidao no meio de um cronograma em
     * execucao. `podeAtivar` recusaria ATIVAR de novo, mas o cronograma que ja
     * esta rodando continua com o veiculo que o guard reprovaria hoje -- e
     * ninguem e avisado, porque excluir a vistoria nao tocava em nada.
     *
     * Rascunho NAO bloqueia: ali a ativacao ainda vai passar pelo guard e
     * recusar com mensagem propria. Encerrado tambem nao: e registro historico.
     *
     * @throws \DomainException quando a vistoria sustenta cronograma ativo
     */
    public function deletar(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $vistoria = Vistoria::query()->with('caminhao:id,placa')->findOrFail($id);

            $cronogramas = $this->cronogramasAtivosQueDependemDe($vistoria);

            if ($cronogramas->isNotEmpty()) {
                throw new \DomainException(sprintf(
                    'Esta e a vistoria aprovada mais recente do caminhao %s, que esta em execucao no(s) cronograma(s) %s. '
                    .'Registre a vistoria nova antes de excluir esta.',
                    $vistoria->caminhao?->placa ?? "id={$vistoria->placa_id}",
                    $cronogramas->implode(', '),
                ));
            }

            return (bool) $vistoria->delete();
        });
    }

    /**
     * Cronogramas ativos que dependem DESTA vistoria.
     *
     * So a aprovada mais recente do caminhao sustenta alguem: e ela que
     * CronogramaService::caminhoesSemVistoriaAte consulta (via
     * `ultimaVistoriaAprovada`). Excluir uma vistoria antiga, ou uma reprovada,
     * nao muda a cobertura de ninguem -- e bloquear isso seria travar a
     * limpeza de cadastro sem ganho nenhum.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function cronogramasAtivosQueDependemDe(Vistoria $vistoria): Collection
    {
        if ($vistoria->parecer !== ParecerVistoria::Aprovada) {
            return collect();
        }

        $maisRecenteAprovada = Vistoria::query()
            ->where('placa_id', $vistoria->placa_id)
            ->where('parecer', ParecerVistoria::Aprovada->value)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->value('id');

        if ($maisRecenteAprovada !== $vistoria->id) {
            return collect();
        }

        return DB::table('tdap_crono_caminhoes as cc')
            ->join('tdap_cronogramas as c', 'c.id', '=', 'cc.cronograma_id')
            ->where('cc.caminhao_id', $vistoria->placa_id)
            ->whereNull('cc.deleted_at')
            ->whereNull('c.deleted_at')
            ->where('c.ativo', true)
            ->whereNull('c.encerrado_em')
            ->distinct()
            ->pluck('c.numero');
    }

    /**
     * Contadores dos cards.
     *
     * Recebem os filtros de RECORTE (busca e caminhao) para que o card "Total"
     * seja o numero de vistorias listadas, e nao o da base inteira. `parecer` e
     * `vigente` ficam de fora de proposito: sao as proprias dimensoes dos
     * cards, e considera-los zeraria os demais contadores assim que um card
     * fosse clicado — deixando o usuario sem como voltar.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, int>
     */
    public function obterEstatisticas(array $filtros = []): array
    {
        $limite = VigenciaVistoria::dataLimite()->toDateString();

        $recorte = array_diff_key($filtros, ['parecer' => null, 'vigente' => null]);

        $row = $this->aplicarFiltros(Vistoria::query(), $recorte)
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE parecer = ?) AS aprovadas,
                COUNT(*) FILTER (WHERE parecer = ?) AS reprovadas,
                COUNT(*) FILTER (WHERE parecer = ? AND data >= ?) AS vigentes,
                COUNT(*) FILTER (WHERE parecer = ? AND data < ?) AS expiradas
            ', [
                ParecerVistoria::Aprovada->value,
                ParecerVistoria::Reprovada->value,
                ParecerVistoria::Aprovada->value, $limite,
                ParecerVistoria::Aprovada->value, $limite,
            ])
            ->first();

        return [
            'total'      => (int) ($row->total ?? 0),
            'aprovadas'  => (int) ($row->aprovadas ?? 0),
            'reprovadas' => (int) ($row->reprovadas ?? 0),
            'vigentes'   => (int) ($row->vigentes ?? 0),
            'expiradas'  => (int) ($row->expiradas ?? 0),
        ];
    }
}
