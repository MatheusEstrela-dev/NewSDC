<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$users = App\Models\User::limit(5)->get(['cpf', 'email', 'name']);
foreach($users as $u) {
    echo $u->cpf . ' | ' . $u->email . ' | ' . $u->name . PHP_EOL;
}
