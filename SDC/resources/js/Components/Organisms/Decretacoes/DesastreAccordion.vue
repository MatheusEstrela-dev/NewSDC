<template>
  <div class="border border-slate-200 dark:border-slate-700/50 rounded-lg overflow-hidden">
    <!-- Header colapsavel -->
    <button
      type="button"
      class="w-full px-4 py-3 flex items-center justify-between bg-slate-50 dark:bg-slate-800/70 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors text-left"
      @click="isExpanded = !isExpanded"
    >
      <div class="flex items-center gap-3 flex-1 min-w-0">
        <span class="font-semibold text-base text-slate-700 dark:text-slate-200 truncate">
          {{ desastre.titulo }}
        </span>
        <DesastreTotalBadge :desastre="localDesastre" />
      </div>
      <ChevronDownIcon
        :class="['w-4 h-4 text-slate-400 transition-transform shrink-0 ml-2', { 'rotate-180': isExpanded }]"
      />
    </button>

    <!-- Body -->
    <div v-show="isExpanded" class="p-4 border-t border-slate-200 dark:border-slate-700/50 space-y-4">
      <!-- Informacao -->
      <p v-if="desastre.informacao" class="text-sm text-slate-500 dark:text-slate-400 italic">
        {{ desastre.informacao }}
      </p>

      <!-- Tabela de itens -->
      <div v-if="localDesastre.items && localDesastre.items.length > 0" class="overflow-x-auto">
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
              v-for="(item, iIndex) in localDesastre.items"
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
                    @update:valor="updateCampo(iIndex, fIndex, $event)"
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
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import DesastreTotalBadge from '@/Components/Molecules/Decretacoes/DesastreTotalBadge.vue';
import DesastreCampoField from '@/Components/Molecules/Decretacoes/DesastreCampoField.vue';

const props = defineProps({
  desastre: {
    type: Object,
    required: true,
  },
  municipioId: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['update:desastre']);

const isExpanded = ref(false);
const localDesastre = ref(JSON.parse(JSON.stringify(props.desastre)));

watch(() => props.desastre, (val) => {
  localDesastre.value = JSON.parse(JSON.stringify(val));
}, { deep: true });

function updateCampo(iIndex, fIndex, valor) {
  localDesastre.value.items[iIndex].campos[fIndex].valor = valor;
  emitUpdate();
}

function emitUpdate() {
  const payload = JSON.parse(JSON.stringify(localDesastre.value));

  // A descricao de areas/populacao afetada saiu do formulario: nao e enviada.
  delete payload.descricao;

  emit('update:desastre', payload);
}
</script>
