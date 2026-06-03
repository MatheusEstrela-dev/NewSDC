<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;
$users = User::all(['id', 'name', 'email', 'password'])->toArray();
echo json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
