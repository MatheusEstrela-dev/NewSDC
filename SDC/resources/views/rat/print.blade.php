<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOS {{ $ocorrencia->numero_bos ?? 'RAT' }} — Defesa Civil MG</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            background: #fff;
        }

        /* ── Layout A4 ─────────────────────────────────── */
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 12mm 12mm 15mm 12mm;
            background: #fff;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 10mm 12mm 10mm;
            }
            body { background: #fff !important; }
            .page { width: 100%; margin: 0; padding: 0; box-shadow: none; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }

        @media screen {
            body { background: #e5e5e5; padding: 20px; }
            .page { box-shadow: 0 2px 16px rgba(0,0,0,.18); }
        }

        /* ── Cabeçalho ─────────────────────────────────── */
        .header {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1.5px solid #003b7a;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 6px;
            background: #003b7a;
            color: #fff;
        }
        .header-logo { flex: 0 0 60px; }
        .header-logo img { width: 56px; height: auto; display: block; }
        .header-center { flex: 1; text-align: center; }
        .header-center h1 { font-size: 11pt; font-weight: bold; letter-spacing: .5px; }
        .header-center h2 { font-size: 9pt; font-weight: 600; margin-top: 3px; opacity: .9; }
        .header-bos {
            flex: 0 0 140px;
            text-align: right;
            font-size: 8pt;
            line-height: 1.5;
        }
        .header-bos strong { display: block; font-size: 11pt; letter-spacing: 1px; }

        /* ── Seções ────────────────────────────────────── */
        .section-bar {
            background: #003b7a;
            color: #fff;
            font-size: 8.5pt;
            font-weight: bold;
            letter-spacing: .8px;
            padding: 3px 8px;
            margin-top: 8px;
            margin-bottom: 0;
        }
        .subsection-bar {
            background: #c0cfe0;
            color: #003b7a;
            font-size: 8pt;
            font-weight: bold;
            padding: 2px 8px;
            margin-top: 4px;
        }

        /* ── Tabela de campos ──────────────────────────── */
        .bos-table {
            width: 100%;
            border-collapse: collapse;
        }
        .bos-table td {
            border: 1px solid #888;
            padding: 3px 6px;
            vertical-align: top;
            font-size: 9pt;
        }
        .bos-table .lbl {
            background: #edf1f7;
            font-weight: bold;
            font-size: 7.5pt;
            color: #003366;
            white-space: nowrap;
            width: 20%;
        }
        .bos-table .val { width: 30%; }
        .bos-table .val-full { width: 80%; }
        .bos-table .val-half { width: 30%; }

        /* ── Histórico / Narrativa ─────────────────────── */
        .historico-cell {
            min-height: 60px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* ── Rodapé ────────────────────────────────────── */
        .footer {
            margin-top: 14px;
            border-top: 1px solid #aaa;
            padding-top: 5px;
            font-size: 7.5pt;
            color: #555;
            display: flex;
            justify-content: space-between;
        }

        /* ── Botão imprimir (tela) ─────────────────────── */
        .print-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 10px 22px;
            background: #003b7a;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 11pt;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,.3);
            z-index: 999;
        }
        .print-btn:hover { background: #00519e; }
    </style>
</head>
<body>

<div class="page">

    {{-- ── Cabeçalho ──────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-logo">
            <img src="{{ asset('imgs/logo_dc.png') }}" alt="Defesa Civil MG">
        </div>
        <div class="header-center">
            <h1>SISTEMA INTEGRADO DE DEFESA CIVIL — SIDC</h1>
            <h2>BOLETIM DE OCORRÊNCIA SIMPLIFICADO — BOS/RAT</h2>
        </div>
        <div class="header-bos">
            Nº BOS<br>
            <strong>{{ $ocorrencia->numero_bos ?? 'N/A' }}</strong>
            <br>
            <span style="font-size:7pt;">Emitido: {{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    {{-- ── Dados Gerais ─────────────────────────────────────────── --}}
    <div class="section-bar">DADOS GERAIS DA OCORRÊNCIA</div>
    <table class="bos-table">
        <tr>
            <td class="lbl">DATA/HORA DO FATO</td>
            <td class="val">{{ $dg?->data_fato ? \Carbon\Carbon::parse($dg->data_fato)->format('d/m/Y H:i') : '—' }}</td>
            <td class="lbl">INÍCIO DA ATIVIDADE</td>
            <td class="val">{{ $dg?->data_inicio_atividade ? \Carbon\Carbon::parse($dg->data_inicio_atividade)->format('d/m/Y H:i') : '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">TÉRMINO DA ATIVIDADE</td>
            <td class="val">{{ $dg?->data_termino_atividade ? \Carbon\Carbon::parse($dg->data_termino_atividade)->format('d/m/Y H:i') : '—' }}</td>
            <td class="lbl">CÓDIGO DA OCORRÊNCIA</td>
            <td class="val">{{ $dg?->nat_codigo ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">TIPO DE OCORRÊNCIA</td>
            <td class="val">{{ $dg?->nat_ocorrencia ?? '—' }}</td>
            <td class="lbl">NOME DA OPERAÇÃO</td>
            <td class="val">{{ $dg?->nat_nome_operacao ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">DATA DA COMUNICAÇÃO</td>
            <td class="val">{{ $dg?->com_ocorrencia_data ? \Carbon\Carbon::parse($dg->com_ocorrencia_data)->format('d/m/Y H:i') : '—' }}</td>
            <td class="lbl">COMO FOI SOLICITADO</td>
            <td class="val">{{ $dg?->com_ocorrencia_atendimento ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">MUNICÍPIO</td>
            <td class="val">{{ $dg?->local_municipio_nome ?? $dg?->local_municipio ?? '—' }}</td>
            <td class="lbl">UF</td>
            <td class="val">{{ $dg?->local_estadouf ?? 'MG' }}</td>
        </tr>
        <tr>
            <td class="lbl">LOGRADOURO</td>
            <td class="val">{{ $dg?->end_logradouro ?? '—' }}</td>
            <td class="lbl">BAIRRO</td>
            <td class="val">{{ $dg?->end_bairro ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">NÚMERO</td>
            <td class="val">{{ $dg?->end_numero ?? '—' }}</td>
            <td class="lbl">CEP</td>
            <td class="val">{{ $dg?->end_cep ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">PONTO DE REFERÊNCIA</td>
            <td class="val-full" colspan="3">{{ $dg?->end_ponto_referencia ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">UNIDADE RESPONSÁVEL</td>
            <td class="val">{{ $dg?->uni_responsavel_unidade ?? '—' }}</td>
            <td class="lbl">MUNICÍPIO DA UNIDADE</td>
            <td class="val">{{ $dg?->uni_responsavel_municipio ?? '—' }}</td>
        </tr>
    </table>

    {{-- ── Histórico ────────────────────────────────────────────── --}}
    <div class="section-bar">HISTÓRICO / NARRATIVA</div>
    <table class="bos-table">
        <tr>
            <td class="historico-cell" style="padding:6px 8px;">
                @if(is_array($ocorrencia->historico))
                    @foreach($ocorrencia->historico as $item)
                        @if(is_string($item)) {{ $item }}@php echo "\n"; @endphp
                        @elseif(is_array($item)) {{ $item['texto'] ?? $item['descricao'] ?? $item['text'] ?? json_encode($item) }}@php echo "\n"; @endphp
                        @endif
                    @endforeach
                @elseif($dg?->descricao)
                    {{ $dg->descricao }}
                @else
                    NÃO DESCRITO
                @endif
            </td>
        </tr>
    </table>

    {{-- ── Recursos Empregados ──────────────────────────────────── --}}
    <div class="section-bar">RECURSOS EMPREGADOS</div>
    @forelse($recursos as $rec)
        @php $r = $rec->conteudo; @endphp
        @if($recursos->count() > 1)
            <div class="subsection-bar">RECURSO Nº {{ $loop->iteration }}</div>
        @endif
        <table class="bos-table">
            <tr>
                <td class="lbl">TIPO DE RECURSO</td>
                <td class="val">{{ $r?->recurso_tipo ?? '—' }}</td>
                <td class="lbl">ÓRGÃO RESPONSÁVEL</td>
                <td class="val">{{ $r?->viatura_orgao ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">IDENTIFICAÇÃO / PLACA</td>
                <td class="val">{{ $r?->viatura_placa ?? $r?->viatura_prefixo ?? '—' }}</td>
                <td class="lbl">QUANTIDADE</td>
                <td class="val">{{ $r?->viatura_quantidade ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">SAÍDA</td>
                <td class="val">{{ $r?->viatura_saida ? \Carbon\Carbon::parse($r->viatura_saida)->format('d/m/Y H:i') : '—' }}</td>
                <td class="lbl">CHEGADA</td>
                <td class="val">{{ $r?->viatura_chegada ? \Carbon\Carbon::parse($r->viatura_chegada)->format('d/m/Y H:i') : '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">KM PERCORRIDO</td>
                <td class="val">{{ $r?->viatura_km ?? '—' }}</td>
                <td class="lbl">CONDIÇÃO</td>
                <td class="val">{{ $r?->viatura_condicao ?? '—' }}</td>
            </tr>
            @if($r?->recurso_descricao)
            <tr>
                <td class="lbl">DESCRIÇÃO</td>
                <td class="val-full" colspan="3">{{ $r->recurso_descricao }}</td>
            </tr>
            @endif
        </table>
    @empty
        <table class="bos-table"><tr><td style="padding:6px;text-align:center;color:#666;">Nenhum recurso cadastrado.</td></tr></table>
    @endforelse

    {{-- ── Agentes / Guarnição ──────────────────────────────────── --}}
    @if($agentes->count() > 0)
    <div class="section-bar">AGENTES / INTEGRANTES DA GUARNIÇÃO</div>
    <table class="bos-table">
        <thead>
            <tr>
                <td class="lbl" style="width:22%">NOME COMPLETO</td>
                <td class="lbl" style="width:12%">MASP/MATRÍC.</td>
                <td class="lbl" style="width:10%">PG/CARGO</td>
                <td class="lbl" style="width:18%">ÓRGÃO</td>
                <td class="lbl" style="width:18%">UNIDADE</td>
                <td class="lbl" style="width:14%">FUNÇÃO</td>
                <td class="lbl" style="width:6%">COND.</td>
            </tr>
        </thead>
        <tbody>
            @foreach($agentes as $a)
            <tr>
                <td class="val">{{ $a->nome_completo ?? '—' }}</td>
                <td class="val">{{ $a->masp ?? $a->matricula ?? '—' }}</td>
                <td class="val">{{ $a->pg_cargo ?? '—' }}</td>
                <td class="val">{{ $a->orgao ?? '—' }}</td>
                <td class="val">{{ $a->unidade ?? '—' }}</td>
                <td class="val">{{ $a->funcao ?? '—' }}</td>
                <td class="val" style="text-align:center">{{ $a->is_condutor ? 'S' : 'N' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ── Envolvidos ───────────────────────────────────────────── --}}
    <div class="section-bar">ENVOLVIDOS NA OCORRÊNCIA</div>
    @forelse($envolvidos as $env)
        @php $e = $env->conteudo; @endphp
        @if($envolvidos->count() > 1)
            <div class="subsection-bar">ENVOLVIDO Nº {{ $loop->iteration }}</div>
        @endif
        <table class="bos-table">
            <tr>
                <td class="lbl">TIPO</td>
                <td class="val">{{ $e?->g_tipo_pessoa ?? '—' }}</td>
                <td class="lbl">NOME COMPLETO</td>
                <td class="val">{{ $e?->p_nome_completo ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">CPF</td>
                <td class="val">{{ $e?->p_cpf ?? '—' }}</td>
                <td class="lbl">DATA DE NASCIMENTO</td>
                <td class="val">{{ $e?->p_data_nascimento ? \Carbon\Carbon::parse($e->p_data_nascimento)->format('d/m/Y') : '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">SEXO</td>
                <td class="val">{{ $e?->p_sexo ?? '—' }}</td>
                <td class="lbl">ESTADO CIVIL</td>
                <td class="val">{{ $e?->p_estado_civil ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">NOME DA MÃE</td>
                <td class="val-full" colspan="3">{{ $e?->p_nome_mae ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">MUNICÍPIO</td>
                <td class="val">{{ $e?->p_end_municipio ?? '—' }}</td>
                <td class="lbl">UF</td>
                <td class="val">{{ $e?->p_end_estado_uf ?? '—' }}</td>
            </tr>
        </table>
    @empty
        <table class="bos-table"><tr><td style="padding:6px;text-align:center;color:#666;">Nenhum envolvido cadastrado.</td></tr></table>
    @endforelse

    {{-- ── Rodapé ───────────────────────────────────────────────── --}}
    <div class="footer">
        <span>Defesa Civil MG — SIDC &bull; {{ $ocorrencia->numero_bos }}</span>
        <span>Emitido em {{ now()->format('d/m/Y \à\s H:i') }}</span>
    </div>
</div>

<button class="print-btn no-print" onclick="window.print()">🖨 Imprimir</button>

</body>
</html>
