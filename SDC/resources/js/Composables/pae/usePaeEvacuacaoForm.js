import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { separarLista } from '@/utils/paeEvacuacao';

const FABRICAS = {
  setores: () => ({ id: '', populacao: '', comercial: false, via: 'calcada', largura: '', lados: 2, distancia: '', terreno: 'plano' }),
  rotas: () => ({ id: '', setores_texto: '', chegada_onda: '', nivel_emergencia: 1 }),
  acessos: () => ({ id: '', largura: '', terreno: 'plano', rotas_texto: '' }),
  pontos_encontro: () => ({ nome: '', endereco: '', populacao: '', area: '' }),
};

// Estado da conferencia: o calculo e sempre do servidor (simular), nunca do navegador.
export function usePaeEvacuacaoForm(protocoloId, conferencia) {
  const entrada = conferencia?.entrada ?? null;
  const form = useForm({
    setores: entrada?.setores.map((s) => ({ ...s, lados: s.lados ?? 2 })) ?? [FABRICAS.setores()],
    rotas: entrada?.rotas.map(({ id, setores, chegada_onda, nivel_emergencia }) => ({ id, setores_texto: setores.join(', '), chegada_onda, nivel_emergencia })) ?? [FABRICAS.rotas()],
    acessos: entrada?.acessos.map(({ rotas, ...resto }) => ({ ...resto, rotas_texto: rotas.join(', ') })) ?? [],
    pontos_encontro: entrada?.pontos_encontro.map((p) => ({ ...p })) ?? [FABRICAS.pontos_encontro()],
    tte_declarado: entrada?.tte_declarado ?? '',
    num_sei: '',
    observacao: '',
    chave_idempotencia: crypto.randomUUID(),
  });
  const resultado = ref(conferencia?.resultado ?? null);
  const simulado = ref(conferencia !== null);
  const simulando = ref(false);
  const errosSimulacao = ref({});
  const erroSimulacao = ref('');
  let geracao = 0; // muda a cada edicao: respostas de simulacoes antigas sao descartadas

  watch(() => [form.setores, form.rotas, form.acessos, form.pontos_encontro, form.tte_declarado], () => {
    geracao += 1;
    simulado.value = false;
    errosSimulacao.value = {};
    erroSimulacao.value = '';
  }, { deep: true });

  // Unico ponto que converte o texto digitado em listas: o servidor recebe sempre arrays.
  function paraPayload(dados) {
    return {
      setores: dados.setores,
      rotas: dados.rotas.map(({ setores_texto, ...rota }) => ({ ...rota, setores: separarLista(setores_texto) })),
      acessos: dados.acessos.map(({ rotas_texto, ...acesso }) => ({ ...acesso, rotas: separarLista(rotas_texto) })),
      pontos_encontro: dados.pontos_encontro,
      tte_declarado: dados.tte_declarado || null,
    };
  }

  async function simular() {
    simulando.value = true;
    errosSimulacao.value = {};
    erroSimulacao.value = '';
    const minhaGeracao = geracao;
    try {
      const { data } = await axios.post(route('pae.protocolo.evacuacao.simular', protocoloId), paraPayload(form.data()));
      if (minhaGeracao !== geracao) return;
      resultado.value = data;
      simulado.value = true;
    } catch (erro) {
      if (minhaGeracao !== geracao) return;
      if (erro.response?.status === 422) {
        errosSimulacao.value = Object.fromEntries(Object.entries(erro.response.data.errors ?? {}).map(([campo, mensagens]) => [campo, mensagens[0]]));
      } else {
        erroSimulacao.value = 'Não foi possível simular agora. Tente novamente.';
      }
    } finally {
      simulando.value = false;
    }
  }

  function registrar() {
    form.transform((dados) => ({ ...paraPayload(dados), num_sei: dados.num_sei, observacao: dados.observacao, chave_idempotencia: dados.chave_idempotencia }))
      .post(route('pae.protocolo.evacuacao.registrar', protocoloId), {
        preserveScroll: true,
        onSuccess: () => {
          form.reset('num_sei', 'observacao');
          form.chave_idempotencia = crypto.randomUUID();
        },
      });
  }

  function adicionar(lista) {
    form[lista].push(FABRICAS[lista]());
  }

  function remover(lista, indice) {
    form[lista].splice(indice, 1);
  }

  const erros = computed(() => ({ ...errosSimulacao.value, ...form.errors }));

  return { form, resultado, simulado, simulando, erros, erroSimulacao, simular, registrar, adicionar, remover };
}
