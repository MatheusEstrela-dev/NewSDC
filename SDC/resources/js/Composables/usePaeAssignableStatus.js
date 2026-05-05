export function isAssignableStatus(situacao) {
  const assignableStatuses = [
    'novo',
    'gerenciamento',
    'notificacao',
    'esperar_tratativa',
    'dilacao'
  ];
  return assignableStatuses.includes(situacao);
}

export function usePaeAssignableStatus() {
  return {
    isAssignableStatus
  };
}
