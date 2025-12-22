function pad2(n) {
  return String(n).padStart(2, '0');
}

function pad3(n) {
  return String(n).padStart(3, '0');
}

function addDaysISO(baseDate, days) {
  const d = new Date(baseDate);
  d.setDate(d.getDate() + days);
  return d.toISOString();
}

function formatBR(dateISO) {
  const d = new Date(dateISO);
  return d.toLocaleDateString('pt-BR');
}

function isSameDay(dateISO, today = new Date()) {
  const d = new Date(dateISO);
  return d.toDateString() === today.toDateString();
}

export const paeSituacoes = [
  { value: '', label: 'Todas as situações' },
  { value: 'aguardando_analise', label: 'Aguardando Análise' },
  { value: 'em_edicao', label: 'Em edição' },
  { value: 'finalizado', label: 'Finalizado' },
];

export const paeAnalistas = [
  { value: '', label: 'Todos os analistas' },
  { value: 'DEMETRIO DA SILVA PASSOS', label: 'DEMETRIO DA SILVA PASSOS' },
  { value: 'ANA PAULA', label: 'ANA PAULA' },
  { value: 'CARLOS SILVA', label: 'CARLOS SILVA' },
];

export const paeEmpreendedores = [
  { value: '', label: 'Todos os empreendedores' },
  { value: 'SAMARCO', label: 'SAMARCO' },
  { value: 'VALE S.A.', label: 'VALE S.A.' },
  { value: 'ANGLO AMERICAN', label: 'ANGLO AMERICAN' },
];

export function getMockPaeProtocolos(count = 48) {
  const now = new Date();
  const year = now.getFullYear();
  const base = new Date(now.getTime() - 70 * 24 * 60 * 60 * 1000);

  const situacoes = ['aguardando_analise', 'em_edicao', 'finalizado'];
  const empreendedores = ['SAMARCO', 'VALE S.A.', 'ANGLO AMERICAN'];
  const estruturas = ['Dique 1', 'Dique 2', 'Dique 3', 'Barragem A', 'Barragem B'];
  const analistas = ['DEMETRIO DA SILVA PASSOS', 'ANA PAULA', 'CARLOS SILVA'];

  return Array.from({ length: count }, (_, i) => {
    const id = i + 1;
    const empreendedor = empreendedores[i % empreendedores.length];
    const analista = analistas[i % analistas.length];
    const estrutura = estruturas[i % estruturas.length];
    const situacao = situacoes[i % situacoes.length];

    // entrada espalhada nos últimos ~70 dias
    const entradaISO = addDaysISO(base, i);
    // limite análise: entre +15 e +45 dias após entrada
    const limiteISO = addDaysISO(entradaISO, 15 + (i % 31));

    const vencido = situacao !== 'finalizado' && new Date(limiteISO) < now;
    const proximo = !vencido && situacao !== 'finalizado' && (new Date(limiteISO) - now) / (1000 * 60 * 60 * 24) <= 10;

    const protocoloNumero = `${pad2((i % 28) + 1)}.${pad2(((i + 3) % 12) + 1)}.${year}.${pad3(id)}`;

    return {
      id,
      protocoloNumero,
      estrutura,
      empreendedor,
      analista,
      situacao,
      dataEntradaISO: entradaISO,
      dataEntrada: formatBR(entradaISO),
      limiteAnaliseISO: limiteISO,
      limiteAnalise: formatBR(limiteISO),
      prazo: vencido ? 'vencido' : proximo ? 'proximo' : 'ok',
      ccpae: i % 4 === 0,
      notificacoes: (i % 3) + 1,
      analises: i % 2,
    };
  });
}

export function getMockPaeStats(protocolosFiltrados = []) {
  const now = new Date();
  const total = protocolosFiltrados.length;

  const historico = protocolosFiltrados.filter((p) => p.situacao === 'finalizado').length;
  const vencidos = protocolosFiltrados.filter(
    (p) => p.situacao !== 'finalizado' && new Date(p.limiteAnaliseISO) < now
  ).length;
  const ccpae = protocolosFiltrados.filter((p) => p.ccpae).length;

  return { total, historico, vencidos, ccpae };
}

export function getMockPaeHistorico(protocoloId) {
  const now = new Date();
  const base = new Date(now.getTime() - 60 * 24 * 60 * 60 * 1000);
  const author = 'DEMETRIO DA SILVA PASSOS';

  const events = [
    {
      id: `${protocoloId}-1`,
      tipo: 'edicao',
      titulo: 'Protocolo Atualizado',
      descricao: 'Dados do protocolo foram atualizados.',
      dataISO: addDaysISO(base, 35),
      responsavel: author,
    },
    {
      id: `${protocoloId}-2`,
      tipo: 'notificacao',
      titulo: 'Notificação Enviada',
      descricao: 'Notificação foi enviada relacionada à análise.',
      dataISO: addDaysISO(base, 20),
      responsavel: author,
    },
    {
      id: `${protocoloId}-3`,
      tipo: 'analise',
      titulo: 'Análise Realizada',
      descricao: 'Análise técnica foi realizada para este protocolo.',
      dataISO: addDaysISO(base, 12),
      responsavel: author,
    },
    {
      id: `${protocoloId}-4`,
      tipo: 'criacao',
      titulo: 'Protocolo Criado',
      descricao: 'Protocolo foi criado no sistema.',
      dataISO: addDaysISO(base, 0),
      responsavel: author,
    },
  ];

  return {
    protocoloId,
    timeline: events.map((e) => ({
      ...e,
      data: new Date(e.dataISO).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      }),
    })),
    analises: [
      {
        id: `${protocoloId}-a1`,
        titulo: 'Análise Técnica #1',
        status: 'concluida',
        data: formatBR(addDaysISO(base, 12)),
        responsavel: author,
      },
    ],
    notificacoes: [
      {
        id: `${protocoloId}-n1`,
        titulo: 'Notificação de Pendência',
        canal: 'Sistema',
        data: formatBR(addDaysISO(base, 20)),
        responsavel: author,
      },
    ],
  };
}

export function matchesPaeFilters(protocolo, filters = {}) {
  const f = filters || {};

  const q = (f.buscar || '').trim().toLowerCase();
  if (q) {
    const hay = `${protocolo.protocoloNumero} ${protocolo.empreendedor} ${protocolo.estrutura}`.toLowerCase();
    if (!hay.includes(q)) return false;
  }

  if (f.situacao && protocolo.situacao !== f.situacao) return false;
  if (f.analista && protocolo.analista !== f.analista) return false;
  if (f.empreendedor && protocolo.empreendedor !== f.empreendedor) return false;

  if (f.data_inicio) {
    const start = new Date(f.data_inicio);
    if (new Date(protocolo.dataEntradaISO) < start) return false;
  }
  if (f.data_fim) {
    const end = new Date(f.data_fim);
    if (new Date(protocolo.dataEntradaISO) > end) return false;
  }

  return true;
}


