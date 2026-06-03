<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Municipio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Endpoint interno de consulta de municipios a partir da tabela local.
 *
 * Substitui a dependencia direta da API publica do IBGE no frontend,
 * dando resiliencia (funciona offline/sem IBGE) e consistencia com os
 * dados que o backend ja usa. O formato espelha o da API do IBGE
 * (id = codigo IBGE) para preservar o contrato dos selects existentes.
 */
class MunicipioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $uf = strtoupper((string) $request->query('uf', ''));

        $municipios = Cache::remember(
            "municipios.index.{$uf}",
            now()->addDay(),
            static function () use ($uf): array {
                return Municipio::query()
                    ->when($uf !== '', fn ($q) => $q->where('uf', $uf))
                    ->orderBy('nome')
                    ->get(['codigo_ibge', 'nome', 'uf'])
                    ->map(static fn (Municipio $m): array => [
                        'id' => (int) $m->codigo_ibge,
                        'nome' => $m->nome,
                        'codigo_ibge' => $m->codigo_ibge,
                        'uf' => $m->uf,
                    ])
                    ->all();
            }
        );

        return response()->json($municipios);
    }
}
