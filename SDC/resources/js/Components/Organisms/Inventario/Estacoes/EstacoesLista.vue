<template>
  <ListaResponsiva :itens="estacoes" :colunas="COLUNAS" :rotulo-acoes="temAcoes ? 'Ações' : ''" acoes-fixas :vazio="VAZIO">
    <template #linha="{ item, td, tdForte }">
      <tr class="table-row-solid transition-colors">
        <td :class="tdForte"><span class="block max-w-[16rem] truncate" :title="item.nome">{{ item.nome }}</span></td>
        <td :class="td"><span class="block max-w-[14rem] truncate" :title="item.usuario?.name">{{ item.usuario?.name || '—' }}</span></td>
        <td :class="td"><span class="block max-w-[12rem] truncate" :title="item.ponto_rede">{{ item.ponto_rede || '—' }}</span></td>
        <td :class="td">{{ item.equipamentos_count }}</td>
        <td :class="td"><Badge :variant="status(item).variante" size="sm">{{ status(item).rotulo }}</Badge></td>
        <td v-if="temAcoes" class="table-actions-cell px-3 py-2">
          <div class="flex items-center justify-end gap-1">
            <ButtonIcon
              v-if="pode.editar"
              :icon="PencilSquareIcon"
              variant="primary"
              size="sm"
              title="Editar estação"
              aria-label="Editar estação"
              @click="$emit('editar', item)"
            />
            <ButtonIcon
              v-if="pode.remover"
              :icon="TrashIcon"
              variant="danger"
              size="sm"
              title="Remover estação"
              aria-label="Remover estação"
              @click="$emit('remover', item)"
            />
          </div>
        </td>
      </tr>
    </template>

    <template #cartao="{ item }">
      <div class="flex min-w-0 items-start justify-between gap-2">
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100" :title="item.nome">{{ item.nome }}</p>
          <p class="truncate text-xs text-slate-500 dark:text-slate-400" :title="item.ponto_rede">Ponto de rede: {{ item.ponto_rede || '—' }}</p>
        </div>
        <Badge class="shrink-0" :variant="status(item).variante" size="sm">{{ status(item).rotulo }}</Badge>
      </div>
      <p class="mt-2 truncate text-xs text-slate-600 dark:text-slate-300" :title="item.usuario?.name">Usuário: {{ item.usuario?.name || '—' }}</p>
      <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">Equipamentos: {{ item.equipamentos_count }}</p>
      <!-- Rotulo visivel e alvo de 40px: no toque o title nao aparece. -->
      <div v-if="temAcoes" class="mt-3 grid grid-cols-2 gap-2">
        <Button v-if="pode.editar" variant="primary" size="md" :icon="PencilSquareIcon" class="min-h-10 w-full" @click="$emit('editar', item)">
          Editar
        </Button>
        <Button v-if="pode.remover" variant="danger" size="md" :icon="TrashIcon" class="min-h-10 w-full" @click="$emit('remover', item)">
          Remover
        </Button>
      </div>
    </template>
  </ListaResponsiva>
</template>

<script setup>
import { computed } from 'vue';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ButtonIcon from '@/Components/Atoms/Button/ButtonIcon.vue';
import ListaResponsiva from '@/Components/Molecules/Inventario/ListaResponsiva.vue';

const props = defineProps({
  estacoes: { type: Array, required: true },
  pode: { type: Object, required: true },
});
defineEmits(['editar', 'remover']);

const temAcoes = computed(() => props.pode.editar || props.pode.remover);

// Ocupacao e derivada do user_id, a mesma regra do filtro do EstacaoController.
const OCUPADA = { rotulo: 'Ocupada', variante: 'info' };
const LIVRE = { rotulo: 'Livre', variante: 'success' };
const status = (item) => (item.user_id ? OCUPADA : LIVRE);

const COLUNAS = ['Estação', 'Usuário', 'Ponto de rede', 'Equipamentos', 'Status'];
const VAZIO = { titulo: 'Nenhuma estação encontrada', ajuda: 'Ajuste os filtros ou cadastre uma nova estação.' };
</script>
