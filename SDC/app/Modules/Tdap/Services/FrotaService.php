<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\CaminhaoDTO;
use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Support\VigenciaVistoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FrotaService
{
    /**
     * A frota com a situacao de vistoria de cada veiculo.
     *
     * Caminhao e vistoria viviam em telas separadas, e nenhuma das duas contava
     * a verdade: a de caminhoes anuncia "132 ativos" -- mas `ativo` e flag de
     * CADASTRO -- enquanto quem decide se o veiculo pode rodar e a vistoria
     * vigente, que so 2 tinham. Aqui as duas metades ficam na mesma linha.
     *
     * `vistoriaVigente` e a MESMA relacao que CronogramaService::podeAtivar
     * consulta; `ultimaVistoria` existe para a tela poder dizer "venceu em
     * tal data" em vez de um vazio indistinguivel de "nunca vistoriado".
     *
     * @param  array<string, mixed>  $filtros
     */
    public function listarFrota(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return $this->consultaDaFrota($filtros)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Consulta base da frota: mesmos vinculos e mesmos filtros para a tela e
     * para o CSV. Exportacao que filtra diferente da listagem entrega um
     * arquivo que nao corresponde ao que o usuario estava vendo.
     *
     * Colunas QUALIFICADAS no eager load: latestOfMany monta um self-join sobre
     * tdap_vistorias, e `placa_id` cru fica ambiguo entre as duas pontas -- o
     * Postgres recusa a consulta.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function consultaDaFrota(array $filtros): Builder
    {
        $colunasDaVistoria = [
            'tdap_vistorias.id', 'tdap_vistorias.placa_id', 'tdap_vistorias.data',
            'tdap_vistorias.parecer', 'tdap_vistorias.ficha', 'tdap_vistorias.lacre',
            'tdap_vistorias.nome',
        ];

        return Caminhao::query()
            ->with([
                'prestador:id,nome,cnpj',
                'vistoriaVigente' => fn ($q) => $q->select($colunasDaVistoria),
                'ultimaVistoria'  => fn ($q) => $q->select($colunasDaVistoria),
            ])
            ->withCount('vistorias')
            ->when(
                array_key_exists('ativo', $filtros) && $filtros['ativo'] !== null && $filtros['ativo'] !== '',
                fn ($q) => $q->where('ativo', (bool) $filtros['ativo']),
            )
            ->when($filtros['prestador_id'] ?? null, fn ($q, $id) => $q->doPrestador((int) $id))
            // Filtro exato por CNPJ: quem vem da nota/empenho tem o documento da
            // empresa, nao o id interno dela. Aceita com e sem mascara.
            ->when($filtros['prestador_cnpj'] ?? null, fn ($q, $cnpj) => $q->doCnpj((string) $cnpj))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo))
            ->when($filtros['vistoria'] ?? null, function ($q, $situacao): void {
                match ($situacao) {
                    // Apto = o criterio real de operacao, nao a flag `ativo`.
                    'apto'         => $q->whereHas('vistoriaVigente'),
                    'vencida'      => $q->whereDoesntHave('vistoriaVigente')->whereHas('vistorias'),
                    'sem_vistoria' => $q->whereDoesntHave('vistorias'),
                    default        => null,
                };
            })
            ->orderBy('placa');
    }

    /**
     * A frota inteira para o seletor de "Nova vistoria".
     *
     * Sem paginacao e sem os filtros da tela: quem abre o seletor quer achar UM
     * caminhao entre os 132, e a pagina em que ele caiu na grade -- ou o
     * recorte que estava aplicado -- nao tem relacao nenhuma com isso.
     *
     * Reusa `consultaDaFrota` para a situacao de vistoria sair pela mesma regra
     * da listagem: o badge do seletor e o badge da linha, nao uma segunda
     * leitura do mesmo dado que um dia diverge.
     *
     * So os ativos: vistoriar veiculo que saiu do cadastro nao e um fluxo, e
     * uma lista onde ele aparece so aumenta a chance de escolher o errado.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Caminhao>
     */
    public function listarParaSelecaoDeVistoria(): EloquentCollection
    {
        return $this->consultaDaFrota(['ativo' => true])->get();
    }

    /**
     * Contadores da frota pelo criterio de APTIDAO, em uma consulta.
     *
     * @return array<string, int|float>
     */
    public function obterEstatisticasDaFrota(): array
    {
        // Mesma borda do accessor e do scope -- este SQL cru era a quarta copia
        // da regra de vigencia, e a que ninguem lembrava de atualizar.
        $vigenciaDesde = VigenciaVistoria::dataLimite()->toDateString();
        $aprovada = ParecerVistoria::Aprovada->value;

        $row = Caminhao::query()
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE ativo = TRUE) AS ativos,
                COALESCE(SUM(capacidade_m3) FILTER (WHERE ativo = TRUE), 0) AS capacidade_total_m3
            ')
            ->selectRaw('COUNT(*) FILTER (WHERE EXISTS (
                SELECT 1 FROM tdap_vistorias v
                WHERE v.placa_id = tdap_caminhoes.id AND v.deleted_at IS NULL
                  AND v.parecer = ? AND v.data >= ?
            )) AS aptos', [$aprovada, $vigenciaDesde])
            ->selectRaw('COUNT(*) FILTER (WHERE NOT EXISTS (
                SELECT 1 FROM tdap_vistorias v WHERE v.placa_id = tdap_caminhoes.id AND v.deleted_at IS NULL
            )) AS sem_vistoria', [])
            ->first();

        $total = (int) ($row->total ?? 0);
        $aptos = (int) ($row->aptos ?? 0);
        $semVistoria = (int) ($row->sem_vistoria ?? 0);

        return [
            'total'               => $total,
            'ativos'              => (int) ($row->ativos ?? 0),
            'aptos'               => $aptos,
            // Vencida = tem historico de vistoria, mas nenhuma vigente.
            'vistoria_vencida'    => max(0, $total - $aptos - $semVistoria),
            'sem_vistoria'        => $semVistoria,
            'capacidade_total_m3' => (float) ($row->capacidade_total_m3 ?? 0),
            'placas_duplicadas'   => $this->contarPlacasDuplicadas(),
        ];
    }

    /**
     * Placas repetidas na frota.
     *
     * Nao e metrica de vaidade: sao 15 placas em 30 veiculos, com prestadores
     * diferentes na mesma placa. Numa tela em que a placa e a identidade do
     * registro, esconder isso faz o operador escolher o caminhao errado.
     */
    private function contarPlacasDuplicadas(): int
    {
        return Caminhao::query()
            ->select('placa')
            ->groupBy('placa')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }

    /**
     * Linhas planas para exportacao CSV (respeita os filtros da listagem).
     *
     * @param  array<string, mixed>  $filtros
     * @return array<int, array<string, mixed>>
     */
    public function exportar(array $filtros = []): array
    {
        $rows = $this->consultaDaFrota($filtros)->get();

        return $rows->map(function (Caminhao $c): array {
            $ultima = $c->ultimaVistoria;

            return [
                'Placa'          => $c->placa,
                'Marca'          => $c->marca,
                'Modelo'         => $c->modelo,
                'Cor'            => $c->cor,
                'Ano'            => $c->ano,
                'Capacidade m3'  => number_format((float) $c->capacidade_m3, 2, ',', '.'),
                'Prestador'      => $c->prestador?->nome,
                'CNPJ'           => $c->prestador?->cnpj,
                'Situacao'       => $c->ativo ? 'Ativo' : 'Inativo',
                // Cadastro e aptidao sao coisas diferentes: sem estas colunas o
                // CSV repetia "132 ativos" e escondia que so 2 podiam rodar.
                'Vistoria'          => $this->rotuloDaSituacao($c),
                'Data da vistoria'  => $ultima?->data?->format('d/m/Y'),
                'Parecer'           => $ultima?->parecer?->value,
                'Ficha'             => $ultima?->ficha,
                'Lacre'             => $ultima?->lacre,
                'Vistorias no total' => $c->vistorias_count ?? 0,
                'Observacoes'       => $c->observacoes,
            ];
        })->all();
    }

    /** Mesmos tres estados da tela, escritos por extenso para o CSV. */
    private function rotuloDaSituacao(Caminhao $caminhao): string
    {
        if ($caminhao->vistoriaVigente !== null) {
            return 'Apto';
        }

        return $caminhao->ultimaVistoria !== null ? 'Vencida' : 'Sem vistoria';
    }

    public function obter(int $id): Caminhao
    {
        return Caminhao::query()
            ->with(['prestador:id,nome,cnpj,email'])
            ->findOrFail($id);
    }

    public function criar(CaminhaoDTO $dto): Caminhao
    {
        return DB::transaction(fn () => Caminhao::create($dto->toArray()));
    }

    public function atualizar(int $id, CaminhaoDTO $dto): Caminhao
    {
        return DB::transaction(function () use ($id, $dto): Caminhao {
            $caminhao = Caminhao::findOrFail($id);
            $caminhao->update($dto->toArray());

            return $caminhao->fresh(['prestador']);
        });
    }

    /**
     * Guard de integridade de negocio: caminhao alocado em cronograma vivo
     * (rascunho ou ativo) nao pode sair do cadastro.
     *
     * Sem o guard o delete passava calado -- SoftDeletes nao dispara a FK
     * `restrictOnDelete` de tdap_crono_caminhoes -- e o cronograma ficava com
     * uma alocacao apontando para um caminhao invisivel: a ficha mostrava
     * placa vazia e podeAtivar() acusava vistoria faltando num caminhao que
     * ninguem mais achava na tela.
     *
     * Cronograma ENCERRADO nao bloqueia: ali a alocacao e registro historico
     * (e o proprio cronograma guarda o snapshot em `stored_caminhoes`).
     *
     * @throws \DomainException quando o caminhao esta alocado em cronograma vivo
     */
    public function deletar(int $id): bool
    {
        $caminhao = Caminhao::findOrFail($id);

        $cronogramasVivos = DB::table('tdap_crono_caminhoes as cc')
            ->join('tdap_cronogramas as c', 'c.id', '=', 'cc.cronograma_id')
            ->where('cc.caminhao_id', $id)
            ->whereNull('cc.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereNull('c.encerrado_em')
            ->distinct()
            ->pluck('c.numero');

        if ($cronogramasVivos->isNotEmpty()) {
            throw new \DomainException(sprintf(
                'Caminhao %s esta alocado no(s) cronograma(s) %s. Desaloque-o antes de excluir.',
                $caminhao->placa,
                $cronogramasVivos->implode(', '),
            ));
        }

        return (bool) $caminhao->delete();
    }
}
