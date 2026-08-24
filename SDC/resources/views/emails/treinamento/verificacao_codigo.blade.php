<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Codigo de confirmacao</title>
</head>
<body style="margin:0;padding:0;background:#0B1F3A;font-family:Arial,Helvetica,sans-serif;color:#0B1F3A;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0B1F3A;padding:40px 0;">
        <tr><td align="center">
            <table width="560" cellpadding="0" cellspacing="0" border="0" style="background:#FFFFFF;border-radius:8px;overflow:hidden;">
                <tr><td align="center" style="background:#0B1F3A;padding:24px;">
                    <img src="cid:logo-cedec" alt="CEDEC" width="64" style="display:block;border:0;">
                </td></tr>
                <tr><td style="padding:32px;">
                    <h1 style="margin:0 0 16px;font-size:20px;color:#0B1F3A;">Confirme seu cadastro</h1>
                    <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                        Ola <strong>{{ $nome }}</strong>,
                    </p>
                    <p style="margin:0 0 24px;font-size:15px;line-height:1.5;">
                        Recebemos um cadastro no Portal de Treinamentos da Defesa Civil de Minas Gerais
                        usando este endereco (<strong>{{ $email }}</strong>). Para confirmar que ele e seu
                        e liberar o acesso, digite o codigo abaixo na tela de confirmacao.
                    </p>
                    <div style="background:#F5F7FA;border:1px solid #E5E9F0;border-radius:8px;padding:24px;text-align:center;margin:0 0 24px;">
                        <p style="margin:0 0 8px;font-size:13px;color:#64748B;letter-spacing:1px;">SEU CODIGO</p>
                        <p style="margin:0;font-family:'Courier New',monospace;font-size:32px;letter-spacing:8px;color:#0B1F3A;font-weight:bold;">
                            {{ $codigo }}
                        </p>
                        <p style="margin:8px 0 0;font-size:12px;color:#64748B;">
                            Expira em {{ $ttlMin }} minutos ({{ $expiraEm }})
                        </p>
                    </div>
                    <p style="margin:0 0 8px;font-size:13px;color:#64748B;line-height:1.5;">
                        Se nao foi voce quem se cadastrou, ignore este e-mail. Sem a confirmacao deste
                        codigo, o cadastro nao da acesso a nada e e descartado automaticamente.
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
