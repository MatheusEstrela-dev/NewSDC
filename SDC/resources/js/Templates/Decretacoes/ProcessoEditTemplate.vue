<template>
  <div class="processo-edit-template">
    <!-- Header -->
    <div class="page-header mb-6">
      <Heading level="h1" size="2xl">Editar Decretacao</Heading>
      <Text size="sm" color="muted" class="mt-1">
        {{ processo?.n_protocolo_fide || 'Atualize os dados do processo e os danos por municipio' }}
      </Text>
    </div>

    <!-- Tabs -->
    <DecretacaoTabs
      :tabs="tabs"
      :active-tab="activeTab"
      @update:active-tab="setActiveTab"
    >
      <template #default="{ activeTab: current }">
        <!-- Aba 1: Identificacao do Processo -->
        <div v-if="current === 'identificacao'">
          <ProcessoForm
            :form="form"
            :tipos-desastre="tiposDesastre"
            :cobrades="cobrades"
            :municipios="municipiosOptions"
            :redecs="redecs"
            :status-options="statusOptions"
            :analistas="analistas"
            submit-label="Atualizar Processo"
            :is-editing="true"
            @submit="handleProcessoSubmit"
            @cancel="handleCancel"
          />
        </div>

        <!-- Aba 2: Dados de Desastre -->
        <div v-else-if="current === 'desastres'">
          <!-- Resumo do Processo (paridade com /desastres/edit antigo) -->
          <div class="bg-amber-50 dark:bg-amber-900/10 rounded-lg border border-amber-200 dark:border-amber-800/30 p-4 mb-6 flex items-start gap-3">
            <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg shrink-0">
              <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <Heading level="h3" size="sm">Editar Dados do Desastre</Heading>
              <Text size="xs" color="muted" class="mt-0.5">
                Atualize os dados de danos e prejuizos por municipio
              </Text>
            </div>
          </div>

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

          <form @submit.prevent="handleDesastresSubmit">
            <div class="space-y-4 mb-6">
              <MunicipioDesastreSection
                v-for="(mun, mIndex) in localMunicipios"
                :key="mun.id"
                :municipio="mun"
                :m-index="mIndex"
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
                Adicione municipios ao processo antes de editar os dados de desastres
              </Text>
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-700/50">
              <Button type="button" variant="secondary" @click="handleCancel">Cancelar</Button>
              <Button
                type="submit"
                variant="primary"
                :loading="desastresForm.processing"
                :disabled="desastresForm.processing"
              >
                Salvar Dados de Desastre
              </Button>
            </div>
          </form>
        </div>
      </template>
    </DecretacaoTabs>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { MapPinIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import StatusBadge from '@/Components/Molecules/Decretacoes/StatusBadge.vue';
import ProcessoForm from '@/Components/Organisms/Decretacoes/ProcessoForm.vue';
import MunicipioDesastreSection from '@/Components/Organisms/Decretacoes/MunicipioDesastreSection.vue';
import DecretacaoTabs from '@/Components/Organisms/Decretacoes/DecretacaoTabs.vue';
import { useDecretacaoTabs } from '@/Composables/decretacoes/useDecretacaoTabs';
import { formatOnLoad } from '@/Composables/ui/useDesastreMask';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  processo: { type: Object, required: true },
  municipios: { type: Array, default: () => [] },
  tiposDesastre: { type: Array, default: () => [] },
  cobrades: { type: Array, default: () => [] },
  redecs: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] },
  analistas: { type: Array, default: () => [] },
  activeTab: { type: String, default: 'identificacao' },
  tabs: { type: Array, required: true },
});

const { show: toast } = useToast();
const { activeTab, setActiveTab } = useDecretacaoTabs({
  initialTab: props.activeTab,
  processo: props.processo,
});

// Aba 1 - form de processo (mapeado de ProcessoResource::toArray)
// Os aliases existentes em ProcessoResource (ex: cobrade_id => tipo_desastre_id,
// origem => processo, n_portaria_federal => portaria_reconhecimento_fed)
// permitem usar o nome canonico do form sem fallbacks defensivos.
const form = useForm({
  tipo_desastre_id: props.processo.tipo_desastre_id ?? '',
  cobrade_id: props.processo.cobrade_id ?? '',
  origem: props.processo.origem ?? props.processo.processo ?? '',
  municipio_id: props.processo.municipio_id ?? '',
  redec_id: props.processo.redec_id ?? '',
  situacao_anormalidade: props.processo.situacao_anormalidade ?? props.processo.reconhecimento ?? '',
  data_entrada: props.processo.data_entrada ?? '',
  data_ocorrencia: props.processo.data_ocorrencia ?? props.processo.data_ocorrencia_desastre ?? '',
  data_vencimento_decreto: props.processo.data_vencimento ?? '',
  status: props.processo.status ?? '',
  analista_id: props.processo.analista ?? '',
  n_protocolo_fide: props.processo.n_protocolo_fide ?? props.processo.protocolo_fide ?? '',
  n_decreto_municipal: props.processo.decreto_municipal ?? '',
  data_decreto_municipal: props.processo.data_decreto_municipal ?? '',
  data_publicacao_decreto_municipal: props.processo.data_publicacao_mg ?? '',
  prazo_vigencia_decreto: props.processo.prazo_vigencia ?? '',
  n_decreto_estadual: props.processo.n_decreto_estadual ?? '',
  data_decreto_estadual: props.processo.data_decreto_estadual ?? '',
  n_edicao_domg: props.processo.n_edicao_domg ?? '',
  data_publicacao_domg: props.processo.data_publicacao_domg ?? '',
  n_portaria_federal: props.processo.n_portaria_federal ?? '',
  data_portaria_federal: props.processo.data_portaria_federal ?? '',
  n_edicao_dou: props.processo.n_edicao_dou ?? '',
  data_publicacao_dou: props.processo.data_publicacao_diario ?? '',
  n_processo_sei: props.processo.processo_inserido_sei ?? '',
  observacoes: props.processo.observacoes ?? '',
});

// Aba 2 - form de desastres
const desastresForm = useForm({ municipios: props.municipios || [] });
const localMunicipios = ref(JSON.parse(JSON.stringify(props.municipios || [])));

watch(() => props.municipios, (val) => {
  localMunicipios.value = JSON.parse(JSON.stringify(val || []));
}, { deep: true });

onMounted(() => {
  formatOnLoad(localMunicipios.value);
});

// Lista de municipios do select da Aba 1 - virtualmente ignorado em edit (multi nao suportado pelo form atual),
// mas passado como prop para nao quebrar o template.
const municipiosOptions = ref([]);

function handleProcessoSubmit() {
  form.put(route('decretacoes.update', props.processo.id), {
    preserveScroll: true,
    onSuccess: () => toast('Processo atualizado com sucesso.'),
  });
}

function handleDesastresSubmit() {
  desastresForm.municipios = localMunicipios.value;
  desastresForm.post(route('decretacoes.desastres.store', props.processo.id), {
    preserveScroll: true,
    onSuccess: () => toast('Dados de desastre salvos com sucesso.'),
  });
}

function handleCancel() {
  router.visit(route('decretacoes.index'));
}
</script>

<style scoped>
.processo-edit-template {
  @apply w-full;
}
</style>
