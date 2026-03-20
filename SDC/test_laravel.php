<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Decretacoes\Models\Processo;

$p = Processo::where('n_protocolo_fide', 'MG-F-3149507-13214-20251202')->first();
$p->load('municipios');
echo "Process municipalities loaded via Eloquent:\n";
print_r($p->municipios->toArray());
