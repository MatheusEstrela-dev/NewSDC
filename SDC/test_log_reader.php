<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $logReader = app(\App\Services\Logging\LogFileReaderService::class);
    $logs = $logReader->readLogs(['limit' => 5]);
    
    echo "TOTAL LOGS RETORNADOS: " . $logs->count() . "

";
    
    foreach($logs as $index => $log) {
        echo "[$index] LEVEL: {$log['level']} | MESSAGE: " . substr($log['message'], 0, 100) . "...
";
    }
} catch (\Exception $e) {
    echo "ERRO AO LER LOGS: " . $e->getMessage() . "
";
}
