<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Notificacao PAE</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #14213d; margin: 0; padding: 24px; background-color: #f4f6fb;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px; border: 1px solid #e2e8f0;">
        <h2 style="margin-top: 0; color: #14213d;">Notificacao {{ $ciclo }} de 3 — Protocolo PAE</h2>

        <p>Prezado(a) Coordenador(a) do empreendimento <strong>{{ $empreendimentoNome }}</strong>,</p>

        <p>
            Informamos a emissao da <strong>notificacao {{ $ciclo }}</strong> referente ao protocolo
            <strong>{{ $protocoloNumero }}</strong> (SEI {{ $numSei }}), emitida em {{ \Carbon\Carbon::parse($dtNotificacao)->format('d/m/Y') }}.
        </p>

        <p style="background: #fff4e5; border-left: 4px solid #fca311; padding: 12px 16px;">
            O prazo para devolutiva e de <strong>30 dias</strong>, encerrando em
            <strong>{{ \Carbon\Carbon::parse($prazoFinal)->format('d/m/Y') }}</strong>.
            @if ($ciclo >= 3)
                Esta e a ultima notificacao: a ausencia de devolutiva acarretara a suspensao do protocolo.
            @endif
        </p>

        <p>Em caso de duvidas, responda ao processo SEI indicado acima.</p>

        <p style="margin-bottom: 0;">Coordenadoria Estadual de Defesa Civil - CEDEC/MG</p>
    </div>
</body>
</html>
