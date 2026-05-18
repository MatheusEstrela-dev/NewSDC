<template>
  <div class="processo-create-template">
    <!-- Header -->
    <div class="page-header mb-6">
      <Heading level="h1" size="2xl">
        {{ headerTitle }}
      </Heading>
      <Text size="sm" color="muted" class="mt-1">
        {{ headerSubtitle }}
      </Text>
    </div>

    <!-- Banner de modo somente-leitura -->
    <div
      v-if="viewOnly"
      class="bg-sky-50 dark:bg-sky-900/20 rounded-lg border border-sky-200 dark:border-sky-800/40 p-3 mb-6 flex items-center gap-3"
    >
      <EyeIcon class="w-5 h-5 text-sky-600 dark:text-sky-400 shrink-0" />
      <Text size="sm" class="text-sky-700 dark:text-sky-200 font-medium">
        Modo de visualizacao - somente leitura. Edicao desabilitada.
      </Text>
    </div>

    <!-- Tabs (Aba 2 disabled ate processo ser salvo) -->
    <DecretacaoTabs
      :tabs="tabs"
      :active-tab="activeTab"
      @update:active-tab="setActiveTab"
    >
      <template #default="{ activeTab: current }">
        <!-- Aba 1: Identificacao do Processo -->
        <ProcessoForm
          v-if="current === 'identificacao'"
          :form="form"
          :tipos-desastre="tiposDesastre"
          :cobrades="cobrades"
          :municipios="municipios"
          :redecs="redecs"
          :status-options="statusOptions"
          :analistas="analistas"
          :submit-label="processo?.id ? 'Atualizar Identificacao' : 'Criar Processo'"
          :is-editing="!!processo?.id"
          :view-only="viewOnly"
          @submit="handleSubmitIdentificacao"
          @cancel="$emit('cancel')"
        />

        <!-- Aba 2: Dados de Desastre (so renderiza apos processo criado) -->
        <div v-else-if="current === 'desastres' && processo?.id">
          <!-- Aviso de etapa -->
          <div class="bg-amber-50 dark:bg-amber-900/10 rounded-lg border border-amber-200 dark:border-amber-800/30 p-4 mb-6 flex items-start gap-3">
            <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg shrink-0">
              <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <Heading level="h3" size="sm">Cadastrar Dados do Desastre</Heading>
              <Text size="xs" color="muted" class="mt-0.5">
                Informe os danos e prejuizos por municipio. Voce pode finalizar o cadastro depois sem preencher tudo agora.
              </Text>
            </div>
          </div>

          <!-- Resumo do Processo recem-criado -->
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

          <form @submit.prevent="handleSubmitDesastres">
            <div class="space-y-4 mb-6">
              <MunicipioDesastreSection
                v-for="(mun, mIndex) in localMunicipios"
                :key="mun.id"
                :municipio="mun"
                :m-index="mIndex"
                :view-only="viewOnly"
                @update:municipio="localMunicipios[mIndex] = $event"
              />
            </div>

            <div
              v-if="!localMunicipios || localMunicipios.length === 0"
              class="bg-white dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700/50 p-12 text-center"
            >
              <MapPinIcon class="w-16 h-16 text-slate-400 mx-auto mb-4" />
              <Heading level="h3" color="muted">Nenhum municipio vinculado</Heading>
              <Text size="sm" color="muted" class="mt-2">
                Adicione municipios na Aba 1 antes de preencher os dados de desastres
              </Text>
            </div>

            <div
              v-if="!viewOnly"
              class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700/50"
            >
              <Button type="button" variant="secondary" @click="$emit('finish')">
                Concluir mais tarde
              </Button>
              <Button
                type="submit"
                variant="primary"
                :loading="saving"
                :disabled="saving"
              >
                Salvar e Concluir Cadastro
              </Button>
            </div>
          </form>
        </div>
      </template>
    </DecretacaoTabs>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { ExclamationTriangleIcon, MapPinIcon, EyeIcon } from '@heroicons/vue/24/outline';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import StatusBadge from '@/Components/Molecules/Decretacoes/StatusBadge.vue';
import ProcessoForm from '@/Components/Organisms/Decretacoes/ProcessoForm.vue';
import MunicipioDesastreSection from '@/Components/Organisms/Decretacoes/MunicipioDesastreSection.vue';
import DecretacaoTabs from '@/Components/Organisms/Decretacoes/DecretacaoTabs.vue';
import { useDecretacaoTabs } from '@/composables/decretacoes/useDecretacaoTabs';
import { formatOnLoad } from '@/composables/ui/useDesastreMask';

const props = defineProps({
  form:                { type: Object, required: true },
  tiposDesastre:       { type: Array,  default: () => [] },
  cobrades:            { type: Array,  default: () => [] },
  municipios:          { type: Array,  default: () => [] },
  redecs:              { type: Array,  default: () => [] },
  statusOptions:       { type: Array,  default: () => [] },
  analistas:           { type: Array,  default: () => [] },
  processo:            { type: Object, default: null },
  municipiosDesastres: { type: Array,  default: () => [] },
  viewOnly:            { type: Boolean, default: false },
});

const headerTitle = computed(() => {
  if (props.viewOnly) return 'Visualizar Processo';
  return isEditing.value ? 'Editar Processo' : 'Novo Reconhecimento de Desastre';
});

const headerSubtitle = computed(() => {
  if (props.viewOnly) return 'Detalhes do processo em modo somente leitura';
  return props.processo?.id
    ? 'Etapa 2 de 2 - Preencha os dados do desastre por municipio'
    : 'Etapa 1 de 2 - Cadastre um novo processo de reconhecimento';
});

const emit = defineEmits(['submit', 'submit-desastres', 'cancel', 'finish']);

// Determina se estamos no modo edicao (processo ja existe no backend)
const isEditing = computed(() => !!props.processo?.id);

// Wizard: Aba 2 fica disabled ate o processo ser criado.
// - Em viewOnly: comeca sempre na Aba 1 (identificacao).
// - Apos store no fluxo de criacao (processo.id ja existe): pula direto para a Aba 2.
const { activeTab, setActiveTab, tabs } = useDecretacaoTabs({
  initialTab: !props.viewOnly && props.processo?.id ? 'desastres' : 'identificacao',
  processo: computed(() => props.processo),
});

// Estado local da Aba 2 - clone profundo dos municipios vindos do backend.
const localMunicipios = ref(JSON.parse(JSON.stringify(props.municipiosDesastres || [])));
const saving = ref(false);

watch(() => props.municipiosDesastres, (val) => {
  localMunicipios.value = JSON.parse(JSON.stringify(val || []));
}, { deep: true });

onMounted(() => {
  if (localMunicipios.value.length > 0) {
    formatOnLoad(localMunicipios.value);
  }
});

function handleSubmitIdentificacao() {
  emit('submit');
}

function handleSubmitDesastres() {
  saving.value = true;
  emit('submit-desastres', localMunicipios.value);
  // Reset do flag fica a cargo do consumidor (idealmente via prop), mas aqui
  // damos uma janela curta para evitar duplo-submit.
  setTimeout(() => { saving.value = false; }, 1500);
}
</script>

<style scoped>
.processo-create-template {
  @apply w-full;
}
</style>
