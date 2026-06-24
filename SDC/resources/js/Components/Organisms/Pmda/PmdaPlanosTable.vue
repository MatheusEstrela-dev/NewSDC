<template>
  <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-3 text-left font-semibold text-gray-600">Protocolo</th>
          <th class="px-4 py-3 text-left font-semibold text-gray-600">Município</th>
          <th class="px-4 py-3 text-left font-semibold text-gray-600">Situação</th>
          <th class="px-4 py-3 text-left font-semibold text-gray-600">Criação</th>
          <th class="px-4 py-3 text-right font-semibold text-gray-600">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <tr v-for="plano in items" :key="plano.id" class="hover:bg-gray-50">
          <td class="px-4 py-3 font-mono text-gray-800">{{ plano.protocolo ?? '—' }}</td>
          <td class="px-4 py-3 text-gray-700">{{ plano.municipio ?? '—' }}</td>
          <td class="px-4 py-3">
            <PmdaStatusBadge :label="plano.status_label" :color-class="plano.status_color" />
          </td>
          <td class="px-4 py-3 text-gray-500">{{ formatDate(plano.data) }}</td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-2">
              <button
                type="button"
                class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100"
                @click="$emit('edit', plano.id)"
              >
                Editar
              </button>
              <button
                v-if="plano.pode_copiar"
                type="button"
                class="rounded-md border border-indigo-300 px-2.5 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-50"
                @click="$emit('copiar', plano.id)"
              >
                Copiar
              </button>
            </div>
          </td>
        </tr>
        <tr v-if="!items.length">
          <td colspan="5" class="px-4 py-8 text-center text-gray-400">Nenhum PMDA encontrado.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import PmdaStatusBadge from '@/Components/Atoms/Pmda/PmdaStatusBadge.vue';

const props = defineProps({
  planos: { type: Object, required: true },
});

defineEmits(['edit', 'copiar']);

// Aceita tanto um recurso paginado ({ data: [...] }) quanto um array simples.
const items = computed(() => props.planos?.data ?? props.planos ?? []);

function formatDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString('pt-BR');
}
</script>
