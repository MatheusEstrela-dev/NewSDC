import { onBeforeUnmount, ref } from 'vue';

/**
 * Busca remota conforme o usuario digita: espera uma pausa na digitacao
 * (debounce) e cancela a requisicao anterior, para que so a resposta do ultimo
 * termo chegue a tela. O endpoint recebe `?q=` e devolve uma lista JSON.
 *
 * @param {() => string} endereco funcao que devolve a URL (reativa a props)
 */
export function useBuscaSobDemanda(endereco, { minimo = 2, atraso = 300 } = {}) {
  const resultados = ref([]);
  const carregando = ref(false);
  const falhou = ref(false);
  let espera = null;
  let controle = null;

  function cancelar() {
    clearTimeout(espera);
    controle?.abort();
    controle = null;
    carregando.value = false;
  }

  function limpar() {
    cancelar();
    resultados.value = [];
    falhou.value = false;
  }

  function buscar(termo) {
    limpar();
    const limpo = String(termo ?? '').trim();
    if (limpo.length < minimo) return;
    carregando.value = true;
    espera = setTimeout(() => executar(limpo), atraso);
  }

  async function executar(termo) {
    const minha = new AbortController();
    controle = minha;
    try {
      const url = new URL(endereco(), window.location.origin);
      url.searchParams.set('q', termo);
      const resposta = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        signal: minha.signal,
      });
      if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);
      const dados = await resposta.json();
      if (controle === minha) resultados.value = Array.isArray(dados) ? dados : [];
    } catch (erro) {
      // Cancelamento e esperado quando o usuario continua digitando.
      if (erro?.name === 'AbortError' || controle !== minha) return;
      resultados.value = [];
      falhou.value = true;
    } finally {
      if (controle === minha) {
        controle = null;
        carregando.value = false;
      }
    }
  }

  onBeforeUnmount(cancelar);

  return { resultados, carregando, falhou, buscar, limpar };
}
