/**
 * Utilitários para formatação de datas
 */

/**
 * Formata data para exibição relativa (ex: "Há 2 horas", "Ontem")
 */
export function formatRelativeDate(date) {
  const now = new Date();
  const targetDate = new Date(date);
  const diffInSeconds = Math.floor((now - targetDate) / 1000);

  if (diffInSeconds < 60) {
    return 'Agora';
  }

  const diffInMinutes = Math.floor(diffInSeconds / 60);
  if (diffInMinutes < 60) {
    return `Há ${diffInMinutes} minuto${diffInMinutes > 1 ? 's' : ''}`;
  }

  const diffInHours = Math.floor(diffInMinutes / 60);
  if (diffInHours < 24) {
    return `Há ${diffInHours} hora${diffInHours > 1 ? 's' : ''}`;
  }

  const diffInDays = Math.floor(diffInHours / 24);
  if (diffInDays === 1) {
    return 'Ontem';
  }

  if (diffInDays < 7) {
    return `Há ${diffInDays} dias`;
  }

  // Retorna data formatada se for mais antiga
  return targetDate.toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
}

/**
 * Formata data no padrão brasileiro (DD/MM/YYYY)
 */
export function formatDate(date) {
  if (!date) return '';
  
  const d = new Date(date);
  return d.toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
}

/**
 * Formata data e hora no padrão brasileiro
 */
export function formatDateTime(date) {
  if (!date) return '';
  
  const d = new Date(date);
  return d.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

