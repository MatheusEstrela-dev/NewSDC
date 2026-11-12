function lerCookie(nome) {
  const par = document.cookie.split('; ').find((c) => c.startsWith(`${nome}=`));

  return par ? decodeURIComponent(par.slice(nome.length + 1)) : '';
}

/**
 * POST por fetch, fora do axios global: nenhum interceptor da aplicacao roda
 * (o de 419 recarregaria a pagina no meio da aula) e toda falha e engolida.
 * Dispara e esquece: devolve false quando nao ha como enviar.
 */
export function postSilencioso(url, corpo = null, { keepalive = false } = {}) {
  if (!url || typeof fetch !== 'function') return false;

  try {
    fetch(url, {
      method: 'POST',
      keepalive,
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': lerCookie('XSRF-TOKEN'),
      },
      body: corpo === null ? null : JSON.stringify(corpo),
    }).catch(() => {});

    return true;
  } catch {
    return false;
  }
}
