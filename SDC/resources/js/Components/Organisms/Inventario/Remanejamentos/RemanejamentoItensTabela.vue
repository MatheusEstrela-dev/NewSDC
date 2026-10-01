<template>
  <div v-if="isDesktop" class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
      <thead>
        <tr>
          <th v-for="coluna in COLUNAS" :key="coluna" scope="col" :class="TH">{{ coluna }}</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
        <tr v-for="item in itens" :key="item.id">
          <td :class="TD">#{{ item.id }}</td>
          <td :class="TD"><span class="block max-w-[16rem] truncate" :title="item.equipamento">{{ item.equipamento }}</span></td>
          <td :class="TD">{{ item.patrimonio }}</td>
          <td :class="TD"><span class="block max-w-[12rem] truncate">{{ item.usuario_destino }}</span></td>
          <td :class="TD"><span class="block max-w-[12rem] truncate">{{ item.estacao_destino }}</span></td>
          <td :class="TD"><StatusRemanejamentoBadge :status="item.status" :label="item.status_label" /></td>
        </tr>
      </tbody>
    </table>
  </div>

  <ul v-else class="space-y-2">
    <li
      v-for="item in itens"
      :key="item.id"
      class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <div class="flex min-w-0 items-start justify-between gap-2">
        <p class="min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ item.equipamento }}</p>
        <StatusRemanejamentoBadge class="shrink-0" :status="item.status" :label="item.status_label" />
      </div>
      <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
        <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Patrimônio</dt><dd class="truncate text-slate-800 dark:text-slate-200">{{ item.patrimonio }}</dd></div>
        <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">ID</dt><dd class="text-slate-800 dark:text-slate-200">#{{ item.id }}</dd></div>
        <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Usuário destino</dt><dd class="truncate text-slate-800 dark:text-slate-200">{{ item.usuario_destino }}</dd></div>
        <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Estação destino</dt><dd class="truncate text-slate-800 dark:text-slate-200">{{ item.estacao_destino }}</dd></div>
      </dl>
    </li>
  </ul>
</template>

<script setup>
import StatusRemanejamentoBadge from '@/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue';
import { useMobile } from '@/Composables/useMobile';

defineProps({ itens: { type: Array, required: true } });

const { isDesktop } = useMobile();
const COLUNAS = ['ID', 'Equipamento', 'Patrimônio', 'Usuário destino', 'Estação destino', 'Status'];
const TH = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
</script>
