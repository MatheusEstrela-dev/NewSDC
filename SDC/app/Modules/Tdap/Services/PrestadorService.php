<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\PrestadorDTO;
use App\Modules\Tdap\Models\Prestador;
use App\Modules\Tdap\Support\Documento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PrestadorService
{
    /**
     * Filtros da listagem aplicados em UM lugar so — listagem, exportacao e
     * cards leem daqui. Antes cada metodo repetia as clausulas e o filtro `uf`,
     * aceito pelo controller, nunca chegava a exportacao.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltros(Builder $query, array $filtros): Builder
    {
        return $query
            ->when(
                array_key_exists('ativo', $filtros) && $filtros['ativo'] !== null && $filtros['ativo'] !== '',
                fn (Builder $q) => $q->where('ativo', filter_var($filtros['ativo'], FILTER_VALIDATE_BOOLEAN)),
            )
            ->when($filtros['uf'] ?? null, fn (Builder $q, $uf) => $q->where('uf', mb_strtoupper((string) $uf)))
            ->when($filtros['search'] ?? null, fn (Builder $q, $termo) => $q->buscar((string) $termo));
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return $this->aplicarFiltros(Prestador::query(), $filtros)
            ->withCount('caminhoes')
            ->orderBy('nome')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Linhas planas para exportacao CSV (respeita os filtros da listagem).
     *
     * Documentos saem MASCARADOS: o CSV e lido por pessoa, e o Excel ainda
     * comeria os zeros a esquerda de um CNPJ so com digitos.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<int, array<string, mixed>>
     */
    public function exportar(array $filtros = []): array
    {
        $rows = $this->aplicarFiltros(Prestador::query(), $filtros)
            ->withCount('caminhoes')
            ->orderBy('nome')
            ->get();

        return $rows->map(fn (Prestador $p) => [
            'CNPJ'          => Documento::cnpj($p->cnpj),
            'Nome'          => $p->nome,
            'Representante' => $p->representante,
            'Email'         => $p->email,
            'Telefone 1'    => Documento::telefone($p->tel1),
            'Telefone 2'    => Documento::telefone($p->tel2),
            'Endereco'      => $p->endereco,
            'Bairro'        => $p->bairro,
            'CEP'           => Documento::cep($p->cep),
            'Cidade'        => $p->cidade,
            'UF'            => $p->uf,
            'Caminhoes'     => (int) $p->caminhoes_count,
            'Situacao'      => $p->ativo ? 'Ativo' : 'Inativo',
        ])->all();
    }

    /**
     * Ficha do prestador com a frota carregada.
     *
     * A frota vem junto porque e o passo seguinte do cadastro: sem caminhao com
     * vistoria vigente o prestador nao entra em cronograma nenhum
     * ({@see CronogramaService::podeAtivar()}).
     */
    public function obter(int $id): Prestador
    {
        return Prestador::query()
            ->withCount('caminhoes')
            ->with(['caminhoes' => fn ($q) => $q
                ->orderBy('placa')
                ->select(['id', 'prestador_id', 'placa', 'marca', 'modelo', 'capacidade_m3', 'ativo']),
            ])
            ->findOrFail($id);
    }

    public function criar(PrestadorDTO $dto): Prestador
    {
        return DB::transaction(fn () => Prestador::create($dto->toArray()));
    }

    public function atualizar(int $id, PrestadorDTO $dto): Prestador
    {
        return DB::transaction(function () use ($id, $dto): Prestador {
            $prestador = Prestador::findOrFail($id);
            $prestador->update($dto->toArray());

            return $prestador->fresh();
        });
    }

    /**
     * Guard de integridade de negocio: caminhao vinculado impede a exclusao.
     *
     * @throws \DomainException quando ha caminhao vinculado
     */
    public function deletar(int $id): bool
    {
        $prestador = Prestador::query()
            ->withCount('caminhoes')
            ->findOrFail($id);

        if ($prestador->caminhoes_count > 0) {
            throw new \DomainException(
                "Prestador {$prestador->nome} possui {$prestador->caminhoes_count} caminhao(oes) vinculado(s). Remova-os antes."
            );
        }

        return (bool) $prestador->delete();
    }

    /**
     * Contadores dos cards, no mesmo recorte da grade.
     *
     * `ativo` fica FORA do recorte de proposito: ele e a propria dimensao dos
     * cards Ativos/Inativos, e considera-lo zeraria o card oposto assim que um
     * fosse clicado — deixando o usuario sem como voltar. Mesma decisao de
     * {@see VistoriaService::obterEstatisticas()}.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, int>
     */
    public function obterEstatisticas(array $filtros = []): array
    {
        $recorte = array_diff_key($filtros, ['ativo' => null]);

        $row = $this->aplicarFiltros(Prestador::query(), $recorte)
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE ativo = TRUE) AS ativos,
                COUNT(*) FILTER (WHERE ativo = FALSE) AS inativos
            ')
            ->first();

        return [
            'total'    => (int) ($row->total ?? 0),
            'ativos'   => (int) ($row->ativos ?? 0),
            'inativos' => (int) ($row->inativos ?? 0),
        ];
    }
}
