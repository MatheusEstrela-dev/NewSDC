<template>
  <div class="bg-white dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700/50 overflow-hidden">
    <!-- Header do Municipio -->
    <button
      type="button"
      class="w-full px-4 py-3 flex items-center justify-between bg-slate-50 dark:bg-slate-800/70 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors text-left"
      @click="isExpanded = !isExpanded"
    >
      <div class="flex items-center gap-3">
        <MapPinIcon class="w-5 h-5 text-primary-500 shrink-0" />
        <span class="font-semibold text-slate-800 dark:text-slate-200">
          {{ municipio.nome || municipio.p_nome || `Municipio ${municipio.id}` }}
        </span>
        <span v-if="localMunicipio.n_protocolo_fide" class="text-xs text-slate-400 hidden sm:inline">
          ({{ localMunicipio.n_protocolo_fide }})
        </span>
      </div>
      <ChevronDownIcon
        :class="['w-5 h-5 text-slate-400 transition-transform shrink-0', { 'rotate-180': isExpanded }]"
      />
    </button>

    <!-- Conteudo do Municipio -->
    <div
      v-show="isExpanded"
      :class="['p-4 border-t border-slate-200 dark:border-slate-700/50 space-y-6', { 'is-view-only': viewOnly }]"
    >
      <!-- Protocolo FIDE -->
      <div class="max-w-sm">
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
          N. Protocolo FIDE
        </label>
        <ProtocoloFideInput
          :model-value="localMunicipio.n_protocolo_fide"
          @update:model-value="localMunicipio.n_protocolo_fide = $event; emitUpdate()"
        />
      </div>

      <!-- Categorias -->
      <div
        v-for="categoria in localMunicipio.categorias"
        :key="categoria.id"
        class="space-y-3"
      >
        <!-- Header da Categoria (nao colapsavel) -->
        <div class="flex items-center gap-2 pb-2 border-b border-slate-200 dark:border-slate-700/50">
          <div class="w-1 h-5 bg-primary-500 rounded-full"></div>
          <h4 class="text-sm font-semibold text-primary-600 dark:text-primary-400">
            {{ categoria.titulo }}
          </h4>
        </div>

        <!-- Desastres -->
        <div class="space-y-2 pl-3">
          <DesastreAccordion
            v-for="(desastre, dIndex) in categoria.desastres"
            :key="desastre.id"
            :desastre="desastre"
            :municipio-id="municipio.id"
            @update:desastre="updateDesastre(categoria.id, dIndex, $event)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { MapPinIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import ProtocoloFideInput from '@/Components/Atoms/Input/ProtocoloFideInput.vue';
import DesastreAccordion from '@/Components/Organisms/Decretacoes/DesastreAccordion.vue';

const props = defineProps({
  municipio: {
    type: Object,
    required: true,
  },
  mIndex: {
    type: Number,
    required: true,
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:municipio']);

const isExpanded = ref(props.mIndex === 0);
const localMunicipio = ref(JSON.parse(JSON.stringify(props.municipio)));

watch(() => props.municipio, (val) => {
  localMunicipio.value = JSON.parse(JSON.stringify(val));
}, { deep: true });

function updateDesastre(categoriaId, dIndex, updatedDesastre) {
  const cat = localMunicipio.value.categorias.find((c) => c.id === categoriaId);
  if (cat) {
    cat.desastres[dIndex] = updatedDesastre;
    emitUpdate();
  }
}

function emitUpdate() {
  emit('update:municipio', JSON.parse(JSON.stringify(localMunicipio.value)));
}
</script>

<style scoped>
.is-view-only :deep(input),
.is-view-only :deep(select),
.is-view-only :deep(textarea) {
  pointer-events: none;
  opacity: 0.75;
  cursor: not-allowed;
}
</style>
