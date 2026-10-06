import { dataDeISO } from '@/Support/dataLocal';

const SO_DIA = /^\d{4}-\d{2}-\d{2}$/;

/**
 * Converte a entrada em Date. 'YYYY-MM-DD' (data sem hora) vira meia-noite
 * LOCAL: `new Date('2025-10-30')` le como UTC e, no fuso de Brasilia, exibia
 * 29/10. Datetime com hora/fuso segue no parse nativo.
 */
function paraData(date) {
  if (typeof date !== 'string') return date;
  return SO_DIA.test(date) ? dataDeISO(date) : new Date(date);
}

/**
 * Formata uma data para o formato brasileiro
 * @param {Date|string} date - Data a ser formatada
 * @returns {string} Data formatada (dd/mm/aaaa hh:mm)
 */
export function formatDateTime(date) {
  if (!date) return '';
  
  const d = paraData(date);
  
  if (isNaN(d.getTime())) return '';
  
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  
  return `${day}/${month}/${year} ${hours}:${minutes}`;
}

/**
 * Formata uma data para o formato brasileiro (sem hora)
 * @param {Date|string} date - Data a ser formatada
 * @returns {string} Data formatada (dd/mm/aaaa)
 */
export function formatDate(date) {
  if (!date) return '';
  
  const d = paraData(date);
  
  if (isNaN(d.getTime())) return '';
  
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  
  return `${day}/${month}/${year}`;
}

/**
 * Data e hora para listagens: mesmo formato de formatDateTime, com travessao
 * quando nao ha data valida (celula em branco parece dado faltando).
 * @param {Date|string|null} date - Data a ser formatada
 * @returns {string} dd/mm/aaaa hh:mm ou travessao
 */
export function formatarDataHora(date) {
  return formatDateTime(date) || '—';
}
