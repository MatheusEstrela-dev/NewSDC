import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';

/**
 * Utilitário para cores de eventos do PAE
 * Retorna classes Tailwind CSS baseadas no tipo de evento
 */
export function getEventColorClass(type) {
  const colorMap = {
    submissao: 'bg-blue-500 text-white',
    vencimento: 'bg-yellow-500 text-white',
    aprovacao: 'bg-green-500 text-white',
    cadastro: 'bg-slate-600 text-white',
  };

  return colorMap[type] || 'bg-slate-700 text-slate-400';
}

/**
 * Retorna componente de ícone baseado no tipo de evento
 */
export function getEventIcon(type) {
  const iconMap = {
    submissao: DocumentTextIcon,
    vencimento: ExclamationTriangleIcon,
    aprovacao: CheckCircleIcon,
    cadastro: BuildingOfficeIcon,
  };

  return iconMap[type] || DocumentTextIcon;
}

