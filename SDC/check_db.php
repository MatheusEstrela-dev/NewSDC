<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = [
    'dec_processo',
    'dec_entrada_processos',
    'dec_entrada_processo_logs',
    'dec_entrada_decretos',
    'dec_entrada_desastres',
    'dec_entrada_categoria_desastres',
    'dec_cobrade',
    'dec_decreto_categorias',
    'dec_decreto_municipios',
    'dec_desastre_categorias',
    'dec_desastre_grupos',
    'dec_desastre_item_campos',
    'dec_desastre_items'
];

echo "=== Verificando Estado Geral das Tabelas ===\n";
foreach ($tables as $table) {
    try {
        $count = \Illuminate\Support\Facades\DB::table($table)->count();
        $result = \Illuminate\Support\Facades\DB::table($table)->first();
        
        echo "\n--- $table (Total: $count) ---\n";
        if ($result) {
            print_r((array) $result);
        } else {
            echo "(TABELA VAZIA)\n";
        }
    } catch (\Exception $e) {
        echo "\n--- $table (ERRO) ---\n";
        echo $e->getMessage() . "\n";
    }
}
