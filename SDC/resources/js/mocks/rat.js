function pad3(n) {
  return String(n).padStart(3, '0');
}

function daysAgoISO(days) {
  const now = new Date();
  return new Date(now.getTime() - days * 24 * 60 * 60 * 1000).toISOString();
}

export function getMockRats(count = 50) {
  const municipios = [
    'Belo Horizonte/MG',
    'Contagem/MG',
    'Betim/MG',
    'Nova Lima/MG',
    'Ribeirão das Neves/MG',
    'Santa Luzia/MG',
    'Sete Lagoas/MG',
    'Ibirité/MG',
  ];

  const autores = ['Sistema', 'João Silva', 'Maria Oliveira', 'Ana Souza', 'Carlos Pereira'];

  const statusPool = ['rascunho', 'em_andamento', 'finalizado', 'arquivado'];

  // Distribuir datas nos últimos ~60 dias
  const maxDays = 60;
  const year = new Date().getFullYear();

  return Array.from({ length: count }, (_, i) => {
    const id = i + 1;
    const protocolo = `RAT-${year}-${pad3(id)}`;

    const status = statusPool[i % statusPool.length];
    const municipio = municipios[i % municipios.length];
    const criado_por = autores[i % autores.length];

    const createdDaysAgo = (i * 3) % maxDays;
    const updatedDaysAgo = Math.max(0, createdDaysAgo - 1);

    const created_at = daysAgoISO(createdDaysAgo);
    const updated_at = daysAgoISO(updatedDaysAgo);

    return {
      id,
      protocolo,
      status,
      created_at,
      updated_at,
      local: { municipio },
      dadosGerais: { data_fato: daysAgoISO(createdDaysAgo + 1) },
      recursos: [],
      envolvidos: [],
      anexos: [],
      criado_por,
    };
  });
}

export function getMockStatisticsFromRats(rats = []) {
  const safe = Array.isArray(rats) ? rats : [];
  const now = new Date();
  const sameDay = (a, b) =>
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate();

  const total = safe.length;
  const hoje = safe.filter((r) => r?.created_at && sameDay(new Date(r.created_at), now)).length;
  const esteMes = safe.filter((r) => {
    if (!r?.created_at) return false;
    const d = new Date(r.created_at);
    return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
  }).length;
  const esteAno = safe.filter((r) => {
    if (!r?.created_at) return false;
    return new Date(r.created_at).getFullYear() === now.getFullYear();
  }).length;

  return { total, hoje, esteMes, esteAno };
}

export const mockMunicipalities = [
  { value: '', label: 'Todos' },
  { value: 'Belo Horizonte/MG', label: 'Belo Horizonte/MG' },
  { value: 'Contagem/MG', label: 'Contagem/MG' },
  { value: 'Betim/MG', label: 'Betim/MG' },
  { value: 'Nova Lima/MG', label: 'Nova Lima/MG' },
  { value: 'Ribeirão das Neves/MG', label: 'Ribeirão das Neves/MG' },
  { value: 'Santa Luzia/MG', label: 'Santa Luzia/MG' },
  { value: 'Sete Lagoas/MG', label: 'Sete Lagoas/MG' },
  { value: 'Ibirité/MG', label: 'Ibirité/MG' },
];

export const mockCobradeTypes = [{ value: '', label: 'Todos' }];

export function getDefaultYears() {
  const current = new Date().getFullYear();
  return Array.from({ length: 6 }, (_, i) => {
    const y = String(current - i);
    return { value: y, label: y };
  });
}


