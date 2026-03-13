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

    <!-- Formulario de Desastres -->
    <form @submit.prevent="handleSubmit">
      <!-- Municipios (Accordion) -->
      <div class="space-y-4 mb-6">
        <div
          v-for="(municipio, mIndex) in localMunicipios"
          :key="municipio.id"
          class="bg-white dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700/50 overflow-hidden"
        >
          <!-- Header do Municipio -->
          <button
            type="button"
            @click="toggleMunicipio(mIndex)"
            class="w-full px-4 py-3 flex items-center justify-between bg-slate-50 dark:bg-slate-800/70 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <MapPinIcon class="w-5 h-5 text-primary-500" />
              <span class="font-semibold text-slate-800 dark:text-slate-200">
                {{ municipio.nome || `Municipio ${municipio.id}` }}
              </span>
              <span v-if="municipio.n_protocolo_fide" class="text-xs text-slate-500">
                ({{ municipio.n_protocolo_fide }})
              </span>
            </div>
            <ChevronDownIcon
              :class="['w-5 h-5 text-slate-400 transition-transform', { 'rotate-180': expandedMunicipios.includes(mIndex) }]"
            />
          </button>

          <!-- Conteudo do Municipio -->
          <div v-show="expandedMunicipios.includes(mIndex)" class="p-4 border-t border-slate-200 dark:border-slate-700/50">
            <!-- Categorias -->
            <div v-if="municipio.categorias && municipio.categorias.length > 0" class="space-y-4">
              <div
                v-for="(categoria, cIndex) in municipio.categorias"
                :key="categoria.id"
                class="border border-slate-200 dark:border-slate-700/50 rounded-lg"
              >
                <!-- Header da Categoria -->
                <div class="px-4 py-2 bg-slate-100 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700/50">
                  <span class="font-medium text-sm text-slate-700 dark:text-slate-300">
                    {{ categoria.nome || `Categoria ${categoria.id}` }}
                  </span>
                </div>

                <!-- Desastres/Items -->
                <div class="p-4">
                  <div
                    v-for="(desastre, dIndex) in categoria.desastres"
                    :key="desastre.id"
                    class="mb-4 last:mb-0"
                  >
                    <Text size="sm" class="font-medium text-slate-600 dark:text-slate-400 mb-2">
                      {{ desastre.descricao || `Item ${desastre.id}` }}
                    </Text>

                    <!-- Campos do Desastre -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                      <div
                        v-for="(item, iIndex) in desastre.items"
                        :key="item.id"
                      >
                        <div
                          v-for="(campo, fIndex) in item.campos"
                          :key="campo.id"
                          class="mb-2"
                        >
                          <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                            {{ campo.titulo }}
                          </label>
                          <input
                            v-if="campo.tipo === 'number'"
                            type="number"
                            v-model="localMunicipios[mIndex].categorias[cIndex].desastres[dIndex].items[iIndex].campos[fIndex].valor"
                            class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            :placeholder="campo.titulo"
                          />
                          <input
                            v-else-if="campo.tipo === 'currency'"
                            type="text"
                            v-model="localMunicipios[mIndex].categorias[cIndex].desastres[dIndex].items[iIndex].campos[fIndex].valor"
                            class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="R$ 0,00"
                          />
                          <input
                            v-else
                            type="text"
                            v-model="localMunicipios[mIndex].categorias[cIndex].desastres[dIndex].items[iIndex].campos[fIndex].valor"
                            class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            :placeholder="campo.titulo"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-8">
              <ExclamationTriangleIcon class="w-12 h-12 text-slate-400 mx-auto mb-3" />
              <Text size="sm" color="muted">Nenhuma categoria de desastre disponivel</Text>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State Geral -->
      <div v-if="!localMunicipios || localMunicipios.length === 0" class="bg-white dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700/50 p-12 text-center">
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
          @click="$emit('cancel')"
          class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 rounded-lg transition-colors flex items-center gap-2"
        >
          <span v-if="form.processing" class="animate-spin">
            <ArrowPathIcon class="w-4 h-4" />
          </span>
          Salvar Alteracoes
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import StatusBadge from '@/Components/Molecules/Decretacoes/StatusBadge.vue';
import {
  ExclamationTriangleIcon,
  MapPinIcon,
  ChevronDownIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline';

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

const localMunicipios = ref([...props.municipios]);
const expandedMunicipios = ref([0]);

watch(() => props.municipios, (newVal) => {
  localMunicipios.value = [...newVal];
}, { deep: true });

function toggleMunicipio(index) {
  const idx = expandedMunicipios.value.indexOf(index);
  if (idx > -1) {
    expandedMunicipios.value.splice(idx, 1);
  } else {
    expandedMunicipios.value.push(index);
  }
}

function handleSubmit() {
  props.form.municipios = localMunicipios.value;
  emit('submit', props.form);
}
</script>

<style scoped>
.processo-desastres-edit-template {
  @apply w-full;
}
</style>
