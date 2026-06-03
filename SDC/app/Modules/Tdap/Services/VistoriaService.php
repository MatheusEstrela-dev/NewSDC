<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\VistoriaDTO;
use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Vistoria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VistoriaService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return Vistoria::query()
            ->with(['caminhao:id,placa,marca,modelo,prestador_id', 'caminhao.prestador:id,nome'])
            ->when($filtros['parecer'] ?? null, fn ($q, $p) => $q->where('parecer', (string) $p))
            ->when($filtros['placa_id'] ?? null, fn ($q, $id) => $q->doCaminhao((int) $id))
            ->when(
                ! empty($filtros['vigente']),
                fn ($q) => $q->vigente(),
            )
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo))
            ->orderByDesc('data')
            ->paginate($perPage)
            ->withQueryString();
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

    public function deletar(int $id): bool
    {
        $vistoria = Vistoria::findOrFail($id);

        return (bool) $vistoria->delete();
    }

    /**
     * @return array<string, int>
     */
    public function obterEstatisticas(): array
    {
        $limite = now()->subMonths(Vistoria::VIGENCIA_MESES)->toDateString();

        $row = Vistoria::query()
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
