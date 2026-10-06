import { formatDate, formatDateTime } from '@/utils/dateFormatter';

export function hojeISO(data = new Date()) {
  const ano = data.getFullYear();
  const mes = String(data.getMonth() + 1).padStart(2, '0');
  const dia = String(data.getDate()).padStart(2, '0');
  return `${ano}-${mes}-${dia}`;
}

/**
 * 'YYYY-MM-DD' como meia-noite LOCAL. `new Date('2025-10-30')` le a string
 * como UTC, e no fuso de Brasilia isso vira 29/10 as 21h -- a data exibida
 * recuava um dia.
 */
export function dataDeISO(iso) {
  if (!iso) return null;
  const [ano, mes, dia] = iso.slice(0, 10).split('-').map(Number);
  return new Date(ano, mes - 1, dia);
}

/**
 * dd/mm/aaaa de um campo so-dia; '' quando vazio ou invalido. Aceita tambem o
 * cast 'date' cru do Eloquent ('2025-10-30T00:00:00.000000Z'): o dia sai dos
 * 10 primeiros caracteres, sem passar pelo fuso.
 */
export function formatarDia(iso) {
  return formatDate(dataDeISO(iso));
}

/**
 * "30 out" -- dia e mes abreviado, sem o ponto que o Intl poe no mes. Fora do
 * ano corrente leva o ano ("30 out 2025"), senao a data fica ambigua.
 */
export function diaMesCurto(iso, agora = new Date()) {
  const data = dataDeISO(iso);
  if (!data) return '—';
  const diaMes = data.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' }).replace('.', '').replace(' de ', ' ');
  return data.getFullYear() === agora.getFullYear() ? diaMes : `${diaMes} ${data.getFullYear()}`;
}

const MINUTO = 60 * 1000;
const HORA = 60 * MINUTO;

/**
 * Quando algo aconteceu, do jeito que se fala: "Agora", "Há 12 min",
 * "Hoje, 09:24", "Ontem, 16:40"; dali para tras, data e hora completas.
 */
export function tempoRelativo(dataHora, agora = new Date()) {
  if (!dataHora) return '';
  const data = new Date(dataHora);
  if (Number.isNaN(data.getTime())) return '';

  const decorrido = agora - data;
  if (decorrido < MINUTO) return 'Agora';
  if (decorrido < HORA) return `Há ${Math.floor(decorrido / MINUTO)} min`;

  const hora = data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  const ontem = new Date(agora.getFullYear(), agora.getMonth(), agora.getDate() - 1);
  if (hojeISO(data) === hojeISO(agora)) return `Hoje, ${hora}`;
  if (hojeISO(data) === hojeISO(ontem)) return `Ontem, ${hora}`;

  return formatDateTime(data);
}
