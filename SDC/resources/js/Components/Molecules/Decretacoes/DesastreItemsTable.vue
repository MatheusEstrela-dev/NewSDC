<template>
  <div v-if="items && items.length > 0" class="overflow-x-auto">
    <table class="w-full text-sm border-collapse">
      <thead>
        <tr class="bg-slate-100 dark:bg-slate-800/50">
          <th class="px-3 py-2 text-left text-sm font-semibold text-slate-500 dark:text-slate-400 w-1/3 border-b border-slate-200 dark:border-slate-700/50">
            Item
          </th>
          <th class="px-3 py-2 text-left text-sm font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700/50">
            Campos
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(item, iIndex) in items"
          :key="item.id"
          class="border-b border-slate-100 dark:border-slate-700/30 last:border-0"
        >
          <td class="px-3 py-3 align-top">
            <p class="font-medium text-slate-700 dark:text-slate-300 text-sm">{{ item.titulo }}</p>
            <p v-if="item.observacao" class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
              {{ item.observacao }}
            </p>
          </td>
          <td class="px-3 py-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <DesastreCampoField
                v-for="(campo, fIndex) in item.campos"
                :key="campo.id"
                :campo="campo"
                :item-id="item.id"
                :municipio-id="municipioId"
                @update:valor="emit('update:campo', { iIndex, fIndex, valor: $event })"
              />
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div v-else class="text-center py-4">
    <p class="text-xs text-slate-400 dark:text-slate-500 italic">Nenhum dado registrado para este item.</p>
  </div>
</template>

<script setup>
/**
 * Tabela Item | Campos de um bloco de danos.
 *
 * Extraida do DesastreAccordion para servir tanto ao bloco principal quanto as
 * subsecoes (ex.: Danos Ambientais dentro de Danos Materiais), sem duplicar a
 * marcacao. Puramente apresentacional: quem recebe `update:campo` e responsavel
 * por gravar o valor na arvore.
 */
import DesastreCampoField from '@/Components/Molecules/Decretacoes/DesastreCampoField.vue';

defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  municipioId: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['update:campo']);
</script>
