import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { CATEGORIAS_TEMPO } from '@/utils/paeSimulado';

const NUMEROS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

const linhaVazia = () => ({ nome: '', populacao: '', chegada_onda: '', saida: '', houve_problemas: false, ponto_valido: true, estimativa: false, nivel_emergencia: 2 });

function estadoVazio() {
  return {
    dt_realizacao: '',
    nivel_emergencia: 2,
    dt_apresentacao: '',
    num_sei: '',
    observacao: '',
    integrado: false,
    barragens_integradas: '',
    aviso_cedec_em: '',
    criterios: Object.fromEntries(NUMEROS.map((n) => [n, { atende: null, justificativa: '' }])),
    tempos: Object.fromEntries(CATEGORIAS_TEMPO.map((c) => [c.chave, []])),
    alarme: { audivel_todos: null, morador_nome: '', morador_localizacao: '' },
    informativos: {
      participacao: { populacao_zas: '', participantes: '', cadastrados_pae: '', anos_anteriores: [] },
      ensino_observacoes: '',
      recursos_observacoes: '',
      conclusao_compdec: '',
    },
    arquivo: null,
    chave_idempotencia: crypto.randomUUID(),
  };
}

// Um relatorio ja registrado vira o ponto de partida de uma nova revisao (nova chave, PDF novo).
function estadoDoRelatorio(r) {
  const participacao = r.informativos?.participacao ?? {};
  return {
    ...estadoVazio(),
    dt_realizacao: r.dt_realizacao,
    nivel_emergencia: r.nivel_emergencia,
    dt_apresentacao: r.dt_apresentacao,
    num_sei: r.num_sei,
    observacao: r.observacao ?? '',
    integrado: r.integrado,
    barragens_integradas: r.barragens_integradas ?? '',
    aviso_cedec_em: r.aviso_cedec_em ?? '',
    criterios: Object.fromEntries(NUMEROS.map((n) => [n, { atende: r.criterios?.[n]?.atende ?? null, justificativa: r.criterios?.[n]?.justificativa ?? '' }])),
    tempos: Object.fromEntries(CATEGORIAS_TEMPO.map(({ chave }) => [chave, (r.tempos?.[chave] ?? []).map((l) => ({
      nome: l.nome,
      populacao: l.populacao ?? '',
      chegada_onda: l.chegada_onda_fmt,
      saida: l.saida_fmt,
      houve_problemas: l.houve_problemas,
      ponto_valido: l.ponto_valido,
      estimativa: l.estimativa,
      nivel_emergencia: l.nivel_emergencia ?? 2,
    }))])),
    alarme: {
      audivel_todos: r.alarme?.audivel_todos ?? null,
      morador_nome: r.alarme?.morador_nome ?? '',
      morador_localizacao: r.alarme?.morador_localizacao ?? '',
    },
    informativos: {
      participacao: {
        populacao_zas: participacao.populacao_zas ?? '',
        participantes: participacao.participantes ?? '',
        cadastrados_pae: participacao.cadastrados_pae ?? '',
        anos_anteriores: (participacao.anos_anteriores ?? []).map((a) => ({ ...a })),
      },
      ensino_observacoes: r.informativos?.ensino_observacoes ?? '',
      recursos_observacoes: r.informativos?.recursos_observacoes ?? '',
      conclusao_compdec: r.informativos?.conclusao_compdec ?? '',
    },
  };
}

// O FormData do Inertia nao garante a forma de um booleano; o Laravel so aceita true/false/1/0.
function paraEnvio(valor) {
  if (typeof valor === 'boolean') return valor ? 1 : 0;
  if (valor instanceof File) return valor;
  if (Array.isArray(valor)) return valor.map(paraEnvio);
  if (valor && typeof valor === 'object') return Object.fromEntries(Object.entries(valor).map(([chave, v]) => [chave, paraEnvio(v)]));
  return valor;
}

// Estado do relatorio em edicao. Validado e indicios sao do servidor; aqui so se pede a previa.
export function usePaeSimuladoForm(protocoloId, relatorioInicial) {
  const form = useForm(relatorioInicial ? estadoDoRelatorio(relatorioInicial) : estadoVazio());
  const indicios = ref(relatorioInicial?.indicios ?? {});
  const selecionadoId = ref(relatorioInicial?.id ?? null);
  const atualizandoIndicios = ref(false);
  const erroIndicios = ref('');

  /** `relatorio` nulo abre um formulario em branco (outro simulado). */
  function carregar(relatorio) {
    form.setData(relatorio ? estadoDoRelatorio(relatorio) : estadoVazio());
    form.clearErrors();
    indicios.value = relatorio?.indicios ?? {};
    selecionadoId.value = relatorio?.id ?? null;
    erroIndicios.value = '';
  }

  async function atualizarIndicios() {
    atualizandoIndicios.value = true;
    erroIndicios.value = '';
    try {
      const { data } = await axios.post(route('pae.protocolo.simulados.indicios', protocoloId), {
        tempos: form.tempos,
        alarme: form.alarme,
      });
      indicios.value = data;
    } catch (erro) {
      if (erro.response?.status !== 422) throw erro;
      erroIndicios.value = 'Corrija os tempos (mm:ss) e o alarme para calcular os indícios.';
    } finally {
      atualizandoIndicios.value = false;
    }
  }

  /** `aoErro` deixa a tela levar o analista a aba com os erros devolvidos pelo servidor. */
  function registrar(aoErro) {
    form.transform((dados) => paraEnvio(dados)).post(route('pae.protocolo.simulados.relatorios.store', protocoloId), {
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        form.arquivo = null;
        form.chave_idempotencia = crypto.randomUUID();
      },
      onError: () => aoErro?.(),
    });
  }

  const adicionarLinha = (categoria) => form.tempos[categoria].push(linhaVazia());
  const removerLinha = (categoria, indice) => form.tempos[categoria].splice(indice, 1);
  const adicionarAno = () => form.informativos.participacao.anos_anteriores.push({ ano: '', participantes: '' });
  const removerAno = (indice) => form.informativos.participacao.anos_anteriores.splice(indice, 1);

  return { form, indicios, atualizandoIndicios, erroIndicios, selecionadoId, carregar, atualizarIndicios, registrar, adicionarLinha, removerLinha, adicionarAno, removerAno };
}
