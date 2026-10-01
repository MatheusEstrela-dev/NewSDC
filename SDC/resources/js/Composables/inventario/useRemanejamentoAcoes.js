import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

// Acoes de um lote na listagem. Erro de dominio volta como erro de sessao
// (chave remanejamento/configuracao) e o template mostra no aviso do topo.
export function useRemanejamentoAcoes() {
  const loteParaDesfazer = ref(null);
  const processando = ref(false);

  // Um clique = uma requisicao: a trava sobe antes do post (e nao no onStart)
  // para que o segundo clique do duplo clique ja encontre a acao em andamento.
  function postar(nomeRota, lote, extra = {}) {
    if (processando.value) return;
    processando.value = true;
    router.post(route(nomeRota, lote.id), {}, {
      preserveScroll: true,
      ...extra,
      onFinish: () => { processando.value = false; },
    });
  }

  function pedirDesfazer(lote) {
    loteParaDesfazer.value = lote;
  }

  function cancelarDesfazer() {
    if (!processando.value) loteParaDesfazer.value = null;
  }

  function confirmarDesfazer() {
    if (!loteParaDesfazer.value) return;
    postar('inventario.remanejamentos.desfazer', loteParaDesfazer.value, {
      onSuccess: () => { loteParaDesfazer.value = null; },
    });
  }

  function editar(lote) {
    router.visit(route('inventario.remanejamentos.edit', lote.id));
  }

  function baixarPlanilha(lote) {
    window.location.href = route('inventario.remanejamentos.planilha', lote.id);
  }

  function registrarChamado(lote) {
    postar('inventario.remanejamentos.chamado', lote);
  }

  function abrirChamado(lote) {
    router.visit(route('demandas.show', lote.demanda.id));
  }

  // Reenvio a SEPLAG e permitido de proposito; o que a trava impede e o duplo clique.
  function enviarSeplag(lote) {
    postar('inventario.remanejamentos.seplag', lote);
  }

  return {
    loteParaDesfazer, processando, pedirDesfazer, cancelarDesfazer, confirmarDesfazer,
    editar, baixarPlanilha, registrarChamado, abrirChamado, enviarSeplag,
  };
}
