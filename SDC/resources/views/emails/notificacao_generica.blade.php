@php
    $colorNavy = '#002147';
    $colorOrange = '#f39c12';
    $colorTextDark = '#1f2937';
    $colorTextMuted = '#6b7280';
    $colorCardBorder = '#e5e7eb';

    // A faixa de destaque muda com o tipo. E a unica pista visual do peso da
    // mensagem: no e-mail nao existe o badge colorido que o sino mostra.
    $faixa = match ($tipo) {
        'urgent' => ['bg' => '#fee2e2', 'borda' => '#dc2626', 'texto' => '#991b1b'],
        'error'  => ['bg' => '#fee2e2', 'borda' => '#dc2626', 'texto' => '#991b1b'],
        'warning' => ['bg' => '#fef3c7', 'borda' => '#f39c12', 'texto' => '#92400e'],
        'success' => ['bg' => '#dcfce7', 'borda' => '#16a34a', 'texto' => '#166534'],
        default   => ['bg' => '#eff6ff', 'borda' => '#2563eb', 'texto' => '#1e40af'],
    };
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <title>{{ $titulo }}</title>
    <style>
        @media only screen and (max-width: 620px) {
            .container { width: 100% !important; }
            .px-pad { padding-left: 20px !important; padding-right: 20px !important; }
            .cta { width: 100% !important; box-sizing: border-box; }
        }
        a { color: {{ $colorOrange }}; }
    </style>
</head>
<body bgcolor="{{ $colorNavy }}" style="margin:0; padding:0; background-color: {{ $colorNavy }}; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: {{ $colorTextDark }};">

    {{-- Preheader: o que o cliente de e-mail mostra na previa da lista. --}}
    <span style="display:none !important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden; mso-hide:all;">
        {{ $mensagem }}
    </span>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="{{ $colorNavy }}" style="background-color: {{ $colorNavy }};">
        <tr>
            <td align="center" style="padding: 32px 12px;">

                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="container" style="max-width:600px; width:100%; background-color:#ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border: 1px solid {{ $colorCardBorder }};">

                    <tr>
                        <td align="center" valign="middle" bgcolor="{{ $colorNavy }}" style="background-color:{{ $colorNavy }}; padding: 32px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin: 0 auto;">
                                <tr>
                                    <td valign="middle" style="padding: 0 12px 0 0;">
                                        <img src="cid:logo-cedec" alt="" width="56" height="56" style="display:block; border:0; outline:none; text-decoration:none; max-width:56px;" />
                                    </td>
                                    <td valign="middle" align="left" style="color:#ffffff; font-size: 22px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; line-height: 1;">
                                        DEFESA CIVIL
                                    </td>
                                </tr>
                            </table>
                            <div style="color:#ffffff; font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; font-weight: 600; opacity: 0.92; margin-top: 14px;">
                                {{ $moduloLabel }}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="px-pad" style="padding: 32px 40px 8px;">
                            <h1 style="margin:0 0 16px; font-size: 20px; line-height: 1.35; color: {{ $colorTextDark }};">
                                {{ $titulo }}
                            </h1>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="background-color: {{ $faixa['bg'] }}; border-left: 4px solid {{ $faixa['borda'] }}; padding: 14px 18px; color: {{ $faixa['texto'] }}; font-size: 15px; line-height: 1.55;">
                                        {{ $mensagem }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if ($acaoUrl)
                    <tr>
                        <td class="px-pad" align="left" style="padding: 24px 40px 8px;">
                            <a href="{{ $acaoUrl }}" class="cta" style="display:inline-block; background-color: {{ $colorOrange }}; color:#ffffff !important; text-decoration:none; font-weight:600; font-size:15px; padding: 13px 28px; border-radius: 8px;">
                                {{ $acaoTexto ?: 'Abrir no SDC' }}
                            </a>
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td class="px-pad" style="padding: 24px 40px 32px;">
                            <p style="margin:0; font-size: 13px; line-height: 1.5; color: {{ $colorTextMuted }};">
                                Voce recebeu este e-mail porque ativou o canal de e-mail para
                                <strong>{{ $moduloLabel }}</strong>. Para desligar, acesse
                                Configuracoes &rsaquo; Notificacoes no SDC.
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
