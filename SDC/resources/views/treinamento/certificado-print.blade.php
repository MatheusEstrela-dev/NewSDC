<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado — {{ $treinamento->titulo }}</title>
    <style>
        @font-face {
            font-family: 'Poppins';
            font-weight: 400;
            src: url('{{ asset('fonts/Poppins-Regular.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-weight: 700;
            src: url('{{ asset('fonts/Poppins-Bold.ttf') }}') format('truetype');
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', Arial, Helvetica, sans-serif;
            font-size: 12pt;
            color: #2b2b2b;
            background: #efe9df;
        }

        .page {
            position: relative;
            width: 297mm;
            min-height: 210mm;
            margin: 0 auto;
            background: #efe9df;
            overflow: hidden;
        }

        @media print {
            @page { size: A4 landscape; margin: 0; }
            body { background: #efe9df !important; }
            .page { width: 100%; min-height: 100vh; box-shadow: none; }
            .no-print { display: none !important; }
        }

        @media screen {
            body { padding: 20px; }
            .page { box-shadow: 0 2px 16px rgba(0,0,0,.18); }
        }

        /* ── Faixa decorativa lateral ─────────────────────── */
        .faixa {
            position: absolute;
            top: 0;
            left: 0;
            width: 70mm;
            height: 100%;
        }
        .faixa .tri {
            position: absolute;
            left: 0;
            width: 70mm;
            height: 40mm;
        }
        .faixa .tri-navy-1 { top: 0; background: #16305c; clip-path: polygon(0 0, 100% 0, 0 100%); }
        .faixa .tri-orange-1 { top: 34mm; background: #e8792e; clip-path: polygon(0 0, 100% 0, 0 100%); }
        .faixa .tri-navy-2 { bottom: 0; background: #16305c; clip-path: polygon(100% 100%, 0 100%, 100% 0); }
        .faixa .tri-orange-2 { bottom: 34mm; background: #e8792e; clip-path: polygon(100% 100%, 0 100%, 100% 0); }

        .logos {
            position: absolute;
            left: 12mm;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            gap: 10mm;
            z-index: 2;
        }
        .logos .selo {
            width: 34mm;
            height: 34mm;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
            overflow: hidden;
        }
        .logos .selo img { width: 90%; height: 90%; object-fit: contain; }

        /* ── Conteudo principal ───────────────────────────── */
        .conteudo {
            position: relative;
            z-index: 1;
            margin-left: 80mm;
            padding: 18mm 20mm 14mm 4mm;
        }

        .titulo {
            font-size: 34pt;
            font-weight: 700;
            color: #16305c;
            margin-bottom: 8mm;
        }

        .corpo p {
            font-size: 11pt;
            line-height: 1.6;
            color: #2b2b2b;
            margin-bottom: 5mm;
            text-align: justify;
        }

        .corpo strong { color: #16305c; }

        .rodape {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 14mm;
        }

        .assinatura {
            text-align: center;
            font-size: 8.5pt;
            color: #2b2b2b;
            line-height: 1.4;
        }
        .assinatura img {
            max-width: 55mm;
            max-height: 18mm;
            display: block;
            margin: 0 auto 2mm;
        }
        .assinatura .linha {
            width: 65mm;
            border-top: 1px solid #666;
            padding-top: 3mm;
        }

        .local-data {
            text-align: right;
            font-size: 9pt;
            color: #2b2b2b;
        }

        .print-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 10px 22px;
            background: #16305c;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 11pt;
            font-family: Arial, sans-serif;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,.3);
            z-index: 999;
        }
        .print-btn:hover { background: #1e4079; }
    </style>
</head>
<body>

<div class="page">
    <div class="faixa">
        <div class="tri tri-navy-1"></div>
        <div class="tri tri-orange-1"></div>
        <div class="tri tri-orange-2"></div>
        <div class="tri tri-navy-2"></div>
    </div>

    <div class="logos">
        <div class="selo">
            <img src="{{ asset('imgs/LOGO GMG - CEDEC-07.png') }}" alt="">
        </div>
        <div class="selo">
            <img src="{{ asset('imgs/logo.jpeg') }}" alt="">
        </div>
    </div>

    <div class="conteudo">
        <div class="titulo">Certificado</div>

        <div class="corpo">
            <p>
                A Coordenadoria Estadual de Defesa Civil de Minas Gerais (CEDEC-MG), por meio da Diretoria de
                Educação em Proteção e Defesa Civil (DEPDC), certifica que:
            </p>

            <p>
                <strong>{{ $nomeInscrito }}</strong> participou e concluiu com aproveitamento
                {{ $treinamento->categoria->value === 'EVENTO' ? 'o evento' : 'o(a) curso/capacitação' }}
                <strong>&ldquo;{{ $treinamento->titulo }}&rdquo;</strong>, realizado no período de
                <strong>{{ optional($treinamento->data_inicio)->format('d/m/Y') ?? '—' }}</strong> a
                <strong>{{ optional($treinamento->data_fim)->format('d/m/Y') ?? optional($treinamento->data_inicio)->format('d/m/Y') ?? '—' }}</strong>,
                com carga horária de <strong>{{ $treinamento->carga_horaria }} horas</strong>, promovido no âmbito
                das ações de fortalecimento das capacidades institucionais e de desenvolvimento de competências em
                Proteção e Defesa Civil.
            </p>

            <p>
                A presente certificação reconhece o empenho e a dedicação do participante na aquisição de
                conhecimentos voltados à gestão de riscos e desastres, à promoção da cultura de prevenção e
                resiliência e ao fortalecimento do Sistema Nacional de Proteção e Defesa Civil.
            </p>
        </div>

        <div class="rodape">
            <div class="assinatura">
                <img src="{{ asset('imgs/assinatura.jpeg') }}" alt="">
                <div class="linha">
                    Paulo Roberto Bermudes Rezende, CEL PM<br>
                    Chefe do Gabinete Militar do Governador<br>
                    e Coordenador Estadual de Defesa Civil
                </div>
            </div>

            <div class="assinatura">
                <div style="height: 18mm;"></div>
                <div class="linha">Assinatura do participante</div>
            </div>
        </div>

        <div class="local-data" style="margin-top: 8mm;">
            Belo Horizonte, {{ $emitidoEm }}
        </div>
    </div>
</div>

<button class="print-btn no-print" onclick="window.print()">Imprimir / Salvar como PDF</button>

</body>
</html>
