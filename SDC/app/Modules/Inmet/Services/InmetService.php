<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Services;

use App\Modules\Inmet\DTOs\LeituraMeteorologicaDTO;
use App\Modules\Inmet\Models\EstacaoMeteorologica;
use App\Modules\Shared\BaseService;
use Illuminate\Support\Collection;

class InmetService extends BaseService
{
    public function __construct(
        private readonly InmetApiClient $apiClient
    ) {
    }

    public function getLeiturasAtuais(string $uf = 'MG'): Collection
    {
        $leituras = $this->apiClient->getLeiturasRecentes($uf);

        if (empty($leituras)) {
            return collect([]);
        }

        return collect($leituras)
            ->map(fn($data) => LeituraMeteorologicaDTO::fromInmetArray($data))
            ->sortByDesc('precipitacao')
            ->values();
    }

    public function getEstatisticas(Collection $leituras): array
    {
        if ($leituras->isEmpty()) {
            return [
                'total_estacoes' => 0,
                'precipitacao_media' => 0,
                'precipitacao_maxima' => 0,
                'estacoes_com_chuva' => 0,
                'temperatura_media' => 0,
            ];
        }

        return [
            'total_estacoes' => $leituras->count(),
            'precipitacao_media' => round($leituras->avg('precipitacao') ?? 0, 2),
            'precipitacao_maxima' => round($leituras->max('precipitacao') ?? 0, 2),
            'estacoes_com_chuva' => $leituras->where('precipitacao', '>', 0)->count(),
            'temperatura_media' => round($leituras->avg('temperatura') ?? 0, 2),
        ];
    }

    public function findAllEstacoes(): Collection
    {
        return EstacaoMeteorologica::all();
    }

    public function findEstacaoByCodigo(string $codigo): ?EstacaoMeteorologica
    {
        return EstacaoMeteorologica::where('codigo', $codigo)->first();
    }

    public function findEstacoesByUf(string $uf): Collection
    {
        return EstacaoMeteorologica::porUf($uf)->get();
    }

    public function createEstacao(array $data): EstacaoMeteorologica
    {
        return EstacaoMeteorologica::create($data);
    }

    public function updateEstacao(int $id, array $data): EstacaoMeteorologica
    {
        $estacao = EstacaoMeteorologica::findOrFail($id);
        $estacao->update($data);
        return $estacao->refresh();
    }

    public function deleteEstacao(int $id): bool
    {
        $estacao = EstacaoMeteorologica::find($id);

        if (!$estacao) {
            return false;
        }

        return $estacao->delete();
    }

    public function updateCoordinates(string $codigo, float $lat, float $lon): bool
    {
        return EstacaoMeteorologica::where('codigo', $codigo)
            ->update(['latitude' => $lat, 'longitude' => $lon]) > 0;
    }
}
