<template>
  <div v-if="isDesktop" class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
      <thead class="bg-slate-50 dark:bg-slate-900/50">
        <tr>
          <th v-for="coluna in COLUNAS" :key="coluna" scope="col" :class="TH">{{ coluna }}</th>
          <th scope="col" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ação</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
        <tr v-for="item in movimentacoes" :key="item.id" class="table-row-solid">
          <td :class="TD">{{ formatarDataHora(item.data_saida) }}</td>
          <td :class="TD"><span class="block max-w-[16rem] truncate">{{ item.equipamento?.nome ?? '—' }}</span></td>
          <td :class="TD">{{ item.equipamento?.patrimonio ?? '—' }}</td>
          <td :class="TD"><span class="block max-w-[12rem] truncate">{{ item.usuario_destino?.name ?? '—' }}</span></td>
          <td :class="TD"><StatusRemanejamentoBadge :status="item.status" :label="rotuloStatus(item.status)" /></td>
          <td class="px-3 py-2 text-right">
            <Button v-if="podeDevolver && item.status === 'ativo'" variant="success" size="sm" @click="$emit('devolver', item)">Devolver</Button>
          </td>
        </tr>
        <tr v-if="movimentacoes.length === 0">
          <td :colspan="COLUNAS.length + 1" class="p-0"><ListEmptyState :title="VAZIO.titulo" :helper="VAZIO.ajuda" /></td>
        </tr>
      </tbody>
    </table>
  </div>

  <div v-else class="space-y-3">
    <article
      v-for="item in movimentacoes"
      :key="item.id"
      class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <div class="flex min-w-0 items-start justify-between gap-2">
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ item.equipamento?.nome ?? '—' }}</p>
          <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ item.equipamento?.patrimonio ?? '—' }} · {{ formatarDataHora(item.data_saida) }}</p>
        </div>
        <StatusRemanejamentoBadge class="shrink-0" :status="item.status" :label="rotuloStatus(item.status)" />
      </div>
      <p class="mt-2 truncate text-xs text-slate-600 dark:text-slate-300">Com: {{ item.usuario_destino?.name ?? '—' }}</p>
      <div v-if="podeDevolver && item.status === 'ativo'" class="mt-3 flex justify-end">
        <Button variant="success" size="sm" @click="$emit('devolver', item)">Devolver</Button>
      </div>
    </article>
    <div
      v-if="movimentacoes.length === 0"
      class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <ListEmptyState :title="VAZIO.titulo" :helper="VAZIO.ajuda" />
    </div>
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import StatusRemanejamentoBadge from '@/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue';
import { useMobile } from '@/Composables/useMobile';
import { formatarDataHora } from '@/Support/demandasFormat';

defineProps({
  movimentacoes: { type: Array, required: true },
  podeDevolver: { type: Boolean, default: false },
});
defineEmits(['devolver']);

const { isDesktop } = useMobile();

// A avulsa chega crua do model (sem status_label), entao o rotulo sai daqui.
const ROTULO = { ativo: 'Ativo', devolvido: 'Devolvido' };
const rotuloStatus = (status) => ROTULO[status] ?? status;

const VAZIO = { titulo: 'Nenhum empréstimo encontrado', ajuda: 'Empréstimos avulsos aparecem aqui.' };
const COLUNAS = ['Saída', 'Equipamento', 'Patrimônio', 'Com', 'Status'];
const TH = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
</script>
