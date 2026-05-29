<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pedido de troca de e-mail</title>
</head>
<body style="margin:0;padding:0;background:#0B1F3A;font-family:Arial,Helvetica,sans-serif;color:#0B1F3A;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0B1F3A;padding:40px 0;">
        <tr><td align="center">
            <table width="560" cellpadding="0" cellspacing="0" border="0" style="background:#FFFFFF;border-radius:8px;overflow:hidden;">
                <tr><td align="center" style="background:#0B1F3A;padding:24px;">
                    <img src="cid:logo-cedec" alt="CEDEC" width="64" style="display:block;border:0;">
                </td></tr>
                <tr><td style="padding:32px;">
                    <h1 style="margin:0 0 16px;font-size:20px;color:#0B1F3A;">Pedido de troca do seu e-mail</h1>
                    <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                        Ola <strong>{{ $name }}</strong>,
                    </p>

                    @if ($byAdminName)
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                            O administrador <strong>{{ $byAdminName }}</strong> solicitou a troca do
                            e-mail da sua conta no SDC para <strong>{{ $newEmailMasked }}</strong>.
                        </p>
                    @else
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                            Recebemos um pedido para trocar o e-mail da sua conta para
                            <strong>{{ $newEmailMasked }}</strong>.
                        </p>
                    @endif

                    <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                        <strong>Se foi voce:</strong> use o codigo de 6 digitos que enviamos para o novo endereco
                        para confirmar a troca.
                    </p>

                    <div style="background:#FEF3C7;border-left:4px solid #F59E0B;padding:16px 20px;border-radius:4px;margin:0 0 24px;">
                        <p style="margin:0 0 8px;font-size:14px;font-weight:bold;color:#92400E;">
                            Nao foi voce?
                        </p>
                        <p style="margin:0 0 12px;font-size:13px;line-height:1.5;color:#92400E;">
                            Seu e-mail atual continua valido e voce continua recebendo as notificacoes
                            normalmente. Recomendamos trocar sua senha imediatamente.
                        </p>
                        <a href="{{ $passwordResetUrl }}"
                           style="display:inline-block;background:#DC2626;color:#FFFFFF;padding:8px 16px;border-radius:4px;text-decoration:none;font-size:13px;font-weight:bold;">
                            Trocar senha agora
                        </a>
                    </div>

                    <p style="margin:0;font-size:12px;color:#64748B;line-height:1.5;">
                        Voce esta recebendo este e-mail porque ele e o endereco atual cadastrado na sua conta.
                    </p>
                </td></tr>
                <tr><td style="background:#F5F7FA;padding:16px 32px;text-align:center;font-size:12px;color:#64748B;">
                    CEDEC - Defesa Civil de Minas Gerais
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
