<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Cronograma TDAP nº {{ $cronograma->numero }} liberado</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f3f4f6; padding:20px; color:#1f2937;">
    <div style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
        <div style="background:#2563eb; color:#ffffff; padding:20px 24px;">
            <h1 style="margin:0; font-size:20px;">TDAP — Cronograma liberado</h1>
            <p style="margin:6px 0 0; font-size:13px; opacity:.9;">Sistema Integrado de Defesa Civil — MG</p>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 12px;">Olá,</p>

            <p style="margin:0 0 16px;">
                O cronograma de fornecimento de água potável <strong>nº {{ $cronograma->numero }}</strong>
                foi <strong>ativado</strong> e está liberado para execução.
            </p>

            <table cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:14px; margin-bottom:16px;">
                <tr>
                    <td style="background:#f9fafb; border:1px solid #e5e7eb; width:40%;"><strong>Município</strong></td>
                    <td style="border:1px solid #e5e7eb;">
                        {{ $cronograma->municipio?->nome }}@if($cronograma->municipio?->uf)/{{ $cronograma->municipio->uf }}@endif
                    </td>
                </tr>
                <tr>
                    <td style="background:#f9fafb; border:1px solid #e5e7eb;"><strong>Prestador</strong></td>
                    <td style="border:1px solid #e5e7eb;">
                        {{ $cronograma->prestador?->nome }}<br>
                        <span style="font-family:monospace; font-size:12px; color:#6b7280;">{{ $cronograma->prestador?->cnpj }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="background:#f9fafb; border:1px solid #e5e7eb;"><strong>Ata / Lote</strong></td>
                    <td style="border:1px solid #e5e7eb;">
                        {{ $cronograma->ata?->numero }} / {{ $cronograma->lote?->numero }}
                    </td>
                </tr>
                <tr>
                    <td style="background:#f9fafb; border:1px solid #e5e7eb;"><strong>Vigência</strong></td>
                    <td style="border:1px solid #e5e7eb;">
                        {{ $cronograma->dt_inicio?->format('d/m/Y') }} — {{ $cronograma->dt_final?->format('d/m/Y') }}
                    </td>
                </tr>
                <tr>
                    <td style="background:#f9fafb; border:1px solid #e5e7eb;"><strong>Volume contratado</strong></td>
                    <td style="border:1px solid #e5e7eb;">
                        {{ number_format($cronograma->volume_contratado, 2, ',', '.') }} m³
                    </td>
                </tr>
                <tr>
                    <td style="background:#f9fafb; border:1px solid #e5e7eb;"><strong>Empenho</strong></td>
                    <td style="border:1px solid #e5e7eb;">{{ $cronograma->empenho ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="background:#f9fafb; border:1px solid #e5e7eb;"><strong>Caminhões alocados</strong></td>
                    <td style="border:1px solid #e5e7eb;">{{ count($cronograma->stored_caminhoes ?? []) }}</td>
                </tr>
            </table>

            @if(! empty($cronograma->justificativa))
                <p style="margin:0 0 8px;"><strong>Justificativa:</strong></p>
                <p style="margin:0 0 16px; padding:12px; background:#f9fafb; border-left:4px solid #2563eb; font-size:13px; white-space:pre-line;">{{ $cronograma->justificativa }}</p>
            @endif

            <p style="margin:16px 0 0; font-size:12px; color:#6b7280;">
                Mensagem automática do Sistema TDAP. Em caso de dúvidas, responda este e-mail
                ou entre em contato com a coordenação.
            </p>
        </div>
    </div>
</body>
</html>
