/*
 * Normaliza o que foi lido do QR ou digitado no campo de consulta. Aceita:
 * - a URL da rota de consulta (o QR carrega a URL absoluta) ou o token puro;
 * - o CPF do servidor, com ou sem mascara (vira so digitos);
 * - o codigo de verificacao de um certificado (CEDEC-XXXX-XXXX).
 * Devolve null quando o texto nao e nenhum desses.
 */
const PADRAO_URL = /validar-participante\/([A-Za-z0-9_-]+)/;
const PADRAO_TOKEN = /^[A-Za-z0-9_-]{16,}$/;
const PADRAO_CPF = /^[\d.\-\s]+$/;
const PADRAO_CERTIFICADO = /^[A-Z]+-[A-Z0-9]{4}-[A-Z0-9]{4}$/;

export function extrairTokenCredencial(texto) {
  const valor = String(texto ?? '').trim();

  if (!valor) return null;

  const daUrl = valor.match(PADRAO_URL);

  if (daUrl) return daUrl[1];

  const digitos = valor.replace(/\D/g, '');

  if (PADRAO_CPF.test(valor) && digitos.length === 11) return digitos;

  if (PADRAO_CERTIFICADO.test(valor.toUpperCase())) return valor.toUpperCase();

  return PADRAO_TOKEN.test(valor) ? valor : null;
}
