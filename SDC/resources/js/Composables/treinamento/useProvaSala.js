import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import axios from 'axios';

const ATRASO_RETENTATIVA_MS = 3000;
const MAX_RETENTATIVAS = 3;

function mensagemDe(erro, padrao) {
  return erro?.response?.data?.message ?? padrao;
}

/**
 * Estado e chamadas da prova do aluno. Toda regra (nota, tentativas, correcao) vem do
 * backend; aqui so ha o fluxo: carregar, iniciar/retomar, salvar cada resposta, enviar.
 *
 * @param {object} opcoes
 * @param {object} opcoes.urls          carregar, iniciar, responder(tentativaId), enviar(tentativaId)
 * @param {object|null} opcoes.situacaoInicial  item.prova vindo do estado da trilha
 * @param {(estado: object) => void} opcoes.aoAtualizarEstado  recebe o EstadoTrilha apos o envio
 */
export function useProvaSala({ urls, situacaoInicial, aoAtualizarEstado }) {
  const situacao = ref(situacaoInicial ?? null);
  const ultimoResultado = ref(null);
  const tentativa = ref(null);
  const resultado = ref(null);
  const respostas = reactive({});
  const carregando = ref(false);
  const iniciando = ref(false);
  const enviando = ref(false);
  const erro = ref('');

  const emVoo = reactive(new Set());
  const sujas = reactive(new Set());
  const tentativasDeSalvar = {};
  const temporizadores = {};
  let salvouAlgo = false;

  const salvamento = computed(() => {
    if (sujas.size > 0 && emVoo.size === 0) return 'erro';
    if (emVoo.size > 0) return 'salvando';
    return salvouAlgo ? 'salvo' : 'ocioso';
  });

  const respondidas = computed(() => (tentativa.value
    ? tentativa.value.questoes.filter((q) => respostas[q.id] != null).length
    : 0));

  function aplicarPayload(dados) {
    situacao.value = dados.situacao ?? situacao.value;
    ultimoResultado.value = dados.ultimo_resultado ?? null;
    tentativa.value = dados.tentativa ?? null;

    Object.keys(respostas).forEach((chave) => delete respostas[chave]);
    (dados.tentativa?.questoes ?? []).forEach((q) => {
      if (q.resposta_alternativa_id != null) respostas[q.id] = q.resposta_alternativa_id;
    });
  }

  async function carregar() {
    if (!urls) return;

    carregando.value = true;
    erro.value = '';
    try {
      const { data } = await axios.get(urls.carregar);
      aplicarPayload(data);
    } catch (e) {
      erro.value = mensagemDe(e, 'Não foi possível carregar a prova agora.');
    } finally {
      carregando.value = false;
    }
  }

  // Cria a tentativa ou retoma a aberta (o backend decide: 201 ou 200).
  async function iniciar() {
    if (!urls || iniciando.value) return false;

    iniciando.value = true;
    erro.value = '';
    resultado.value = null;
    try {
      const { data } = await axios.post(urls.iniciar);
      aplicarPayload(data);

      return true;
    } catch (e) {
      erro.value = mensagemDe(e, 'Não foi possível iniciar a prova agora.');

      return false;
    } finally {
      iniciando.value = false;
    }
  }

  function agendarRetentativa(questaoId) {
    tentativasDeSalvar[questaoId] = (tentativasDeSalvar[questaoId] ?? 0) + 1;
    if (tentativasDeSalvar[questaoId] > MAX_RETENTATIVAS) return;

    temporizadores[questaoId] = setTimeout(
      () => sincronizar(questaoId),
      ATRASO_RETENTATIVA_MS * tentativasDeSalvar[questaoId],
    );
  }

  // Envia a resposta atual da questao; se o aluno trocou de alternativa durante o voo, repete.
  async function sincronizar(questaoId) {
    if (emVoo.has(questaoId) || !tentativa.value) return;

    clearTimeout(temporizadores[questaoId]);
    emVoo.add(questaoId);
    try {
      let alvo;
      do {
        alvo = respostas[questaoId];
        await axios.put(urls.responder(tentativa.value.id), { questao_id: questaoId, alternativa_id: alvo });
      } while (respostas[questaoId] !== alvo);

      sujas.delete(questaoId);
      tentativasDeSalvar[questaoId] = 0;
      salvouAlgo = true;
    } catch {
      sujas.add(questaoId);
      agendarRetentativa(questaoId);
    } finally {
      emVoo.delete(questaoId);
    }
  }

  function responder(questaoId, alternativaId) {
    if (respostas[questaoId] === alternativaId) return;

    respostas[questaoId] = alternativaId;
    sujas.add(questaoId);
    tentativasDeSalvar[questaoId] = 0;
    sincronizar(questaoId);
  }

  function tentarSalvarNovamente() {
    [...sujas].forEach((questaoId) => {
      tentativasDeSalvar[questaoId] = 0;
      sincronizar(questaoId);
    });
  }

  async function aguardarSalvamento() {
    tentarSalvarNovamente();
    while (emVoo.size > 0) {
      await new Promise((resolver) => setTimeout(resolver, 100));
    }

    return sujas.size === 0;
  }

  async function enviar() {
    if (!tentativa.value || enviando.value) return false;

    enviando.value = true;
    erro.value = '';
    try {
      if (!(await aguardarSalvamento())) {
        erro.value = 'Algumas respostas não foram salvas. Verifique sua conexão e tente enviar novamente.';

        return false;
      }

      const { data } = await axios.post(urls.enviar(tentativa.value.id));
      resultado.value = data.resultado;
      situacao.value = data.situacao ?? situacao.value;
      tentativa.value = null;
      aoAtualizarEstado?.(data.estado);

      return true;
    } catch (e) {
      erro.value = mensagemDe(e, 'Não foi possível enviar a prova agora.');

      return false;
    } finally {
      enviando.value = false;
    }
  }

  function voltarParaRegras() {
    resultado.value = null;
    erro.value = '';
  }

  onBeforeUnmount(() => Object.values(temporizadores).forEach(clearTimeout));

  return {
    situacao,
    ultimoResultado,
    tentativa,
    resultado,
    respostas,
    respondidas,
    salvamento,
    carregando,
    iniciando,
    enviando,
    erro,
    carregar,
    iniciar,
    responder,
    tentarSalvarNovamente,
    enviar,
    voltarParaRegras,
  };
}
