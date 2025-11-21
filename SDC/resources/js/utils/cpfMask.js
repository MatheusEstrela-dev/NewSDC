/**
 * Utilitário para máscara de CPF
 * Formata CPF no padrão: 000.000.000-00
 */
export function applyCpfMask(value) {
  // Remove tudo que não é dígito
  const numbers = value.replace(/\D/g, '');
  
  // Limita a 11 dígitos
  const limited = numbers.slice(0, 11);
  
  // Aplica a máscara
  if (limited.length <= 3) {
    return limited;
  } else if (limited.length <= 6) {
    return `${limited.slice(0, 3)}.${limited.slice(3)}`;
  } else if (limited.length <= 9) {
    return `${limited.slice(0, 3)}.${limited.slice(3, 6)}.${limited.slice(6)}`;
  } else {
    return `${limited.slice(0, 3)}.${limited.slice(3, 6)}.${limited.slice(6, 9)}-${limited.slice(9, 11)}`;
  }
}

/**
 * Remove a máscara do CPF, retornando apenas números
 */
export function removeCpfMask(cpf) {
  return cpf.replace(/\D/g, '');
}

/**
 * Valida se o CPF está no formato correto (apenas formato, não valida dígitos verificadores)
 */
export function isValidCpfFormat(cpf) {
  const numbers = removeCpfMask(cpf);
  return numbers.length === 11;
}

