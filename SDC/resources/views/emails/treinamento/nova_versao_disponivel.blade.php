<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Versão {{ $rotulo }} disponível: {{ $curso }}</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f3f4f6; padding:20px; color:#1f2937;">
    <div style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
        <div style="background:#003b7a; color:#ffffff; padding:20px 24px;">
            <h1 style="margin:0; font-size:20px;">Versão {{ $rotulo }} disponível</h1>
            <p style="margin:6px 0 0; font-size:13px; opacity:.9;">Sistema Integrado de Defesa Civil — MG</p>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 12px;">Olá, {{ $nomeInscrito }}!</p>

            <p style="margin:0 0 16px;">
                O curso <strong>&ldquo;{{ $curso }}&rdquo;</strong>, que você concluiu, ganhou uma nova versão
                (<strong>{{ $rotulo }}</strong>). O seu certificado da versão anterior continua válido.
                Use o botão <strong>Atualizar</strong> em Meus cursos para completar apenas o que mudou.
            </p>

            <p style="margin:0 0 16px;">
                <a href="{{ $urlMeusCursos }}" style="display:inline-block; background:#003b7a; color:#ffffff; text-decoration:none; padding:10px 18px; border-radius:6px; font-size:14px;">Ir para Meus cursos</a>
            </p>

            @if($notas)
                <p style="margin:0 0 6px; font-size:13px; color:#6b7280;">O que mudou</p>
                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:12px 16px; font-size:14px; white-space:pre-line;">{{ $notas }}</div>
            @endif

            <p style="margin:16px 0 0; font-size:12px; color:#6b7280;">
                Mensagem automática do Portal de Treinamentos da Defesa Civil MG. Em caso de dúvidas,
                entre em contato com a coordenação.
            </p>
        </div>
    </div>
</body>
</html>
