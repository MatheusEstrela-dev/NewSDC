<template>
  <div class="space-y-6 pb-8">
    <PageHeader
      title="Movimentações"
      description="Remanejamentos em lote e empréstimos de equipamentos"
      :icon="ArchiveBoxIcon"
      variant="gradient"
      :espaco-inferior="false"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <Button v-if="pode.emprestar" variant="secondary" size="md" :icon="PlusIcon" icon-position="left" @click="emprestimoAberto = true">
            Novo empréstimo
          </Button>
          <Button v-if="pode.criar" variant="primary" size="md" :icon="PlusIcon" icon-position="left" @click="$emit('novo')">
            Novo remanejamento
          </Button>
        </div>
      </template>
    </PageHeader>

    <div
      v-if="errosDaPagina.length && !avisoDispensado"
      role="alert"
      class="flex items-start gap-3 rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
    >
      <div class="min-w-0 flex-1">
        <p v-for="erro in errosDaPagina" :key="erro" class="break-words">{{ erro }}</p>
      </div>
      <button
        type="button"
        class="-m-2 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 dark:hover:bg-red-500/20"
        aria-label="Fechar aviso"
        @click="avisoDispensado = true"
      >
        <XMarkIcon class="h-5 w-5" aria-hidden="true" />
      </button>
    </div>

    <RemanejamentosFiltersSection :filtros="filtros" :opcoes-status="opcoes.status" @aplicar="$emit('aplicar')" @limpar="$emit('limpar')" />

    <ListContainer title="Remanejamentos" :icon="ArrowsRightLeftIcon" :count="paginacao.total">
      <RemanejamentosLista
        :lotes="lotes"
        :pode="pode"
        :processando="processando"
        @seplag="enviarSeplag"
        @planilha="baixarPlanilha"
        @editar="editar"
        @desfazer="pedirDesfazer"
        @chamado="registrarChamado"
        @abrir-chamado="abrirChamado"
      />
      <Pagination class="mt-4" :pagination="paginacao" @page-change="(p) => $emit('pagina', p)" />
    </ListContainer>

    <ListContainer title="Empréstimos" :icon="ArchiveBoxIcon" :count="avulsas.total">
      <EmprestimosAvulsosLista :movimentacoes="avulsas.data" :pode-devolver="pode.devolver" @devolver="(item) => (paraDevolver = item)" />
      <Pagination class="mt-4" :pagination="avulsas" @page-change="(p) => $emit('pagina-avulsas', p)" />
    </ListContainer>

    <ConfirmDialog
      :is-open="dialogoAberto"
      v-bind="dialogo"
      :loading="processando"
      @confirm="confirmarAcao"
      @cancel="cancelarConfirmacao"
    />

    <ConfirmDialog
      :is-open="Boolean(paraDevolver)"
      title="Registrar devolução"
      message="Confirmar a devolução deste empréstimo?"
      variant="warning"
      confirm-text="Devolver"
      :loading="devolvendo"
      @confirm="devolver"
      @cancel="cancelarDevolucao"
    />

    <EmprestimoFormModal
      :show="emprestimoAberto"
      :equipamentos="equipamentos"
      :usuarios="usuarios"
      :estacoes="estacoes"
      @close="emprestimoAberto = false"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ArchiveBoxIcon, ArrowsRightLeftIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import RemanejamentosFiltersSection from '@/Components/Organisms/Inventario/Remanejamentos/RemanejamentosFiltersSection.vue';
import RemanejamentosLista from '@/Components/Organisms/Inventario/Remanejamentos/RemanejamentosLista.vue';
import EmprestimosAvulsosLista from '@/Components/Organisms/Inventario/Movimentacoes/EmprestimosAvulsosLista.vue';
import EmprestimoFormModal from '@/Components/Organisms/Inventario/Movimentacoes/EmprestimoFormModal.vue';
import { useRemanejamentoAcoes } from '@/Composables/inventario/useRemanejamentoAcoes';

defineProps({
  lotes: { type: Array, required: true },
  paginacao: { type: Object, required: true },
  avulsas: { type: Object, required: true },
  filtros: { type: Object, required: true },
  opcoes: { type: Object, required: true },
  pode: { type: Object, required: true },
  equipamentos: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  estacoes: { type: Array, default: () => [] },
});
defineEmits(['novo', 'aplicar', 'limpar', 'pagina', 'pagina-avulsas']);

const {
  dialogo, dialogoAberto, processando, cancelarConfirmacao, confirmarAcao,
  pedirDesfazer, enviarSeplag, registrarChamado, editar, baixarPlanilha, abrirChamado,
} = useRemanejamentoAcoes();

const emprestimoAberto = ref(false);
const paraDevolver = ref(null);
const devolvendo = ref(false);

// Erros de dominio das acoes do lote e da devolucao chegam como erro de sessao.
const page = usePage();
const errosDaPagina = computed(() => ['remanejamento', 'configuracao', 'movimentacao']
  .map((chave) => page.props.errors?.[chave])
  .filter(Boolean));

// Dispensar vale para os erros atuais: a proxima visita do usuario traz um
// errors novo e o aviso volta. O reload ao vivo nao troca o errors, entao nao
// reabre um aviso fechado nem apaga um aberto.
const avisoDispensado = ref(false);
watch(() => page.props.errors, () => { avisoDispensado.value = false; });

function cancelarDevolucao() {
  if (!devolvendo.value) paraDevolver.value = null;
}

function devolver() {
  if (devolvendo.value || !paraDevolver.value) return;
  devolvendo.value = true;
  router.post(route('inventario.movimentacoes.devolver', paraDevolver.value.id), {}, {
    preserveScroll: true,
    onFinish: () => {
      devolvendo.value = false;
      paraDevolver.value = null;
    },
  });
}
</script>
