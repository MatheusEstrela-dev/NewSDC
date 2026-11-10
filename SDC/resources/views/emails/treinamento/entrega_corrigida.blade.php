<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Entrega {{ $aprovada ? 'aprovada' : 'devolvida' }}: {{ $item }}</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f3f4f6; padding:20px; color:#1f2937;">
    <div style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
        <div style="background:#003b7a; color:#ffffff; padding:20px 24px;">
            <h1 style="margin:0; font-size:20px;">Entrega {{ $aprovada ? 'aprovada' : 'devolvida' }}</h1>
            <p style="margin:6px 0 0; font-size:13px; opacity:.9;">Sistema Integrado de Defesa Civil — MG</p>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 12px;">Olá, {{ $nomeInscrito }}!</p>

            <p style="margin:0 0 16px;">
                O professor corrigiu a sua entrega do projeto <strong>&ldquo;{{ $item }}&rdquo;</strong>
                no curso <strong>&ldquo;{{ $curso }}&rdquo;</strong>.
            </p>

            @if($aprovada)
                <p style="margin:0 0 16px;">Sua entrega foi <strong>aprovada</strong>. Você já pode seguir para o próximo conteúdo.</p>
            @else
                <p style="margin:0 0 16px;">Sua entrega foi <strong>devolvida</strong>. Leia o parecer abaixo e envie uma nova versão.</p>
            @endif

            @if($parecer)
                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:12px 16px; font-size:14px; white-space:pre-line;">{{ $parecer }}</div>
            @endif

            <p style="margin:16px 0 0; font-size:12px; color:#6b7280;">
                Mensagem automática do Portal de Treinamentos da Defesa Civil MG. Em caso de dúvidas,
                entre em contato com a coordenação.
            </p>
        </div>
    </div>
</body>
</html>
