<?php
$db = new PDO('sqlite:database/database.sqlite');
$user = $db->query("SELECT password FROM users WHERE cpf = '12345678900'")->fetch();
echo $user['password'];
?>
