import { ref } from 'vue';

/**
 * Copia texto para a area de transferencia.
 *
 * A API moderna (navigator.clipboard) exige contexto seguro (HTTPS ou
 * localhost). Em rede interna sobre HTTP simples ela nao existe, por isso o
 * fallback com textarea oculta + document.execCommand('copy').
 */
export function useCopiarTexto() {
  const copiado = ref(false);

  const copiarViaTextarea = (texto) => {
    const area = document.createElement('textarea');
    area.value = texto;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.top = '-9999px';
    document.body.appendChild(area);
    area.select();

    let ok = false;
    try {
      ok = document.execCommand('copy');
    } catch {
      ok = false;
    }

    document.body.removeChild(area);
    return ok;
  };

  const copiar = async (texto) => {
    let ok = false;

    if (navigator.clipboard && window.isSecureContext) {
      try {
        await navigator.clipboard.writeText(texto);
        ok = true;
      } catch {
        ok = copiarViaTextarea(texto);
      }
    } else {
      ok = copiarViaTextarea(texto);
    }

    if (ok) {
      copiado.value = true;
      setTimeout(() => {
        copiado.value = false;
      }, 2500);
    }

    return ok;
  };

  return { copiar, copiado };
}
