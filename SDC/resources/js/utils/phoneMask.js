/**
 * Utilitário para máscara de telefone/celular
 * Formata no padrão: (00) 00000-0000 (11 dígitos, DDD + 9 dígitos)
 */
export function applyPhoneMask(value) {
  const numbers = String(value ?? '').replace(/\D/g, '').slice(0, 11);

  if (numbers.length <= 2) {
    return numbers;
  } else if (numbers.length <= 7) {
    return `(${numbers.slice(0, 2)}) ${numbers.slice(2)}`;
  }

  // Fixo (10 digitos) quebra em 4+4; celular (11) em 5+4. Sem este ramo um
  // fixo completo saia como "(38) 34899-880" -- um digito no grupo errado.
  // O caminho de 11 digitos segue identico ao de antes.
  if (numbers.length === 10) {
    return `(${numbers.slice(0, 2)}) ${numbers.slice(2, 6)}-${numbers.slice(6)}`;
  }

  return `(${numbers.slice(0, 2)}) ${numbers.slice(2, 7)}-${numbers.slice(7, 11)}`;
}

/**
 * Remove a máscara do telefone, retornando apenas números
 */
export function removePhoneMask(telefone) {
  return telefone.replace(/\D/g, '');
}
