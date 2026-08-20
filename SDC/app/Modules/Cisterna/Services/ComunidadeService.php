<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\ComunidadeDTO;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Support\EscopoPerfil;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ComunidadeService
{
    /**
     * @param  array<string, mixed>  $filtros
     *
     * O perfil e o ultimo parametro e opcional: as telas web chamam
     * `listar($filtros, $porPagina)` e continuam sem recorte territorial, que
     * e o comportamento atual delas. A API passa o perfil.
     */
    public function listar(
        array $filtros = [],
        int $porPagina = 50,
        ?PerfilCisterna $perfil = null,
    ): LengthAwarePaginator {
        $query = CisternaComunidade::query()
            ->with('municipio:id,nome,uf')
            // Corrige o defeito C18: o legado contava com
            // leftJoin('sinc_cisterna', 'comunidade', '=', 'comunidade'), sem o
            // municipio, entao os 75 nomes de comunidade que existem em mais de
            // um municipio tinham a contagem somada entre eles.
            ->withCount('beneficiarios')
            ->when($filtros['municipio_id'] ?? null, fn (Builder $q, $id) => $q->where('municipio_id', (int) $id))
            ->when($filtros['search'] ?? null, function (Builder $q, $termo): void {
                $q->where('nome', 'ilike', '%'.trim((string) $termo).'%');
            })
            ->when(($filtros['apenas_ativas'] ?? false) === true, fn (Builder $q) => $q->ativas());

        if ($perfil !== null) {
            EscopoPerfil::aplicarEmMunicipio($query, $perfil);
        }

        return $query
            ->orderBy('nome')
            ->paginate($porPagina)
            ->withQueryString();
    }

    /**
     * Usado pelo select em cascata do formulario: escolhido o municipio, carrega
     * as comunidades dele.
     *
     * @return Collection<int, CisternaComunidade>
     */
    public function doMunicipio(int $municipioId): Collection
    {
        return CisternaComunidade::query()
            ->where('municipio_id', $municipioId)
            ->ativas()
            ->orderBy('nome')
            ->get(['id', 'municipio_id', 'nome']);
    }

    public function criar(ComunidadeDTO $dto): CisternaComunidade
    {
        $this->garantirNomeInedito($dto);

        return CisternaComunidade::create($dto->toArray());
    }

    public function atualizar(CisternaComunidade $comunidade, ComunidadeDTO $dto): CisternaComunidade
    {
        $this->garantirNomeInedito($dto, $comunidade->id);

        $comunidade->update($dto->toArray());

        return $comunidade->fresh('municipio');
    }

    /**
     * @throws ValidationException quando ha beneficiario vinculado
     */
    public function deletar(CisternaComunidade $comunidade): bool
    {
        $vinculados = $comunidade->beneficiarios()->count();

        if ($vinculados > 0) {
            throw ValidationException::withMessages([
                'comunidade' => "Nao e possivel excluir: {$vinculados} beneficiario(s) vinculado(s) a esta comunidade.",
            ]);
        }

        return (bool) $comunidade->delete();
    }

    /**
     * O banco tem UNIQUE (municipio_id, nome); esta checagem existe para o
     * usuario receber mensagem tratada em vez de violacao de constraint.
     *
     * @throws ValidationException
     */
    private function garantirNomeInedito(ComunidadeDTO $dto, ?int $ignorarId = null): void
    {
        $existe = CisternaComunidade::query()
            ->where('municipio_id', $dto->municipioId)
            ->where('nome', $dto->nome)
            ->when($ignorarId !== null, fn (Builder $q) => $q->whereKeyNot($ignorarId))
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'nome' => 'Esta comunidade ja esta cadastrada neste municipio.',
            ]);
        }
    }
}
