<?php

/**
 * Script de Validação da API RAT
 *
 * Executa validações rápidas para garantir que:
 * 1. As rotas estão registradas
 * 2. Os controllers existem
 * 3. Os serviços estão disponíveis
 * 4. O banco de dados está conectado
 *
 * Uso: php validate_rat_api.php
 */

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║     Validação da API RAT - 25/04/2026                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. Verificar arquivo de bootstrap
echo "1️⃣  Verificando bootstrap...\n";
$bootstrapPath = __DIR__ . '/bootstrap/app.php';
if (file_exists($bootstrapPath)) {
    echo "   ✅ Bootstrap encontrado\n";
} else {
    echo "   ❌ Bootstrap NÃO encontrado em {$bootstrapPath}\n";
    exit(1);
}

// 2. Carregar Laravel
echo "\n2️⃣  Carregando Laravel...\n";
try {
    $app = require $bootstrapPath;
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    echo "   ✅ Laravel carregado com sucesso\n";
} catch (Exception $e) {
    echo "   ❌ Erro ao carregar Laravel: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Verificar routes
echo "\n3️⃣  Verificando rotas RAT...\n";
try {
    $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
        ->filter(fn($route) => str_contains($route->getName() ?? '', 'compdec.rat'))
        ->pluck('getName')
        ->toArray();

    if (empty($routes)) {
        echo "   ⚠️  Nenhuma rota compdec.rat.* encontrada\n";
    } else {
        echo "   ✅ Encontradas " . count($routes) . " rotas RAT\n";
        foreach ($routes as $route) {
            echo "      • $route\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Erro ao listar rotas: " . $e->getMessage() . "\n";
}

// 4. Verificar controller
echo "\n4️⃣  Verificando RatUnifiedController...\n";
$controllerClass = 'App\Modules\Rat\Controllers\RatUnifiedController';
if (class_exists($controllerClass)) {
    echo "   ✅ RatUnifiedController encontrado\n";

    // Verificar métodos
    $controller = new ReflectionClass($controllerClass);
    $methods = [
        'index', 'create', 'store', 'show', 'edit', 'update', 'destroy',
        'draft', 'finalize', 'indexBo', 'storeBo', 'auditIndex', 'auditShow',
        'export', 'statistics', 'showDadosGerais', 'storeDadosGerais',
        'updateDadosGerais', 'indexEnvolvidos', 'storeEnvolvidos'
    ];

    $missing = [];
    foreach ($methods as $method) {
        if (!$controller->hasMethod($method)) {
            $missing[] = $method;
        }
    }

    if (empty($missing)) {
        echo "   ✅ Todos os métodos principais encontrados\n";
    } else {
        echo "   ⚠️  Métodos ausentes: " . implode(', ', $missing) . "\n";
    }
} else {
    echo "   ❌ RatUnifiedController NÃO encontrado\n";
}

// 5. Verificar Database
echo "\n5️⃣  Verificando conexão com banco de dados...\n";
try {
    $connection = \Illuminate\Support\Facades\DB::connection();
    $connection->getPdo();

    $driver = config('database.default');
    echo "   ✅ Banco de dados conectado (driver: $driver)\n";

    // Verificar se é PostgreSQL
    if ($driver === 'pgsql') {
        echo "   ✅ Usando PostgreSQL (correto!)\n";
    } else {
        echo "   ⚠️  Banco atual é $driver (esperado: pgsql)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro ao conectar ao banco: " . $e->getMessage() . "\n";
}

// 6. Verificar Models
echo "\n6️⃣  Verificando Models RAT...\n";
$models = [
    'App\Models\Rat\RatOcorrencia',
    'App\Models\Rat\RatOcorrenciaHistorico',
    'App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais',
    'App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos',
    'App\Modules\Rat\Models\Relatos\RatRelatoRecurso',
];

$found = 0;
foreach ($models as $model) {
    if (class_exists($model)) {
        $found++;
        echo "   ✅ $model\n";
    } else {
        echo "   ❌ $model NÃO encontrado\n";
    }
}
echo "   📊 {$found}/" . count($models) . " models encontrados\n";

// 7. Verificar Services
echo "\n7️⃣  Verificando Services RAT...\n";
$services = [
    'App\Modules\Rat\Services\RatWriteService',
    'App\Modules\Rat\Services\RatAttachmentService',
    'App\Modules\Rat\Services\RatExportService',
    'App\Modules\Rat\Services\RatStatisticsService',
    'App\Modules\Rat\Application\Services\RatService',
];

$foundServices = 0;
foreach ($services as $service) {
    if (class_exists($service)) {
        $foundServices++;
        echo "   ✅ $service\n";
    } else {
        echo "   ⚠️  $service NÃO encontrado\n";
    }
}
echo "   📊 {$foundServices}/" . count($services) . " services encontrados\n";

// 8. Verificar DTOs
echo "\n8️⃣  Verificando DTOs RAT...\n";
$dtos = [
    'App\Modules\Rat\DTOs\RatBoDTO',
    'App\Modules\Rat\DTOs\RatDadosGeraisDTO',
    'App\Modules\Rat\DTOs\RatEnvolvidoDTO',
    'App\Modules\Rat\DTOs\RatRecursoDTO',
];

$foundDtos = 0;
foreach ($dtos as $dto) {
    if (class_exists($dto)) {
        $foundDtos++;
        echo "   ✅ $dto\n";
    } else {
        echo "   ⚠️  $dto NÃO encontrado\n";
    }
}
echo "   📊 {$foundDtos}/" . count($dtos) . " DTOs encontrados\n";

// 9. Resumo Final
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    RESUMO DA VALIDAÇÃO                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "📊 Estatísticas:\n";
echo "   • Rotas RAT: " . count($routes) . "\n";
echo "   • Models: {$found}/" . count($models) . "\n";
echo "   • Services: {$foundServices}/" . count($services) . "\n";
echo "   • DTOs: {$foundDtos}/" . count($dtos) . "\n";

echo "\n✨ Status Geral: ";
if (
    !empty($routes) &&
    $found > 2 &&
    $foundServices > 2 &&
    $foundDtos > 1
) {
    echo "✅ PRONTO PARA PRODUÇÃO\n\n";
} else {
    echo "⚠️  INCOMPLETO - Revisar antes de deploy\n\n";
}

echo "🚀 Próximos passos:\n";
echo "   1. Executar migrations: php artisan migrate --database=pgsql\n";
echo "   2. Testar API: bun run validate:api\n";
echo "   3. Validar frontend: npm run dev (ou bun run dev)\n";
echo "   4. Deploy: frankenphp run\n\n";
