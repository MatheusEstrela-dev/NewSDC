<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Rat\Models\RatOcorrencia;

echo "--- TESTE DE EXCLUSÃO LÓGICA (SOFT DELETE) ---\n";

// 1. Criar um registro de teste
$testRat = RatOcorrencia::create([
    'numero_bos' => 'TEST-DELETE-' . time(),
    'status' => 0
]);

echo "Registro criado: ID {$testRat->id}, Nº {$testRat->numero_bos}\n";

// 2. Executar delete()
$testRat->delete();
echo "Executado \$rat->delete()...\n";

// 3. Verificar se sumiu da consulta normal
$foundNormal = RatOcorrencia::find($testRat->id);
if (!$foundNormal) {
    echo "✅ SUCESSO: O registro NÃO foi encontrado na consulta normal (está oculto).\n";
} else {
    echo "❌ FALHA: O registro continua aparecendo na consulta normal.\n";
}

// 4. Verificar se ainda existe no banco
$foundWithTrashed = RatOcorrencia::withTrashed()->find($testRat->id);
if ($foundWithTrashed && $foundWithTrashed->deleted_at !== null) {
    echo "✅ SUCESSO: O registro AINDA EXISTE no banco de dados (deleted_at: {$foundWithTrashed->deleted_at}).\n";
} else {
    echo "❌ FALHA: O registro foi removido permanentemente ou não foi marcado como excluído.\n";
}

echo "--- FIM DO TESTE ---\n";
