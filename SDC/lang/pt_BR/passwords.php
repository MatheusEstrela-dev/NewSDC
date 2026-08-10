<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mensagens de redefinicao de senha
|--------------------------------------------------------------------------
|
| Mesmo defeito do validation.php: o broker de senha devolve chaves como
| passwords.sent, e NewPasswordController as passa por __(). Sem este arquivo
| o usuario lia "passwords.sent" na tela de login.
|
*/

return [

    'reset' => 'Sua senha foi redefinida.',
    'sent' => 'Enviamos o link de redefinição de senha para o seu e-mail.',
    'throttled' => 'Aguarde antes de tentar novamente.',
    'token' => 'Este link de redefinição de senha é inválido ou expirou.',
    'user' => 'Não encontramos um usuário com esse endereço de e-mail.',

];
