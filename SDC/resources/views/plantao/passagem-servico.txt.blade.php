Serviço de Plantão ({{ $data }} - {{ $periodo }})

Assumido por: {{ $plantonista }}
@if ($plantonistaSaida !== null)
Saindo de serviço: {{ $plantonistaSaida }}
@endif

Viaturas em condições de atendimento:
Localização: {{ $localizacao }}

@foreach ($viaturas as $v)
🚐 {{ $v['prefixo'] }} - {{ $v['placa'] }}{{ $v['anotacao'] }}
⛽ Combustível: {{ $v['combustivel'] }}
📊 Hodômetro: {{ $v['hodometro'] }}
📝 Alterações: {{ $v['alteracoes'] }}
👨‍✈️ Último condutor: {{ $v['condutor'] }}

@endforeach
Contatos para abastecimento com Diesel (RMBH):
@foreach ($contatosDiesel as $contato)
{{ $contato }}
@endforeach

LINK VERIFICAÇÃO DE COMBUSTÍVEL POSTOS ORGÂNICOS. A Ferramenta possibilita a verificação dos níveis de combustíveis em cada Posto Orgânico Compartilhado-POC, em tempo real. Desta forma, tanto na Capital quanto nas DSP no interior.

{{ $linkBi }}

DTT: {{ $dtt }}
Plantão GMG: {{ $gmg }}

@if ($ocorrencias !== null)
Ocorrências ou ações de destaque do turno anterior:
{{ $ocorrencias }}
@else
Não houve.
@endif
