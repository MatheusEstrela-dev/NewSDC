import axios from 'axios';
import { computed, reactive, ref } from 'vue';

// Checklist GMG 83 de uma secao da pasta: carrega, guarda o rascunho local e
// envia so os itens alterados.
export function usePaeChecklist(protocoloId, secao) {
  const dados = ref(null);
  const carregando = ref(false);
  const salvando = ref(false);
  const erroGeral = ref('');
  const erros = ref({});
  const rascunho = reactive({});
  let original = {};

  const itens = computed(() => (dados.value?.grupos ?? []).flatMap((grupo) => grupo.itens));

  function aplicar(resposta) {
    dados.value = resposta;
    original = {};
    Object.keys(rascunho).forEach((codigo) => delete rascunho[codigo]);
    itens.value.forEach((item) => {
      original[item.codigo] = { resposta: item.resposta ?? null, observacao: item.observacao ?? '' };
      rascunho[item.codigo] = { ...original[item.codigo] };
    });
    erros.value = {};
  }

  const alterados = computed(() => Object.keys(rascunho).filter((codigo) => {
    const atual = rascunho[codigo];
    const base = original[codigo];
    return atual.resposta !== base.resposta || atual.observacao.trim() !== base.observacao.trim();
  }));

  const resumo = computed(() => {
    const avaliaveis = itens.value.filter((item) => item.tipo === 'resposta');
    const respondidos = avaliaveis.filter((item) => rascunho[item.codigo]?.resposta);
    return {
      total: avaliaveis.length,
      respondidos: respondidos.length,
      nao: respondidos.filter((item) => rascunho[item.codigo].resposta === 'nao').length,
    };
  });

  async function carregar() {
    carregando.value = true;
    erroGeral.value = '';
    try {
      const { data } = await axios.get(route('pae.protocolo.checklist.show', [protocoloId, secao]));
      aplicar(data);
    } catch {
      erroGeral.value = 'Não foi possível carregar o checklist. Recarregue a página.';
    } finally {
      carregando.value = false;
    }
  }

  async function salvar() {
    const codigos = alterados.value;
    if (!codigos.length) return false;

    salvando.value = true;
    erroGeral.value = '';
    try {
      const respostas = codigos.map((codigo) => ({ codigo, ...rascunho[codigo] }));
      const { data } = await axios.put(route('pae.protocolo.checklist.salvar', [protocoloId, secao]), { respostas });
      aplicar(data);
      return true;
    } catch (erro) {
      const errosServidor = erro.response?.status === 422 ? erro.response.data.errors ?? {} : null;
      if (!errosServidor) {
        erroGeral.value = 'Não foi possível salvar o checklist. Tente novamente.';
        return false;
      }
      erros.value = Object.fromEntries(Object.entries(errosServidor).map(([campo, mensagens]) => {
        const indice = campo.match(/^respostas\.(\d+)\./)?.[1];
        return [indice !== undefined ? codigos[Number(indice)] : campo, mensagens[0]];
      }));
      erroGeral.value = erros.value.protocolo ?? 'Corrija os itens destacados.';
      return false;
    } finally {
      salvando.value = false;
    }
  }

  function descartar() {
    Object.keys(original).forEach((codigo) => { rascunho[codigo] = { ...original[codigo] }; });
    erros.value = {};
  }

  return { dados, itens, rascunho, alterados, resumo, carregando, salvando, erroGeral, erros, carregar, salvar, descartar };
}
