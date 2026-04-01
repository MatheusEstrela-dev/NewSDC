<?php
require 'vendor/autoload.php';
Illuminate\Support\Facades\Facade::setFacadeApplication(
    new Illuminate\Foundation\Application(getcwd())
);
$db = new PDO('sqlite:database/database.sqlite');
$user = $db->query("SELECT password FROM users WHERE cpf = '12345678900'")->fetch();
echo "Senha armazenada: " . $user['password'] . PHP_EOL;

// Usar Illuminate Hash para verificar
$hash = $user['password'];
$password = 'password';

// Criar um hasher manualmente
require 'vendor/autoload.php';
$app = new Illuminate\Foundation\Application();
$app->make('Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables')->bootstrap($app);
$app->make('Illuminate\Foundation\Bootstrap\RegisterFacades')->bootstrap($app);

// Usar a classe Hash diretamente
$hashFactory = new Illuminate\Hashing\BcryptHasher();
if ($hashFactory->check($password, $hash)) {
    echo "Senha correta!" . PHP_EOL;
} else {
    echo "Senha incorreta!" . PHP_EOL;
}
?>
