<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
try {
    $kernel->handle(
        new \Symfony\Component\Console\Input\ArrayInput(['command' => 'optimize:clear']),
        new \Symfony\Component\Console\Output\ConsoleOutput()
    );
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
