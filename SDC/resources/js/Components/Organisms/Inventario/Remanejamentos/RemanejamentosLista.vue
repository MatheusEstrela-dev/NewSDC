<template>
  <!-- Mesmo corte do Catalogo: tabela a partir de lg, bloco abaixo disso. -->
  <div
    v-if="isDesktop"
    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
  >
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-900/50">
          <tr>
            <th scope="col" :class="TH">Data / hora</th>
            <th scope="col" :class="TH">Pessoas</th>
            <th scope="col" :class="TH">Itens</th>
            <th scope="col" :class="TH">Status</th>
            <th scope="col" class="table-actions-head px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <template v-for="lote in lotes" :key="lote.id">
            <tr class="table-row-solid transition-colors">
              <td :class="TD_FORTE">{{ formatarDataHora(lote.criado_em) }}</td>
              <td :class="TD"><span class="block max-w-[22rem] truncate" :title="lote.pessoas.join(', ')">{{ lote.pessoas.join(', ') }}</span></td>
              <td :class="TD"><Badge variant="info" size="sm">{{ rotuloItens(lote) }}</Badge></td>
              <td :class="TD"><StatusRemanejamentoBadge :status="lote.status" :label="lote.status_label" /></td>
              <td class="table-actions-cell px-3 py-2">
                <RemanejamentoAcoes
                  :lote="lote"
                  :pode="pode"
                  :expandido="estaExpandido(lote.id)"
                  :processando="processando"
                  v-bind="repasse"
                  @expandir="alternar(lote.id)"
                />
              </td>
            </tr>
            <tr v-if="estaExpandido(lote.id)">
              <td colspan="5" class="border-t border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-700/50 dark:bg-slate-900/40">
                <p v-if="lote.observacao" class="mb-3 text-sm text-slate-600 dark:text-slate-300">{{ lote.observacao }}</p>
                <RemanejamentoItensTabela :itens="lote.itens" />
              </td>
            </tr>
          </template>
          <tr v-if="lotes.length === 0">
            <td colspan="5" class="p-0">
              <ListEmptyState title="Nenhum remanejamento encontrado" helper='Ajuste os filtros ou use "Novo remanejamento".' />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div v-else class="space-y-3">
    <article
      v-for="lote in lotes"
      :key="lote.id"
      class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <header class="flex min-w-0 items-start justify-between gap-3">
        <div class="min-w-0">
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatarDataHora(lote.criado_em) }}</p>
          <h3 class="break-words text-sm font-bold text-slate-900 dark:text-slate-100">{{ lote.pessoas.join(', ') }}</h3>
        </div>
        <StatusRemanejamentoBadge class="shrink-0" :status="lote.status" :label="lote.status_label" />
      </header>
      <div class="mt-2"><Badge variant="info" size="sm">{{ rotuloItens(lote) }}</Badge></div>
      <footer class="mt-3 border-t border-slate-200 pt-3 dark:border-slate-700/50">
        <RemanejamentoAcoes
          :lote="lote"
          :pode="pode"
          :expandido="estaExpandido(lote.id)"
          :processando="processando"
          v-bind="repasse"
          @expandir="alternar(lote.id)"
        />
      </footer>
      <div v-if="estaExpandido(lote.id)" class="mt-3 border-t border-slate-200 pt-3 dark:border-slate-700/50">
        <p v-if="lote.observacao" class="mb-3 break-words text-sm text-slate-600 dark:text-slate-300">{{ lote.observacao }}</p>
        <RemanejamentoItensTabela :itens="lote.itens" />
      </div>
    </article>
    <div
      v-if="lotes.length === 0"
      class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <ListEmptyState title="Nenhum remanejamento encontrado" helper='Ajuste os filtros ou use "Novo remanejamento".' />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import StatusRemanejamentoBadge from '@/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue';
import RemanejamentoAcoes from '@/Components/Organisms/Inventario/Remanejamentos/RemanejamentoAcoes.vue';
import RemanejamentoItensTabela from '@/Components/Organisms/Inventario/Remanejamentos/RemanejamentoItensTabela.vue';
import { useMobile } from '@/Composables/useMobile';
import { formatarDataHora } from '@/Support/demandasFormat';

defineProps({
  lotes: { type: Array, required: true },
  pode: { type: Object, required: true },
  processando: { type: Boolean, default: false },
});
const emit = defineEmits(['seplag', 'planilha', 'editar', 'desfazer', 'chamado', 'abrir-chamado']);

const { isDesktop } = useMobile();

// Repassa as acoes de RemanejamentoAcoes para o template sem reescrever cada uma.
const repasse = {
  onSeplag: (lote) => emit('seplag', lote),
  onPlanilha: (lote) => emit('planilha', lote),
  onEditar: (lote) => emit('editar', lote),
  onDesfazer: (lote) => emit('desfazer', lote),
  onChamado: (lote) => emit('chamado', lote),
  onAbrirChamado: (lote) => emit('abrir-chamado', lote),
};

// Guardado por id: o reload do tempo real reordena a lista sem fechar o que esta aberto.
const expandidos = ref([]);
const estaExpandido = (id) => expandidos.value.includes(id);
function alternar(id) {
  expandidos.value = estaExpandido(id) ? expandidos.value.filter((item) => item !== id) : [...expandidos.value, id];
}

const rotuloItens = (lote) => `${lote.itens_movidos} ${lote.itens_movidos === 1 ? 'item movido' : 'itens movidos'}`;

const TH = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'whitespace-nowrap px-3 py-2 text-sm font-semibold text-slate-900 dark:text-slate-100';
</script>
