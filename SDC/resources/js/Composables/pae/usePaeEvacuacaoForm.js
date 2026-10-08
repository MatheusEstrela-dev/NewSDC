import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const FABRICAS = {
  setores: () => ({ id: '', populacao: 0, comercial: false, via: 'calcada', largura: 1.5, lados: 2, distancia: 0, terreno: 'plano' }),
  rotas: () => ({ id: '', setores: [], chegada_onda: '', nivel_emergencia: 1 }),
  acessos: () => ({ id: '', largura: 1.2, terreno: 'plano', rotas: [] }),
  pontos_encontro: () => ({ nome: '', endereco: '', populacao: 0, area: 0 }),
};

// Estado da conferencia: o calculo e sempre do servidor (simular), nunca do navegador.
export function usePaeEvacuacaoForm(protocoloId, conferencia) {
  const entrada = conferencia?.entrada ?? null;
  const form = useForm({
    setores: entrada?.setores.map((s) => ({ ...s, lados: s.lados ?? 2 })) ?? [FABRICAS.setores()],
    rotas: entrada?.rotas.map(({ id, setores, chegada_onda, nivel_emergencia }) => ({ id, setores, chegada_onda, nivel_emergencia })) ?? [FABRICAS.rotas()],
    acessos: entrada?.acessos.map((a) => ({ ...a })) ?? [],
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

  watch(() => [form.setores, form.rotas, form.acessos, form.pontos_encontro, form.tte_declarado], () => {
    simulado.value = false;
  }, { deep: true });

  function entradaAtual() {
    return {
      setores: form.setores,
      rotas: form.rotas,
      acessos: form.acessos,
      pontos_encontro: form.pontos_encontro,
      tte_declarado: form.tte_declarado || null,
    };
  }

  async function simular() {
    simulando.value = true;
    errosSimulacao.value = {};
    try {
      const { data } = await axios.post(route('pae.protocolo.evacuacao.simular', protocoloId), entradaAtual());
      resultado.value = data;
      simulado.value = true;
    } catch (erro) {
      if (erro.response?.status !== 422) throw erro;
      errosSimulacao.value = Object.fromEntries(Object.entries(erro.response.data.errors).map(([campo, mensagens]) => [campo, mensagens[0]]));
    } finally {
      simulando.value = false;
    }
  }

  function registrar() {
    form.transform((dados) => ({ ...dados, tte_declarado: dados.tte_declarado || null }))
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

  return { form, resultado, simulado, simulando, erros, simular, registrar, adicionar, remover };
}
