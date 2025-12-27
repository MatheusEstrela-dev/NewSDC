<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Tdap\Application\UseCases\ListMovimentacoesUseCase;

// Rota de debug temporária
Route::get('/debug/movimentacoes', function (ListMovimentacoesUseCase $useCase) {
    $result = $useCase->executeAsDTO([], 15);

    return response()->json([
        'debug' => 'Testing executeAsDTO method',
        'result_type' => gettype($result),
        'is_array' => is_array($result),
        'keys' => array_keys($result),
        'data_type' => gettype($result['data'] ?? null),
        'data_count' => is_array($result['data'] ?? null) ? count($result['data']) : 'N/A',
        'pagination' => $result['pagination'] ?? null,
        'full_result' => $result,
    ]);
})->middleware(['web']);
