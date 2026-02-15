<?php

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Querying Users...\n";
    $query = User::query()
        ->with(['roles', 'permissions'])
        ->select(['id', 'name', 'email', 'cpf', 'active', 'status', 'email_verified_at', 'created_at']);

    $users = $query->orderBy('name')->paginate(15);

    echo "Query successful. Users count: " . $users->count() . "\n";
    if ($users->count() > 0) {
        echo "First user status: " . $users->first()->status . "\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
