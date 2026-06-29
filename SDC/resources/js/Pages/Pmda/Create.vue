<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import RatTabs from '@/Components/Rat/RatTabs.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import PmdaInicioSection from '@/Components/Organisms/Pmda/PmdaInicioSection.vue';
import InstrucoesPmdaModal from '@/Components/Organisms/InstrucoesPmdaModal.vue';
import { usePmdaWizard } from '@/Composables/pmda/usePmdaWizard.js';

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps({
  municipio: { type: Object, required: true },
});

// Modal de instrucoes: abre toda vez que a pagina de criacao e aberta.
const showInstrucoes = ref(true);

// Create: so a etapa 1 liberada; ao avancar, cria o plano e segue para o Edit.
const { activeTab, tabs, goTo } = usePmdaWizard({ allUnlocked: false });

const form = useForm({
  municipio_id: props.municipio.id,
  motivo: '',
});

function avancar() {
  form.post(route('pmda.planos.store')); // controller redireciona para o Edit
}
</script>

<template>
  <Head title="Novo PMDA" />

  <InstrucoesPmdaModal :show="showInstrucoes" :municipio="municipio" @close="showInstrucoes = false" />

  <div class="pb-6">
    <PageHeader
      :title="`Novo PMDA — ${municipio.nome} / ${municipio.uf}`"
      :icon="DocumentTextIcon"
      variant="gradient"
    />

    <RatTabs :tabs="tabs" :active-tab="activeTab" @tab-change="goTo">
      <PmdaInicioSection
        v-if="activeTab === 1"
        :form="form"
        next-label="Avançar"
        :saving="form.processing"
        @next="avancar"
      />
    </RatTabs>
  </div>
</template>
