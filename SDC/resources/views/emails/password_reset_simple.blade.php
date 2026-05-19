@php
    // $resetUrl chega pronto do AppServiceProvider (URL opaca encriptada).
    // Fallback defensivo caso seja chamado sem essa variavel.
    $resetUrl = $resetUrl ?? (isset($token, $email)
        ? route('password.reset', ['token' => $token, 'email' => $email])
        : url('/login'));
    // Logo embarcado via CID (Symfony Mime embedFromPath em AppServiceProvider).
    $logoSrc = 'cid:logo-cedec';
    // Oxford Blue — tom unico para header e body.
    $colorNavyTop = '#002147';
    $colorNavyBottom = '#002147';
    $colorOrange = '#f39c12';
    $colorOrangeHover = '#d68910';
    $colorTextDark = '#1f2937';
    $colorTextMuted = '#6b7280';
    $colorCardBorder = '#e5e7eb';
    $colorWarnBg = '#fef3c7';
    $colorWarnText = '#92400e';
    // Cor solida unica para body e header: deep navy. Outlook web nao renderiza
    // gradient/background-image, entao usamos a cor mais profunda da paleta SDC.
    $colorBodyBg = $colorNavyBottom;
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <title>Redefinicao de Senha - SDC</title>
    <style>
        @media only screen and (max-width: 620px) {
            .container { width: 100% !important; }
            .px-pad { padding-left: 20px !important; padding-right: 20px !important; }
            .cta { width: 100% !important; box-sizing: border-box; }
        }
        a { color: {{ $colorOrange }}; }
        .cta:hover { background-color: {{ $colorOrangeHover }} !important; }
    </style>
</head>
<body bgcolor="{{ $colorBodyBg }}" style="margin:0; padding:0; background-color: {{ $colorBodyBg }}; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: {{ $colorTextDark }};">

    <span style="display:none !important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden; mso-hide:all;">
        Recebemos uma solicitacao para redefinir a senha da sua conta no SDC. O link expira em 15 minutos.
    </span>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="{{ $colorBodyBg }}" style="background-color: {{ $colorBodyBg }};">
        <tr>
            <td align="center" style="padding: 32px 12px;">

                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="container" style="max-width:600px; width:100%; background-color:#ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border: 1px solid {{ $colorCardBorder }};">

                    <tr>
                        <td align="center" valign="middle" bgcolor="#002147" style="background-color:#002147; padding: 40px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin: 0 auto;">
                                <tr>
                                    <td valign="middle" style="padding: 0 12px 0 0;">
                                        <img src="{{ $logoSrc }}" alt="" width="72" height="72" style="display:block; border:0; outline:none; text-decoration:none; max-width:72px;" />
                                    </td>
                                    <td valign="middle" align="left" style="color:#ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; font-size: 28px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; line-height: 1;">
                                        DEFESA CIVIL
                                    </td>
                                </tr>
                            </table>
                            <div style="color:#ffffff; font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; font-weight: 600; opacity: 0.92; margin-top: 18px;">
                                CEDEC &middot; Defesa Civil de Minas Gerais
                            </div>
                            <div style="color:#ffffff; font-size: 20px; font-weight: 600; margin-top: 6px;">
                                Sistema Integrado de Defesa Civil
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="px-pad" style="padding: 36px 40px 12px;">
                            <h1 style="margin:0 0 16px; font-size: 22px; font-weight: 600; color: {{ $colorTextDark }};">
                                Redefinicao de senha
                            </h1>
                            <p style="margin:0 0 16px; font-size: 15px; line-height: 1.6; color: {{ $colorTextDark }};">
                                Ola,
                            </p>
                            <p style="margin:0 0 16px; font-size: 15px; line-height: 1.6; color: {{ $colorTextDark }};">
                                Recebemos uma solicitacao para redefinir a senha da sua conta no SDC.
                                Clique no botao abaixo para criar uma nova senha:
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td class="px-pad" align="center" style="padding: 8px 40px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" bgcolor="{{ $colorOrange }}" style="background-color: {{ $colorOrange }}; border-radius: 8px;">
                                        <a href="{{ $resetUrl }}" class="cta" target="_blank" rel="noopener noreferrer"
                                           style="display:inline-block; padding: 14px 36px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px; letter-spacing: 0.02em;">
                                            Redefinir minha senha
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="px-pad" style="padding: 0 40px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: {{ $colorWarnBg }}; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; line-height: 1.5; color: {{ $colorWarnText }};">
                                        <strong>Tempo limite:</strong> este link expira em <strong>15 minutos</strong> a partir do envio deste email.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="px-pad" style="padding: 0 40px 28px;">
                            <p style="margin:0 0 8px; font-size: 13px; line-height: 1.5; color: {{ $colorTextMuted }};">
                                Se o botao nao funcionar, copie e cole o endereco abaixo no seu navegador:
                            </p>
                            <p style="margin:0; font-size: 12px; line-height: 1.5; color: {{ $colorOrange }}; word-break: break-all;">
                                <a href="{{ $resetUrl }}" target="_blank" rel="noopener noreferrer" style="color: {{ $colorOrange }}; text-decoration: underline;">{{ $resetUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    @if(!empty($cpf_coordenador))
                    <tr>
                        <td class="px-pad" style="padding: 0 40px 24px;">
                            <div style="border-top: 1px solid {{ $colorCardBorder }}; padding-top: 16px; font-size: 13px; color: {{ $colorTextMuted }};">
                                <strong style="color: {{ $colorTextDark }};">CPF da conta:</strong> {{ $cpf_coordenador }}
                            </div>
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td class="px-pad" style="padding: 0 40px 32px;">
                            <p style="margin:0; font-size: 13px; line-height: 1.5; color: {{ $colorTextMuted }};">
                                Se voce nao solicitou esta alteracao, ignore este email. Sua senha continuara a mesma e nenhuma acao e necessaria.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="background-color: #f9fafb; padding: 20px 40px; border-top: 1px solid {{ $colorCardBorder }};">
                            <p style="margin: 0 0 6px; font-size: 12px; color: {{ $colorTextMuted }};">
                                Atenciosamente,<br />
                                <strong style="color: {{ $colorTextDark }};">CEDEC &middot; Coordenadoria Estadual de Defesa Civil</strong>
                            </p>
                            <p style="margin: 0; font-size: 11px; color: {{ $colorTextMuted }};">
                                Este e um email automatico. Por favor, nao responda.
                            </p>
                        </td>
                    </tr>

                </table>

                <p style="margin: 16px 0 0; font-size: 11px; color: {{ $colorTextMuted }};">
                    &copy; {{ date('Y') }} Governo do Estado de Minas Gerais
                </p>

            </td>
        </tr>
    </table>

</body>
</html>
