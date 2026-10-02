import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { formatarDataHora } from '@/utils/dateFormatter';

// Nome curto do lote para o dialogo: data e as primeiras pessoas.
function identificarLote(lote) {
  const pessoas = lote.pessoas ?? [];
  const nomes = pessoas.length > 3 ? `${pessoas.slice(0, 3).join(', ')} e mais ${pessoas.length - 3}` : pessoas.join(', ');
  return `o lote de ${formatarDataHora(lote.criado_em)}${nomes ? ` (${nomes})` : ''}`;
}

function rotuloReenvio(lote) {
  const { envios, ultimo_envio_em: ultimo } = lote.seplag ?? {};
  return envios ? ` Já enviado ${envios} vez(es), último em ${formatarDataHora(ultimo)}.` : '';
}

// Acoes que mexem fora da tela (desfaz o lote, manda e-mail, abre demanda)
// passam por confirmacao: no toque nao ha title para avisar o que o icone faz.
const ACOES_CONFIRMADAS = {
  desfazer: {
    rota: 'inventario.remanejamentos.desfazer',
    dialogo: (lote) => ({
      title: 'Desfazer remanejamento',
      message: `Desfazer ${identificarLote(lote)}?`,
      description: 'Equipamentos, estações e itens liberados voltam ao estado de antes do lote. Se algum equipamento foi movimentado depois, nada é alterado.',
      variant: 'danger',
      confirmText: 'Desfazer',
    }),
  },
  seplag: {
    rota: 'inventario.remanejamentos.seplag',
    dialogo: (lote) => ({
      title: lote.seplag?.envios ? 'Reenviar à SEPLAG' : 'Enviar à SEPLAG',
      message: `Enviar ${identificarLote(lote)} à SEPLAG?`,
      description: `Um e-mail com a planilha do lote é enviado à SEPLAG.${rotuloReenvio(lote)}`,
      variant: 'info',
      confirmText: 'Enviar',
    }),
  },
  chamado: {
    rota: 'inventario.remanejamentos.chamado',
    dialogo: (lote) => ({
      title: 'Registrar chamado',
      message: `Registrar um chamado para ${identificarLote(lote)}?`,
      description: 'Uma demanda já resolvida é aberta em Demandas, na data do lote, documentando os itens movidos.',
      variant: 'success',
      confirmText: 'Registrar',
    }),
  },
};

// Acoes de um lote na listagem. Erro de dominio volta como erro de sessao
// (chave remanejamento/configuracao) e o template mostra no aviso do topo.
export function useRemanejamentoAcoes() {
  const pendente = ref(null);
  // Conteudo guardado a parte: ao fechar, o dialogo some com o texto que tinha,
  // sem trocar para vazio durante a transicao de saida.
  const dialogo = ref({ title: '', message: '' });
  const processando = ref(false);
  const dialogoAberto = computed(() => Boolean(pendente.value));

  // Um clique = uma requisicao: a trava sobe antes do post (e nao no onStart)
  // para que o segundo clique do duplo clique ja encontre a acao em andamento.
  function postar(nomeRota, lote, aoTerminar = () => {}) {
    if (processando.value) return;
    processando.value = true;
    router.post(route(nomeRota, lote.id), {}, {
      preserveScroll: true,
      onFinish: () => {
        processando.value = false;
        aoTerminar();
      },
    });
  }

  function pedir(acao, lote) {
    if (processando.value) return;
    pendente.value = { acao, lote };
    dialogo.value = ACOES_CONFIRMADAS[acao].dialogo(lote);
  }

  function cancelarConfirmacao() {
    if (!processando.value) pendente.value = null;
  }

  function confirmarAcao() {
    if (!pendente.value) return;
    const { acao, lote } = pendente.value;
    // Fecha no fim, com sucesso ou erro: o erro de dominio aparece no aviso
    // do topo, que o dialogo aberto esconderia.
    postar(ACOES_CONFIRMADAS[acao].rota, lote, () => {
      pendente.value = null;
    });
  }

  function editar(lote) {
    router.visit(route('inventario.remanejamentos.edit', lote.id));
  }

  function baixarPlanilha(lote) {
    window.location.href = route('inventario.remanejamentos.planilha', lote.id);
  }

  function abrirChamado(lote) {
    router.visit(route('demandas.show', lote.demanda.id));
  }

  // Reenvio a SEPLAG e permitido de proposito; a trava impede o duplo clique.
  return {
    dialogo, dialogoAberto, processando, cancelarConfirmacao, confirmarAcao,
    pedirDesfazer: (lote) => pedir('desfazer', lote),
    enviarSeplag: (lote) => pedir('seplag', lote),
    registrarChamado: (lote) => pedir('chamado', lote),
    editar, baixarPlanilha, abrirChamado,
  };
}
