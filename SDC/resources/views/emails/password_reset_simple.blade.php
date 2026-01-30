<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Source Sans Pro', sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        .logo { max-width: 150px; }
        .content { background: white; padding: 20px; border-radius: 4px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 15px; }
        .footer { font-size: 0.85em; color: #666; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>SDC - Sistema de Defesa Civil</h2>
        </div>
        
        <div class="content">
            <p>Olá,</p>
            <p>Recebemos uma solicitação para redefinir a senha da sua conta.</p>
            <p>Por favor, clique no botão abaixo para criar uma nova senha:</p>

            <div style="text-align: center;">
                <a href="{{ route('password.reset', ['token' => $token, 'email' => $email]) }}" class="btn">Redefinir Senha</a>
            </div>

            @if(!empty($cpf_coordenador))
                <p style="margin-top: 20px; font-size: 0.9em;"><strong>CPF do Usuário:</strong> {{ $cpf_coordenador }}</p>
            @endif
            
            <p style="margin-top: 20px;">Este link expira em 15 minutos.</p>
            
            <p>Se você não solicitou essa alteração, nenhuma ação é necessária.</p>
        </div>
        
        <div class="footer">
            <p>Atenciosamente,<br>CEDEC - Defesa Civil de Minas Gerais</p>
            <p><small>Este é um e-mail automático, por favor não responda.</small></p>
        </div>
    </div>
</body>
</html>