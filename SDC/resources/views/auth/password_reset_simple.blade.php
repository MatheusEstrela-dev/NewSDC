<p>Por favor, acesse o sistema e siga as instruções para redefinir sua senha.</p>

@if(!empty($cpf_coordenador))
    <p><strong>CPF do Coordenador:</strong> {{ $cpf_coordenador }}</p>
@endif

<p>Se você não solicitou essa alteração, ignore este e-mail.</p>

<p>Atenciosamente,<br>CEDEC - MG</p>

<a href="{{ route('password.reset', ['token' => $token]) }}">Clique aqui para redefinir sua senha</a>
