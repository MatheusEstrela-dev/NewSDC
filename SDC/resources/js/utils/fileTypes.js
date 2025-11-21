/**
 * Utilitário para tipos de arquivos e ícones
 */

/**
 * Retorna informações sobre o tipo de arquivo
 */
export function getFileTypeInfo(filename) {
  const extension = filename.split('.').pop()?.toLowerCase();
  
  const typeMap = {
    pdf: {
      tipo: 'pdf',
      icone: 'DocumentTextIcon',
      corIcone: 'text-blue-400 bg-blue-900/20',
      corBg: 'bg-blue-900/20',
    },
    kml: {
      tipo: 'kml',
      icone: 'MapIcon',
      corIcone: 'text-green-400 bg-green-900/20',
      corBg: 'bg-green-900/20',
    },
    doc: {
      tipo: 'doc',
      icone: 'DocumentIcon',
      corIcone: 'text-yellow-400 bg-yellow-900/20',
      corBg: 'bg-yellow-900/20',
    },
    docx: {
      tipo: 'doc',
      icone: 'DocumentIcon',
      corIcone: 'text-yellow-400 bg-yellow-900/20',
      corBg: 'bg-yellow-900/20',
    },
  };

  return typeMap[extension] || {
    tipo: 'file',
    icone: 'DocumentIcon',
    corIcone: 'text-slate-400 bg-slate-800',
    corBg: 'bg-slate-800',
  };
}

/**
 * Valida se o arquivo é permitido
 */
export function isAllowedFileType(filename) {
  const allowedTypes = ['pdf', 'kml', 'doc', 'docx'];
  const extension = filename.split('.').pop()?.toLowerCase();
  return allowedTypes.includes(extension);
}

/**
 * Formata tamanho de arquivo
 */
export function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

