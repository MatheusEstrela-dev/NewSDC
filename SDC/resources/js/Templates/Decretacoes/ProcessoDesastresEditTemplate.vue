<template>
  <div class="processo-desastres-edit-template">
    <!-- Header -->
    <div class="page-header mb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
          <ExclamationTriangleIcon class="w-6 h-6 text-amber-600 dark:text-amber-400" />
        </div>
        <div>
          <Heading level="h1" size="2xl">Editar Dados do Desastre</Heading>
          <Text size="sm" color="muted" class="mt-1">
            Atualize os dados de danos e prejuizos por municipio
          </Text>
        </div>
      </div>
    </div>

    <!-- Resumo do Processo -->
    <div class="bg-white dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700/50 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <Text size="xs" color="muted" class="uppercase font-semibold">Protocolo</Text>
          <Text size="sm" class="font-medium text-slate-800 dark:text-slate-200">
            {{ processo?.n_protocolo_fide || '---' }}
          </Text>
        </div>
        <div>
          <Text size="xs" color="muted" class="uppercase font-semibold">Tipo de Desastre</Text>
          <Text size="sm" class="font-medium text-slate-800 dark:text-slate-200">
            {{ processo?.tipo_desastre_nome || '---' }}
          </Text>
        </div>
        <div>
          <Text size="xs" color="muted" class="uppercase font-semibold">COBRADE</Text>
          <Text size="sm" class="font-medium text-slate-800 dark:text-slate-200">
            {{ processo?.tipo_desastre_cobrade || '---' }}
          </Text>
        </div>
        <div>
          <Text size="xs" color="muted" class="uppercase font-semibold">Status</Text>
          <StatusBadge :status="processo?.status" />
        </div>
      </div>
    </div>

    <!-- Formulario -->
    <form @submit.prevent="handleSubmit">
      <!-- Municipios -->
      <div class="space-y-4 mb-6">
        <MunicipioDesastreSection
          v-for="(municipio, mIndex) in localMunicipios"
          :key="municipio.id"
          :municipio="municipio"
          :m-index="mIndex"
          @update:municipio="localMunicipios[mIndex] = $event"
        />
      </div>

      <!-- Empty State -->
      <div
        v-if="!localMunicipios || localMunicipios.length === 0"
        class="bg-white dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700/50 p-12 text-center"
      >
        <MapPinIcon class="w-16 h-16 text-slate-400 mx-auto mb-4" />
        <Heading level="h3" color="muted">Nenhum municipio vinculado</Heading>
        <Text size="sm" color="muted" class="mt-2">
          Adicione municipios ao processo antes de editar os dados de desastres
        </Text>
      </div>

      <!-- Acoes -->
      <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700/50">
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
          @click="$emit('cancel')"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 rounded-lg transition-colors flex items-center gap-2"
        >
          <ArrowPathIcon v-if="form.processing" class="w-4 h-4 animate-spin" />
          Salvar Alteracoes
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { ExclamationTriangleIcon, MapPinIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import StatusBadge from '@/Components/Molecules/Decretacoes/StatusBadge.vue';
import MunicipioDesastreSection from '@/Components/Organisms/Decretacoes/MunicipioDesastreSection.vue';
import { formatOnLoad } from '@/composables/ui/useDesastreMask';

const props = defineProps({
  processo: {
    type: Object,
    required: true,
  },
  municipios: {
    type: Array,
    default: () => [],
  },
  form: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['submit', 'cancel']);

const localMunicipios = ref(JSON.parse(JSON.stringify(props.municipios)));

watch(() => props.municipios, (val) => {
  localMunicipios.value = JSON.parse(JSON.stringify(val));
}, { deep: true });

onMounted(() => {
  formatOnLoad(localMunicipios.value);
});

function handleSubmit() {
  props.form.municipios = localMunicipios.value;
  emit('submit');
}
</script>

<style scoped>
.processo-desastres-edit-template {
  @apply w-full;
}
</style>
