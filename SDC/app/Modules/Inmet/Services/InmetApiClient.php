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
        try {
            $today = date('Y-m-d');
            $url = self::TOKEN_URL . "/{$today}/{$today}/" . self::API_TOKEN;

            $response = Http::timeout(15)
                ->retry(3, 200)
                ->get($url);

            if ($response->failed()) {
                Log::warning("Falha INMET UF {$uf}: " . $response->status());
                return [];
            }

            $data = $response->json();

            if (!is_array($data)) {
                return [];
            }

            return collect($data)
                ->filter(fn($station) => isset($station['UF']) && strtoupper($station['UF']) === strtoupper($uf))
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::error("Erro INMET UF {$uf}: " . $e->getMessage());
            return [];
        }
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
