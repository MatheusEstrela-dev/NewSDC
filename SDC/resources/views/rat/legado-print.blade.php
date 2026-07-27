<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RAT Arquivo Morto {{ $rat['num_ocorrencia'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #1f2937; font-size: 12px; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .sub { color: #6b7280; font-size: 11px; margin-bottom: 16px; }
        .badge { display: inline-block; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; padding: 2px 8px; font-size: 11px; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.meta td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
        table.meta td.label { background: #f9fafb; font-weight: bold; width: 160px; color: #374151; }
        .secao { font-weight: bold; font-size: 13px; margin: 18px 0 6px; border-bottom: 2px solid #111827; padding-bottom: 2px; }
        .acoes { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; line-height: 1.5; }
        .acoes p { margin: 0 0 8px; }
        .rodape { margin-top: 24px; color: #9ca3af; font-size: 10px; text-align: center; }
        @media print { body { margin: 0; } .noprint { display: none; } }
        .noprint { text-align: right; margin-bottom: 12px; }
        button { padding: 8px 14px; border: 0; border-radius: 6px; background: #2563eb; color: #fff; cursor: pointer; }
    </style>
</head>
<body>
    <div class="noprint">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <h1>Registro de Atendimento Tecnico &mdash; Arquivo Morto</h1>
    <div class="sub">
        Protocolo legado <strong>{{ $rat['num_ocorrencia'] }}</strong>
        &middot; <span class="badge">Somente leitura</span>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Numero</td><td>{{ $rat['num_ocorrencia'] }}</td>
            <td class="label">Data</td>
            <td>{{ $rat['dt_ocorrencia'] ? \Illuminate\Support\Carbon::parse($rat['dt_ocorrencia'])->format('d/m/Y H:i') : 'Nao informado' }}</td>
        </tr>
        <tr>
            <td class="label">Municipio</td><td>{{ $rat['municipio'] }}</td>
            <td class="label">Tipo</td><td>{{ $rat['tipo'] }}</td>
        </tr>
        <tr>
            <td class="label">Alvo</td><td>{{ $rat['alvo'] }}</td>
            <td class="label">COBRADE</td><td>{{ $rat['cobrade'] ?? 'Nao informado' }}</td>
        </tr>
        <tr>
            <td class="label">Operador</td><td>{{ $rat['operador'] }}</td>
            <td class="label">Operacao</td><td>{{ $rat['nome_operacao'] ?? '-' }}</td>
        </tr>
    </table>

    <div class="secao">Local</div>
    <table class="meta">
        <tr>
            <td class="label">Endereco</td><td>{{ trim(($rat['endereco'] ?? '').' '.($rat['numero'] ?? '')) ?: '-' }}</td>
            <td class="label">Bairro</td><td>{{ $rat['bairro'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">CEP</td><td>{{ $rat['cep'] ?? '-' }}</td>
            <td class="label">Estado</td><td>{{ $rat['estado'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Referencia</td><td colspan="3">{{ $rat['referencia'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Envolvidos</td><td colspan="3">{{ $rat['envolvidos'] ?? '-' }}</td>
        </tr>
    </table>

    <div class="secao">Acoes / Historico</div>
    <div class="acoes">{!! $rat['acoes_html'] ?: '<em>Sem descricao registrada.</em>' !!}</div>

    <div class="rodape">
        Documento gerado do arquivo morto do RAT legado (com_rat) &middot; SDC MG
    </div>
</body>
</html>
