<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Seu certificado de {{ $curso }} está disponível</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f3f4f6; padding:20px; color:#1f2937;">
    <div style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
        <div style="background:#003b7a; color:#ffffff; padding:20px 24px;">
            <h1 style="margin:0; font-size:20px;">Seu certificado está disponível</h1>
            <p style="margin:6px 0 0; font-size:13px; opacity:.9;">Sistema Integrado de Defesa Civil — MG</p>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 12px;">Olá, {{ $nomeInscrito }}!</p>

            <p style="margin:0 0 16px;">
                Parabéns! Você concluiu o curso <strong>&ldquo;{{ $curso }}&rdquo;</strong>@if($rotuloVersao) (<strong>{{ $rotuloVersao }}</strong>)@endif
                em <strong>{{ $dataConclusao }}</strong> e o seu certificado está disponível.
            </p>

            <p style="margin:0 0 16px;">
                <a href="{{ $urlMeusCursos }}" style="display:inline-block; background:#003b7a; color:#ffffff; text-decoration:none; padding:10px 18px; border-radius:6px; font-size:14px;">Acessar meus certificados</a>
            </p>

            <p style="margin:0 0 6px; font-size:13px; color:#6b7280;">Validação pública do certificado</p>
            <p style="margin:0 0 16px; font-size:13px; word-break:break-all;">
                <a href="{{ $urlValidacao }}" style="color:#003b7a;">{{ $urlValidacao }}</a>
            </p>

            <p style="margin:16px 0 0; font-size:12px; color:#6b7280;">
                Mensagem automática do Portal de Treinamentos da Defesa Civil MG. Em caso de dúvidas,
                entre em contato com a coordenação.
            </p>
        </div>
    </div>
</body>
</html>
