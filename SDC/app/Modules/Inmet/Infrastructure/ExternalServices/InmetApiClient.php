<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Infrastructure\ExternalServices;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InmetApiClient
{
    const BASE_URL = 'https://apitempo.inmet.gov.br/estacao';
    const TOKEN_URL = 'https://apitempo.inmet.gov.br/token/estacao';
    const API_TOKEN = 'Q2MyWEhWUmxwalRSN0Z6ZXVOdmhBTTZYZHo3MEhlMTA=Cc2XHVRlpjTR7FzeuNvhAM6Xdz70He10';
    const CACHE_TTL = 900; // 15 minutos

    public function getLeiturasRecentes(string $uf = 'MG'): array
    {
        // Cache key baseado em UF e hora (muda a cada hora)
        $cacheKey = "inmet_leituras_{$uf}_" . now()->format('YmdH');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($uf) {
            return $this->fetchFromApi($uf);
        });
    }

    private function fetchFromApi(string $uf): array
    {
        // MOCK DATA - Remover quando o token INMET estiver válido
        Log::info("Usando dados MOCK para UF: {$uf}");

        $mockStations = [
            ['CD_ESTACAO' => 'A501', 'DC_NOME' => 'BELO HORIZONTE', 'UF' => 'MG', 'VL_LATITUDE' => -19.9319, 'VL_LONGITUDE' => -43.9388, 'CHUVA' => 2.4, 'TEM_INS' => 24.5, 'UMD_INS' => 78, 'PRE_INS' => 920.3],
            ['CD_ESTACAO' => 'A502', 'DC_NOME' => 'UBERLÂNDIA', 'UF' => 'MG', 'VL_LATITUDE' => -18.9186, 'VL_LONGITUDE' => -48.2772, 'CHUVA' => 0.0, 'TEM_INS' => 28.2, 'UMD_INS' => 65, 'PRE_INS' => 935.1],
            ['CD_ESTACAO' => 'A503', 'DC_NOME' => 'JUIZ DE FORA', 'UF' => 'MG', 'VL_LATITUDE' => -21.7642, 'VL_LONGITUDE' => -43.3503, 'CHUVA' => 15.6, 'TEM_INS' => 21.3, 'UMD_INS' => 92, 'PRE_INS' => 912.5],
            ['CD_ESTACAO' => 'A504', 'DC_NOME' => 'MONTES CLAROS', 'UF' => 'MG', 'VL_LATITUDE' => -16.7167, 'VL_LONGITUDE' => -43.8500, 'CHUVA' => 0.0, 'TEM_INS' => 32.1, 'UMD_INS' => 45, 'PRE_INS' => 942.8],
            ['CD_ESTACAO' => 'A505', 'DC_NOME' => 'GOVERNADOR VALADARES', 'UF' => 'MG', 'VL_LATITUDE' => -18.8500, 'VL_LONGITUDE' => -41.9500, 'CHUVA' => 8.2, 'TEM_INS' => 26.8, 'UMD_INS' => 82, 'PRE_INS' => 925.0],
            ['CD_ESTACAO' => 'A506', 'DC_NOME' => 'UBERABA', 'UF' => 'MG', 'VL_LATITUDE' => -19.7500, 'VL_LONGITUDE' => -47.9333, 'CHUVA' => 0.0, 'TEM_INS' => 29.5, 'UMD_INS' => 58, 'PRE_INS' => 938.2],
            ['CD_ESTACAO' => 'A507', 'DC_NOME' => 'IPATINGA', 'UF' => 'MG', 'VL_LATITUDE' => -19.4700, 'VL_LONGITUDE' => -42.5367, 'CHUVA' => 45.3, 'TEM_INS' => 22.4, 'UMD_INS' => 95, 'PRE_INS' => 915.6],
            ['CD_ESTACAO' => 'A508', 'DC_NOME' => 'DIAMANTINA', 'UF' => 'MG', 'VL_LATITUDE' => -18.2500, 'VL_LONGITUDE' => -43.6000, 'CHUVA' => 3.1, 'TEM_INS' => 19.8, 'UMD_INS' => 88, 'PRE_INS' => 890.2],
            ['CD_ESTACAO' => 'A509', 'DC_NOME' => 'LAVRAS', 'UF' => 'MG', 'VL_LATITUDE' => -21.2500, 'VL_LONGITUDE' => -45.0000, 'CHUVA' => 0.0, 'TEM_INS' => 25.6, 'UMD_INS' => 70, 'PRE_INS' => 905.4],
            ['CD_ESTACAO' => 'A510', 'DC_NOME' => 'POÇOS DE CALDAS', 'UF' => 'MG', 'VL_LATITUDE' => -21.7833, 'VL_LONGITUDE' => -46.5667, 'CHUVA' => 22.8, 'TEM_INS' => 18.2, 'UMD_INS' => 94, 'PRE_INS' => 895.8],
            ['CD_ESTACAO' => 'A511', 'DC_NOME' => 'VARGINHA', 'UF' => 'MG', 'VL_LATITUDE' => -21.5500, 'VL_LONGITUDE' => -45.4333, 'CHUVA' => 5.5, 'TEM_INS' => 23.9, 'UMD_INS' => 80, 'PRE_INS' => 908.3],
            ['CD_ESTACAO' => 'A512', 'DC_NOME' => 'PATOS DE MINAS', 'UF' => 'MG', 'VL_LATITUDE' => -18.5833, 'VL_LONGITUDE' => -46.5167, 'CHUVA' => 0.0, 'TEM_INS' => 27.4, 'UMD_INS' => 62, 'PRE_INS' => 932.6],
            ['CD_ESTACAO' => 'A513', 'DC_NOME' => 'TEÓFILO OTONI', 'UF' => 'MG', 'VL_LATITUDE' => -17.8500, 'VL_LONGITUDE' => -41.5000, 'CHUVA' => 68.5, 'TEM_INS' => 24.1, 'UMD_INS' => 98, 'PRE_INS' => 918.9],
            ['CD_ESTACAO' => 'A514', 'DC_NOME' => 'VIÇOSA', 'UF' => 'MG', 'VL_LATITUDE' => -20.7500, 'VL_LONGITUDE' => -42.8833, 'CHUVA' => 12.3, 'TEM_INS' => 22.7, 'UMD_INS' => 86, 'PRE_INS' => 910.5],
            ['CD_ESTACAO' => 'A515', 'DC_NOME' => 'ARAXÁ', 'UF' => 'MG', 'VL_LATITUDE' => -19.5833, 'VL_LONGITUDE' => -46.9500, 'CHUVA' => 0.0, 'TEM_INS' => 26.3, 'UMD_INS' => 68, 'PRE_INS' => 928.1],
            ['CD_ESTACAO' => 'A516', 'DC_NOME' => 'BARBACENA', 'UF' => 'MG', 'VL_LATITUDE' => -21.2167, 'VL_LONGITUDE' => -43.7667, 'CHUVA' => 1.8, 'TEM_INS' => 20.5, 'UMD_INS' => 84, 'PRE_INS' => 898.7],
            ['CD_ESTACAO' => 'A517', 'DC_NOME' => 'MURIAÉ', 'UF' => 'MG', 'VL_LATITUDE' => -21.1333, 'VL_LONGITUDE' => -42.3667, 'CHUVA' => 35.2, 'TEM_INS' => 25.8, 'UMD_INS' => 90, 'PRE_INS' => 922.4],
            ['CD_ESTACAO' => 'A518', 'DC_NOME' => 'JANAÚBA', 'UF' => 'MG', 'VL_LATITUDE' => -15.8000, 'VL_LONGITUDE' => -43.3000, 'CHUVA' => 0.0, 'TEM_INS' => 34.2, 'UMD_INS' => 38, 'PRE_INS' => 948.5],
            ['CD_ESTACAO' => 'A519', 'DC_NOME' => 'OURO PRETO', 'UF' => 'MG', 'VL_LATITUDE' => -20.3833, 'VL_LONGITUDE' => -43.5000, 'CHUVA' => 8.9, 'TEM_INS' => 17.6, 'UMD_INS' => 92, 'PRE_INS' => 885.3],
            ['CD_ESTACAO' => 'A520', 'DC_NOME' => 'ALFENAS', 'UF' => 'MG', 'VL_LATITUDE' => -21.4333, 'VL_LONGITUDE' => -45.9500, 'CHUVA' => 0.0, 'TEM_INS' => 24.8, 'UMD_INS' => 72, 'PRE_INS' => 902.1],
        ];

        // Adicionar timestamp atual
        $now = now();
        foreach ($mockStations as &$station) {
            $station['DT_MEDICAO'] = $now->format('Y-m-d');
            $station['HR_MEDICAO'] = $now->format('Hi'); // Formato: 0300 (sem dois pontos)
        }

        // Filtrar por UF (mock já é MG, mas mantemos a lógica)
        $filtered = collect($mockStations)
            ->filter(fn($s) => strtoupper($s['UF']) === strtoupper($uf))
            ->values()
            ->toArray();

        Log::info("INMET MOCK: " . count($filtered) . " estações para {$uf}");

        return $filtered;
    }

    public function getLeituraEstacao(string $codigoEstacao): ?array
    {
        // Cache por 15 min
        $cacheKey = "inmet_estacao_{$codigoEstacao}_" . now()->format('YmdH');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($codigoEstacao) {
            return $this->fetchStationFromApi($codigoEstacao);
        });
    }

    private function fetchStationFromApi(string $codigoEstacao): ?array
    {
        try {
            $today = date('Y-m-d');

            // Endpoint autenticado: /token/estacao/DATA/DATA/CODIGO/TOKEN
            $url = self::TOKEN_URL . "/{$today}/{$today}/{$codigoEstacao}/" . self::API_TOKEN;

            Log::info("Buscando dados INMET Estação (Token): {$codigoEstacao}");

            $response = Http::timeout(10)
                ->retry(3, 100)
                ->get($url);

            if ($response->failed()) {
                Log::warning("Falha INMET Estação {$codigoEstacao}: " . $response->status());
                return null;
            }

            $data = $response->json();

            if (!is_array($data) || empty($data)) {
                return null;
            }

            // O INMET retorna array de horários. Pegar o último válido (com temperatura).
            // Collect, reverse, first valid.
            $lastReading = collect($data)
                ->reverse()
                ->first(function ($reading) {
                    return !empty($reading['TEM_INS']);
                });

            return $lastReading; // Retorna array ou null
        } catch (\Exception $e) {
            Log::error("Erro INMET Estação {$codigoEstacao}: " . $e->getMessage());
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
