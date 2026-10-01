<template>
  <ListaResponsiva :itens="movimentacoes" :colunas="COLUNAS" rotulo-acoes="Ação" :vazio="VAZIO">
    <template #linha="{ item, td }">
      <tr class="table-row-solid">
        <td :class="td">{{ formatarDataHora(item.data_saida) }}</td>
        <td :class="td"><span class="block max-w-[16rem] truncate">{{ item.equipamento?.nome ?? '—' }}</span></td>
        <td :class="td">{{ item.equipamento?.patrimonio ?? '—' }}</td>
        <td :class="td"><span class="block max-w-[12rem] truncate">{{ item.usuario_destino?.name ?? '—' }}</span></td>
        <td :class="td"><StatusRemanejamentoBadge :status="item.status" :label="rotuloStatus(item.status)" /></td>
        <td class="px-3 py-2 text-right">
          <Button v-if="podeDevolver && item.status === 'ativo'" variant="success" size="sm" @click="$emit('devolver', item)">Devolver</Button>
        </td>
      </tr>
    </template>

    <template #cartao="{ item }">
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
    </template>
  </ListaResponsiva>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ListaResponsiva from '@/Components/Molecules/Inventario/ListaResponsiva.vue';
import StatusRemanejamentoBadge from '@/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue';
import { formatarDataHora } from '@/utils/dateFormatter';

defineProps({
  movimentacoes: { type: Array, required: true },
  podeDevolver: { type: Boolean, default: false },
});
defineEmits(['devolver']);

// A avulsa chega crua do model (sem status_label), entao o rotulo sai daqui.
const ROTULO = { ativo: 'Ativo', devolvido: 'Devolvido' };
const rotuloStatus = (status) => ROTULO[status] ?? status;

const VAZIO = { titulo: 'Nenhum empréstimo encontrado', ajuda: 'Empréstimos avulsos aparecem aqui.' };
const COLUNAS = ['Saída', 'Equipamento', 'Patrimônio', 'Com', 'Status'];
</script>
