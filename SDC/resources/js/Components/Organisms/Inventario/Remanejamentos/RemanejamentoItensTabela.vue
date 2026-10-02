<template>
  <ListaResponsiva :itens="itens" :colunas="COLUNAS" aninhada>
    <template #linha="{ item, td }">
      <tr>
        <td :class="td">#{{ item.id }}</td>
        <td :class="td"><span class="block max-w-[16rem] truncate" :title="item.equipamento">{{ item.equipamento }}</span></td>
        <td :class="td">{{ item.patrimonio }}</td>
        <td :class="td"><span class="block max-w-[12rem] truncate" :title="item.usuario_destino">{{ item.usuario_destino }}</span></td>
        <td :class="td"><span class="block max-w-[12rem] truncate" :title="item.estacao_destino">{{ item.estacao_destino }}</span></td>
        <td :class="td"><StatusRemanejamentoBadge :status="item.status" :label="item.status_label" /></td>
      </tr>
    </template>

    <template #cartao="{ item }">
      <div class="flex min-w-0 items-start justify-between gap-2">
        <p class="min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100" :title="item.equipamento">{{ item.equipamento }}</p>
        <StatusRemanejamentoBadge class="shrink-0" :status="item.status" :label="item.status_label" />
      </div>
      <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
        <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Patrimônio</dt><dd class="truncate text-slate-800 dark:text-slate-200" :title="item.patrimonio">{{ item.patrimonio }}</dd></div>
        <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">ID</dt><dd class="text-slate-800 dark:text-slate-200">#{{ item.id }}</dd></div>
        <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Usuário destino</dt><dd class="truncate text-slate-800 dark:text-slate-200" :title="item.usuario_destino">{{ item.usuario_destino }}</dd></div>
        <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Estação destino</dt><dd class="truncate text-slate-800 dark:text-slate-200" :title="item.estacao_destino">{{ item.estacao_destino }}</dd></div>
      </dl>
    </template>
  </ListaResponsiva>
</template>

<script setup>
import ListaResponsiva from '@/Components/Molecules/List/ListaResponsiva.vue';
import StatusRemanejamentoBadge from '@/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue';

defineProps({ itens: { type: Array, required: true } });

const COLUNAS = ['ID', 'Equipamento', 'Patrimônio', 'Usuário destino', 'Estação destino', 'Status'];
</script>
