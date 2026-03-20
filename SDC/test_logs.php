<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reader = new \App\Services\Logging\LogFileReaderService();
$logs = $reader->readLogs([
    'type' => 'events',
    'level' => 'critical',
    'limit' => 5
]);

echo "Total critical logs found: " . count($logs) . "\n";
if (count($logs) > 0) {
    echo "--- FIRST LOG ---\n";
    print_r($logs[0]);
    echo "--- END FIRST LOG ---\n";
}
