<template>
  <div class="orgao-create">
    <div class="header-section">
      <Button variant="ghost" @click="handleBack">
        &larr; Voltar
      </Button>

      <Heading level="1">Criar Novo Orgao</Heading>
      <Text variant="muted">Preencha os dados para cadastrar um novo orgao de defesa civil</Text>
    </div>

    <CardBase>
      <OrgaoForm
        :form-data="form"
        :errors="errors"
        :municipios="municipios"
        :orgaos-superior="orgaosSuperior"
        :loading="loading"
        @submit="handleSubmit"
        @cancel="handleBack"
      />
    </CardBase>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import OrgaoForm from '@/Components/Organisms/Compdec/OrgaoForm.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  municipios: {
    type: Array,
    default: () => [],
  },
  orgaosSuperior: {
    type: Array,
    default: () => [],
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const loading = ref(false);

const form = reactive({
  // Identificacao
  tipo: 'compdec',
  codigo: '',
  nome: '',
  status: 'ativo',
  municipio_id: '',
  orgao_superior_id: '',

  // Atos Legais
  lei_criacao_numero: '',
  lei_criacao_data: '',
  decreto_numero: '',
  decreto_data: '',
  portaria_numero: '',
  portaria_data: '',

  // Quantitativos
  qtd_efetivo: 0,
  qtd_nupdec: 0,

  // Contato
  email: '',
  email_secundario: '',
  email_terciario: '',
  telefone: '',
  telefone_secundario: '',
  fax: '',
  endereco: '',

  // Capacidades estruturais (booleans)
  tem_sede_propria: false,
  tem_viatura: false,
  tem_mapeamento_risco: false,
  tem_simulado: false,
  tem_cartao_pdc: false,

  // Capacidades soft (jsonb metadata->capacidades)
  capacidades: {
    tem_computador: false,
    tem_curso_gestao: false,
    data_curso_gestao: '',
    tem_curso_sco: false,
    data_curso_sco: '',
    tem_workshop_pdc: false,
    data_workshop_pdc: '',
    experiencia_dc: '',
    tipo_experiencia_dc: '',
    capacitacao_nupdec: '',
    nupdec_org_representado: '',
    obs_capacidades: '',
  },

  // Responsavel
  responsavel_nome: '',
  responsavel_cpf: '',
  responsavel_email: '',
  responsavel_telefone: '',
});

function handleSubmit(formData) {
  loading.value = true;
  router.post(route('compdec.store'), formData, {
    onFinish: () => {
      loading.value = false;
    },
  });
}

function handleBack() {
  router.visit(route('compdec.index'));
}
</script>

<style scoped>
.orgao-create {
  max-width: 1200px;
  margin: 0 auto;
}

.header-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 2rem;
}
</style>
