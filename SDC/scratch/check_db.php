<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- TABLE: rats ---\n";
$rats = DB::table('rats')->select('id', 'protocolo')->take(5)->get();
foreach ($rats as $r) {
    echo "ID: {$r->id} | Protocolo: {$r->protocolo}\n";
}

echo "\n--- TABLE: rat_ocorrencias ---\n";
$ocs = DB::table('rat_ocorrencias')->select('id', 'numero_bos')->take(5)->get();
foreach ($ocs as $o) {
    echo "ID: {$o->id} | BOS: {$o->numero_bos}\n";
}
