<?php
define('LARAVEL_START', microtime(true));
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
try {
    $users = User::all();
    if (count($users) > 0) {
        foreach ($users as $u) {
            echo 'Email: ' . $u->email . ', Password: ' . $u->password . chr(10);
        }
    } else {
        echo 'No users found' . chr(10);
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . chr(10);
}
?>
