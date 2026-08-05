<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Inscrição confirmada: {{ $treinamento->titulo }}</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f3f4f6; padding:20px; color:#1f2937;">
    <div style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
        <div style="background:#003b7a; color:#ffffff; padding:20px 24px;">
            <h1 style="margin:0; font-size:20px;">Inscrição confirmada</h1>
            <p style="margin:6px 0 0; font-size:13px; opacity:.9;">Sistema Integrado de Defesa Civil — MG</p>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 12px;">Olá, {{ $nomeInscrito }}!</p>

            <p style="margin:0 0 16px;">
                Sua inscrição no {{ $treinamento->categoria->value === 'EVENTO' ? 'evento' : 'curso' }}
                <strong>&ldquo;{{ $treinamento->titulo }}&rdquo;</strong> foi registrada com sucesso.
            </p>

            <table cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:14px; margin-bottom:16px;">
                <tr>
                    <td style="background:#f9fafb; border:1px solid #e5e7eb; width:40%;"><strong>Data</strong></td>
                    <td style="border:1px solid #e5e7eb;">
                        {{ optional($treinamento->data_inicio)->format('d/m/Y') ?? '—' }}
                        @if($treinamento->data_fim) a {{ $treinamento->data_fim->format('d/m/Y') }} @endif
                    </td>
                </tr>
                <tr>
                    <td style="background:#f9fafb; border:1px solid #e5e7eb;"><strong>Local</strong></td>
                    <td style="border:1px solid #e5e7eb;">{{ $treinamento->local ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="background:#f9fafb; border:1px solid #e5e7eb;"><strong>Carga horária</strong></td>
                    <td style="border:1px solid #e5e7eb;">{{ $treinamento->carga_horaria }}h</td>
                </tr>
            </table>

            <p style="margin:0 0 8px;">
                Em anexo está o <strong>QR Code do seu ingresso</strong>. Apresente-o (impresso ou na tela do
                celular) no dia do evento/curso para confirmar sua presença.
            </p>

            <p style="margin:16px 0 0; font-size:12px; color:#6b7280;">
                Mensagem automática do Portal de Treinamentos da Defesa Civil MG. Em caso de dúvidas,
                entre em contato com a coordenação.
            </p>
        </div>
    </div>
</body>
</html>
