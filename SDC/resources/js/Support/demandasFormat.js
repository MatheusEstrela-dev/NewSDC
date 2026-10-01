// Formatador generico mora em utils; reexportado para os chamadores de Demandas.
export { formatarDataHora } from '@/utils/dateFormatter';

export function formatarBytes(bytes) {
  if (!bytes) return '0 B';
  const unidades = ['B', 'KB', 'MB'];
  const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), unidades.length - 1);
  return `${(bytes / 1024 ** i).toFixed(i === 0 ? 0 : 1)} ${unidades[i]}`;
}

// datetime-local exige 'YYYY-MM-DDTHH:MM' no fuso local.
export function paraInputLocal(iso) {
  const d = iso ? new Date(iso) : new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}
