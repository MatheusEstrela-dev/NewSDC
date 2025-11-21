/**
 * Utilitário para cores de funções/roles do CCPAE
 * Retorna classes Tailwind CSS baseadas na função
 */
export function getRoleClass(role) {
  const roleMap = {
    Coordenador: 'text-xs font-bold uppercase tracking-wider text-yellow-400 bg-yellow-900/30 px-2 py-1 rounded',
    Secretário: 'text-xs font-bold uppercase tracking-wider text-blue-400 bg-blue-900/30 px-2 py-1 rounded',
    Membro: 'text-xs font-bold uppercase tracking-wider text-slate-400 bg-slate-800 px-2 py-1 rounded',
  };

  return roleMap[role] || 'text-xs font-bold uppercase tracking-wider text-slate-400 bg-slate-800 px-2 py-1 rounded';
}

/**
 * Retorna cor de badge baseada na função
 */
export function getRoleBadgeColor(role) {
  const colorMap = {
    Coordenador: 'yellow',
    Secretário: 'blue',
    Membro: 'slate',
  };

  return colorMap[role] || 'slate';
}

