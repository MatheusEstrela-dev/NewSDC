<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InmetApiClient
{
    const BASE_URL = 'https://apitempo.inmet.gov.br/estacao';
    const TOKEN_URL = 'https://apitempo.inmet.gov.br/token/estacao';
    const API_TOKEN = 'Q2MyWEhWUmxwalRSN0Z6ZXVOdmhBTTZYZHo3MEhlMTA=Cc2XHVRlpjTR7FzeuNvhAM6Xdz70He10';
    const CACHE_TTL = 900;

    public function getLeiturasRecentes(string $uf = 'MG'): array
    {
        $cacheKey = "inmet_leituras_{$uf}_" . now()->format('YmdH');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($uf) {
            return $this->fetchFromApi($uf);
        });
    }

    private function fetchFromApi(string $uf): array
    {
        Log::info("Usando dados MOCK para UF: {$uf}");

        $mockStations = [
            ['CD_ESTACAO' => 'A501', 'DC_NOME' => 'BELO HORIZONTE', 'UF' => 'MG', 'VL_LATITUDE' => -19.9319, 'VL_LONGITUDE' => -43.9388, 'CHUVA' => 2.4, 'TEM_INS' => 24.5, 'UMD_INS' => 78, 'PRE_INS' => 920.3],
            ['CD_ESTACAO' => 'A502', 'DC_NOME' => 'UBERLANDIA', 'UF' => 'MG', 'VL_LATITUDE' => -18.9186, 'VL_LONGITUDE' => -48.2772, 'CHUVA' => 0.0, 'TEM_INS' => 28.2, 'UMD_INS' => 65, 'PRE_INS' => 935.1],
            ['CD_ESTACAO' => 'A503', 'DC_NOME' => 'JUIZ DE FORA', 'UF' => 'MG', 'VL_LATITUDE' => -21.7642, 'VL_LONGITUDE' => -43.3503, 'CHUVA' => 15.6, 'TEM_INS' => 21.3, 'UMD_INS' => 92, 'PRE_INS' => 912.5],
            ['CD_ESTACAO' => 'A504', 'DC_NOME' => 'MONTES CLAROS', 'UF' => 'MG', 'VL_LATITUDE' => -16.7167, 'VL_LONGITUDE' => -43.8500, 'CHUVA' => 0.0, 'TEM_INS' => 32.1, 'UMD_INS' => 45, 'PRE_INS' => 942.8],
            ['CD_ESTACAO' => 'A505', 'DC_NOME' => 'GOVERNADOR VALADARES', 'UF' => 'MG', 'VL_LATITUDE' => -18.8500, 'VL_LONGITUDE' => -41.9500, 'CHUVA' => 8.2, 'TEM_INS' => 26.8, 'UMD_INS' => 82, 'PRE_INS' => 925.0],
        ];

        $now = now();
        foreach ($mockStations as &$station) {
            $station['DT_MEDICAO'] = $now->format('Y-m-d');
            $station['HR_MEDICAO'] = $now->format('Hi');
        }

        $filtered = collect($mockStations)
            ->filter(fn($s) => strtoupper($s['UF']) === strtoupper($uf))
            ->values()
            ->toArray();

        Log::info("INMET MOCK: " . count($filtered) . " estacoes para {$uf}");

        return $filtered;
    }

    public function getLeituraEstacao(string $codigoEstacao): ?array
    {
        $cacheKey = "inmet_estacao_{$codigoEstacao}_" . now()->format('YmdH');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($codigoEstacao) {
            return $this->fetchStationFromApi($codigoEstacao);
        });
    }

    private function fetchStationFromApi(string $codigoEstacao): ?array
    {
        try {
            $today = date('Y-m-d');
            $url = self::TOKEN_URL . "/{$today}/{$today}/{$codigoEstacao}/" . self::API_TOKEN;

            Log::info("Buscando dados INMET Estacao (Token): {$codigoEstacao}");

            $response = Http::timeout(10)
                ->retry(3, 100)
                ->get($url);

            if ($response->failed()) {
                Log::warning("Falha INMET Estacao {$codigoEstacao}: " . $response->status());
                return null;
            }

            $data = $response->json();

            if (!is_array($data) || empty($data)) {
                return null;
            }

            $lastReading = collect($data)
                ->reverse()
                ->first(function ($reading) {
                    return !empty($reading['TEM_INS']);
                });

            return $lastReading;
        } catch (\Exception $e) {
            Log::error("Erro INMET Estacao {$codigoEstacao}: " . $e->getMessage());
            return null;
        }
    }

    public function clearCache(string $uf = 'MG', ?string $estacao = null): void
    {
        if ($estacao) {
            Cache::forget("inmet_estacao_{$estacao}_" . now()->format('YmdH'));
        }

        $cacheKey = "inmet_leituras_{$uf}_" . now()->format('YmdH');
        Cache::forget($cacheKey);
    }
}
