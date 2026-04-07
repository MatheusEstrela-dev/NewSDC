<?php
define("LARAVEL_START", microtime(true));
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
try {
    $users = User::select("id", "name", "email", "password")->get();
    foreach ($users as $user) {
        echo "ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Password: {$user->password}\n";
    }
    echo "\nTotal: " . count($users) . " users\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
