@php($instrutor = $papel === \App\Modules\Treinamento\Services\ImpressaoDeCertificado::INSTRUTOR)
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado — {{ $curso }}</title>
    <style>
        /* Layout do modelo da DEPDC (PPTX "Concluinte e Instrutor"): A4 deitado,
           medidas em mm tiradas do proprio arquivo. */
        @font-face {
            font-family: 'Montserrat';
            font-weight: 100 900;
            src: url('{{ asset('fonts/Montserrat-Variable.ttf') }}') format('truetype');
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { background: #e5e7eb; }

        body {
            font-family: 'Montserrat', Arial, Helvetica, sans-serif;
            color: #111;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page {
            position: relative;
            width: 297mm;
            height: 210mm;
            margin: 0 auto;
            background: #fff;
            overflow: hidden;
        }

        @media print {
            @page { size: A4 landscape; margin: 0; }
            html, body { background: #fff; }
            .no-print { display: none !important; }
        }

        @media screen {
            body { padding: 20px; }
            .page { box-shadow: 0 2px 16px rgba(0, 0, 0, .18); }
        }

        .faixas { position: absolute; inset: 0; width: 297mm; height: 210mm; }

        .faixa-curva {
            position: absolute;
            left: 180.6mm;
            top: 93.6mm;
            width: 54.3mm;
            height: 178.5mm;
            transform: rotate(-90deg) scaleX(-1);
        }

        .logo {
            position: absolute;
            left: -4.4mm;
            top: 82.1mm;
            width: 59mm;
            height: 44.3mm;
            object-fit: contain;
        }

        .titulo {
            position: absolute;
            left: 95.5mm;
            top: 8.4mm;
            font-size: 60pt;
            font-weight: 700;
            line-height: 1.15;
            color: #093871;
        }

        .texto {
            position: absolute;
            left: 49.7mm;
            top: 47.9mm;
            width: 231.9mm;
            font-size: 14pt;
            line-height: 1.2;
            text-align: justify;
        }

        .texto .nome {
            margin: 6mm 0 8mm;
            text-align: center;
            font-size: 18pt;
            font-weight: 700;
            line-height: 1.15;
        }

        .texto p + p { margin-top: 5.9mm; }
        .texto strong { font-weight: 700; }

        .data {
            position: absolute;
            right: 18.3mm;
            top: 154.4mm;
            font-size: 9.2pt;
        }

        .assinatura-coordenador {
            position: absolute;
            left: 66.1mm;
            top: 166.7mm;
            width: 78mm;
            height: 23.9mm;
            object-fit: contain;
            transform: rotate(1.72deg);
        }

        .rodape-assinatura {
            position: absolute;
            text-align: center;
            font-weight: 700;
            line-height: 1.4;
        }

        .rodape-assinatura.coordenador {
            left: 65.7mm;
            top: 181mm;
            width: 83.5mm;
            font-size: 9.6pt;
        }

        .rodape-assinatura.titular {
            left: 170mm;
            top: 179mm;
            width: 78mm;
            padding-top: 1.6mm;
            border-top: 1.2pt solid #111;
            font-size: 10pt;
        }

        .verificacao {
            position: absolute;
            right: 10mm;
            top: 9mm;
            width: 46mm;
            text-align: right;
            font-size: 6.5pt;
            line-height: 1.35;
            color: #374151;
        }

        .verificacao img { width: 19mm; height: 19mm; display: block; margin: 0 0 1mm auto; }
        .verificacao strong { color: #111; }

        .print-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 10px 22px;
            background: #093871;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 11pt;
            font-family: Arial, sans-serif;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .3);
            z-index: 999;
        }

        .print-btn:hover { background: #0d4a94; }
    </style>
</head>
<body>

<div class="page">
    <svg class="faixas" viewBox="0 0 297 210" aria-hidden="true">
        <polygon fill="#F18945" points="1.66,0 1.66,56.05 18.44,47.21 36.16,56.54 36.16,0" />
        <polygon fill="#093871" points="8.76,0 8.76,58.14 26.36,48.87 43.96,58.14 43.96,0" />
        <polygon fill="#F68944" points="19.81,161.63 1.66,171.15 1.66,210 38.06,210 38.06,171.15 19.91,161.63" />
        <polygon fill="#093871" points="26.31,163.63 8.76,173.26 8.76,210 43.96,210 43.96,173.26 26.41,163.63" />
    </svg>
    <img class="faixa-curva" src="{{ asset('imgs/certificado/faixa-curva.png') }}" alt="">
    <img class="logo" src="{{ asset('imgs/certificado/logo-defesa-civil-mg.png') }}" alt="Defesa Civil - Minas Gerais Sempre Presente">

    <div class="titulo">Certificado</div>

    <div class="verificacao">
        <img src="{{ $qrValidacao }}" alt="QR Code de validação">
        Código <strong>{{ $codigoVerificacao }}</strong><br>
        Valide em {{ $enderecoValidacao }}
    </div>

    <div class="texto">
        <p>
            A Coordenadoria Estadual de Defesa Civil de Minas Gerais (CEDEC-MG), por meio da Diretoria de
            Educação em Proteção e Defesa Civil (DEPDC), certifica que
        </p>

        <div class="nome">{{ $nome }}</div>

        @if ($instrutor)
            <p>
                ministrou {{ $artigoCurso }} <strong>{{ $curso }}</strong>{{ $periodo ? ', '.$realizado.' '.$periodo : '' }},
                com carga horária de {{ $cargaHoraria }} horas, contribuindo de forma relevante para a formação e o
                desenvolvimento de competências dos profissionais e agentes de Proteção e Defesa Civil.
            </p>
            <p>
                Esta certificação expressa o reconhecimento da CEDEC-MG pela excelência, dedicação e compromisso
                demonstrados na condução das atividades de ensino, na disseminação de conhecimentos técnicos e no
                fortalecimento da capacidade institucional dos órgãos integrantes do Sistema Nacional de Proteção e
                Defesa Civil (SINPDEC), contribuindo para a promoção da cultura de prevenção, da redução dos riscos de
                desastres e da construção de comunidades mais resilientes.
            </p>
        @else
            <p>
                Participou e concluiu com aproveitamento {{ $artigoCurso }} <strong>{{ $curso }}</strong>{{ $periodo ? ', '.$realizado.' '.$periodo : '' }},
                com carga horária de {{ $cargaHoraria }} horas{{ $aproveitamento !== null ? ' e aproveitamento de '.number_format($aproveitamento, 1, ',', '.').'%' : '' }},
                promovido no âmbito das ações de fortalecimento das capacidades institucionais e de desenvolvimento de
                competências em Proteção e Defesa Civil.
            </p>
            <p>
                A presente certificação reconhece o empenho e a dedicação do participante na aquisição de
                conhecimentos voltados à gestão de riscos e desastres, à promoção da cultura de prevenção e
                resiliência e ao fortalecimento do Sistema Nacional de Proteção e Defesa Civil.
            </p>
        @endif
    </div>

    <div class="data">Belo Horizonte, {{ $dataEmissao }}.</div>

    <img class="assinatura-coordenador" src="{{ asset('imgs/certificado/assinatura-coordenador.png') }}" alt="">
    <div class="rodape-assinatura coordenador">
        Paulo Roberto Bermudes Rezende, Cel PM<br>
        Chefe do Gabinete Militar do Governador<br>
        e Coordenador Estadual de Defesa Civil
    </div>

    <div class="rodape-assinatura titular">{{ $instrutor ? 'Assinatura do instrutor' : 'Assinatura do concluinte' }}</div>
</div>

<button class="print-btn no-print" onclick="window.print()">Imprimir / Salvar como PDF</button>

</body>
</html>
