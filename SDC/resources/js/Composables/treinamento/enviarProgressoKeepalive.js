function lerCookie(nome) {
  const par = document.cookie.split('; ').find((c) => c.startsWith(`${nome}=`));

  return par ? decodeURIComponent(par.slice(nome.length + 1)) : '';
}

/**
 * Envio de progresso que sobrevive ao fechamento da aba (fetch keepalive).
 * Dispara e esquece: devolve false quando nao ha como enviar.
 */
export function enviarProgressoKeepalive(url, posicao) {
  if (!url || typeof fetch !== 'function') return false;

  try {
    fetch(url, {
      method: 'POST',
      keepalive: true,
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': lerCookie('XSRF-TOKEN'),
      },
      body: JSON.stringify({ posicao }),
    }).catch(() => {});

    return true;
  } catch {
    return false;
  }
}
