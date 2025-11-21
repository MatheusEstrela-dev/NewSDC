/**
 * Utilitário para cores de status
 * Retorna classes Tailwind CSS baseadas no status
 */
export function getStatusColor(status) {
  const statusMap = {
    'Análise Técnica': 'bg-blue-100 text-blue-800',
    'Parecer': 'bg-purple-100 text-purple-800',
    'Aguard. Doc.': 'bg-red-100 text-red-800',
    'Triagem': 'bg-gray-100 text-gray-800',
    'Aprovado': 'bg-emerald-100 text-emerald-800',
    'Rejeitado': 'bg-red-100 text-red-800',
    'Pendente': 'bg-amber-100 text-amber-800',
  };

  return statusMap[status] || 'bg-gray-100 text-gray-800';
}

/**
 * Retorna a cor de fundo do badge de status
 */
export function getStatusBadgeColor(status) {
  const colorMap = {
    'Análise Técnica': 'blue',
    'Parecer': 'purple',
    'Aguard. Doc.': 'red',
    'Triagem': 'gray',
    'Aprovado': 'emerald',
    'Rejeitado': 'red',
    'Pendente': 'amber',
  };

  return colorMap[status] || 'gray';
}

