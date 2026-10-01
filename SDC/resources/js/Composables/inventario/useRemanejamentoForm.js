import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

// O select devolve string; o resto da tela compara ids numericos.
const paraId = (valor) => (valor === '' || valor === null || valor === undefined ? '' : Number(valor));

// Estado do formulario de lote. A "chave" de cada bloco so existe no cliente
// (v-for estavel ao remover); o transform tira antes de enviar.
export function useRemanejamentoForm(remanejamento, opcoes) {
  let sequencia = 0;
  const novoBloco = (dados = {}) => ({
    chave: ++sequencia,
    usuario_id: '',
    estacao_origem_id: '',
    estacao_destino_id: '',
    condicao_destino: '',
    equipamento_ids: [],
    ...dados,
  });

  const form = useForm({
    observacao: remanejamento?.observacao ?? '',
    pessoas: remanejamento
      ? remanejamento.pessoas.map((p) => novoBloco({
        ...p,
        estacao_origem_id: p.estacao_origem_id ?? '',
        estacao_destino_id: p.estacao_destino_id ?? '',
        condicao_destino: p.condicao_destino ?? '',
      }))
      : [novoBloco()],
  });

  const editando = computed(() => Boolean(remanejamento?.id));
  const equipamentosPorId = computed(() => new Map(opcoes.equipamentos.map((e) => [e.id, e])));

  const equipamentosDoUsuario = (usuarioId) => (usuarioId
    ? opcoes.equipamentos.filter((e) => e.user_id === Number(usuarioId))
    : []);
  const estacaoAtual = (usuarioId) => (usuarioId
    ? opcoes.estacoes.find((e) => e.user_id === Number(usuarioId)) ?? null
    : null);

  // Erros do servidor por bloco. Os indices mudam ao remover um bloco, entao
  // os erros por pessoa saem junto para nao cair no bloco errado.
  function limparErrosDasPessoas() {
    const chaves = Object.keys(form.errors).filter((chave) => chave.startsWith('pessoas.'));
    if (chaves.length) form.clearErrors(...chaves);
  }

  // Ids ja marcados nos outros blocos: o servidor recusa o mesmo equipamento
  // duas vezes no lote, entao a busca e a pre-marcacao nao os oferecem.
  function idsEmOutrosBlocos(bloco) {
    return form.pessoas.filter((b) => b.chave !== bloco.chave).flatMap((b) => b.equipamento_ids);
  }

  // Trocar a pessoa recarrega o bloco com o que ela tem hoje: equipamentos
  // pre-marcados (menos os bloqueados, que ficam com ela, e os que outro bloco
  // ja leva) e a estacao atual como origem -- o servidor recusa origem que nao
  // seja dela. Na edicao a estacao atual e o estado depois do lote (em geral o
  // destino dele), e o servidor valida contra o estado de antes: a origem fica
  // vazia para o usuario escolher.
  function selecionarPessoa(bloco, usuarioId) {
    const emOutros = new Set(idsEmOutrosBlocos(bloco));
    bloco.usuario_id = paraId(usuarioId);
    bloco.equipamento_ids = equipamentosDoUsuario(bloco.usuario_id)
      .filter((e) => !e.bloqueio && !emOutros.has(e.id))
      .map((e) => e.id);
    bloco.estacao_origem_id = editando.value ? '' : estacaoAtual(bloco.usuario_id)?.value ?? '';
  }

  const CAMPOS_DE_ID = ['estacao_origem_id', 'estacao_destino_id'];

  function atualizarCampo(bloco, campo, valor) {
    bloco[campo] = CAMPOS_DE_ID.includes(campo) ? paraId(valor) : valor;
  }

  function adicionarPessoa() {
    form.pessoas.push(novoBloco());
  }

  function removerPessoa(chave) {
    form.pessoas = form.pessoas.filter((bloco) => bloco.chave !== chave);
    limparErrosDasPessoas();
  }

  function alternarEquipamento(bloco, id) {
    bloco.equipamento_ids = bloco.equipamento_ids.includes(id)
      ? bloco.equipamento_ids.filter((item) => item !== id)
      : [...bloco.equipamento_ids, id];
  }

  // Equipamentos da pessoa que ninguem do lote levou: o servidor libera esses.
  function liberados(bloco) {
    const levados = new Set(form.pessoas.flatMap((b) => b.equipamento_ids));
    return equipamentosDoUsuario(bloco.usuario_id).filter((e) => !e.bloqueio && !levados.has(e.id));
  }

  // Erros de um bloco por campo. Junta as mensagens por item
  // (pessoas.N.equipamento_ids.M: repetido, inexistente) no campo do bloco,
  // sem repetir a mesma frase.
  function errosDoBloco(indice) {
    const prefixo = `pessoas.${indice}.`;
    const erros = {};
    Object.entries(form.errors).forEach(([chave, mensagem]) => {
      if (!chave.startsWith(prefixo)) return;
      const campo = chave.slice(prefixo.length).split('.')[0];
      const atual = erros[campo];
      if (!atual) erros[campo] = mensagem;
      else if (!atual.includes(mensagem)) erros[campo] = `${atual} ${mensagem}`;
    });
    return erros;
  }

  function enviar() {
    if (form.processing) return;

    form.transform((dados) => ({
      observacao: dados.observacao,
      pessoas: dados.pessoas.map(({ chave, ...p }) => ({
        ...p,
        estacao_origem_id: p.estacao_origem_id || null,
        estacao_destino_id: p.estacao_destino_id || null,
        condicao_destino: p.condicao_destino || null,
      })),
    }));

    const opcoesEnvio = { preserveScroll: true };
    if (editando.value) {
      form.put(route('inventario.remanejamentos.update', remanejamento.id), opcoesEnvio);
    } else {
      form.post(route('inventario.remanejamentos.store'), opcoesEnvio);
    }
  }

  return {
    form, editando, equipamentosPorId, equipamentosDoUsuario, selecionarPessoa, atualizarCampo,
    adicionarPessoa, removerPessoa, alternarEquipamento, liberados, errosDoBloco, idsEmOutrosBlocos, enviar,
  };
}
