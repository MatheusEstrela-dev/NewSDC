<template>
  <div class="orgao-edit">
    <PageHeader
      title="Editar Orgao"
      :description="`${orgao.codigo} - ${orgao.nome}`"
      :icon="BuildingOfficeIcon"
      variant="gradient"
    />

    <CardBase>
      <OrgaoForm
        :form-data="form"
        :errors="errors"
        :municipios="municipios"
        :orgaos-superior="orgaosSuperior"
        :loading="loading"
        is-editing
        @submit="handleSubmit"
        @cancel="handleBack"
      />
    </CardBase>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import OrgaoForm from '@/Components/Organisms/Compdec/OrgaoForm.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  orgao: {
    type: Object,
    required: true,
  },
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

const capacidadesIniciais = props.orgao.capacidades || {};

const form = reactive({
  // Identificacao
  tipo: props.orgao.tipo,
  codigo: props.orgao.codigo,
  nome: props.orgao.nome,
  status: props.orgao.status,
  municipio_id: props.orgao.municipio_id || '',
  orgao_superior_id: props.orgao.orgao_superior_id || '',

  // Atos Legais
  lei_criacao_numero: props.orgao.lei_criacao_numero || '',
  lei_criacao_data: props.orgao.lei_criacao_data || '',
  decreto_numero: props.orgao.decreto_numero || '',
  decreto_data: props.orgao.decreto_data || '',
  portaria_numero: props.orgao.portaria_numero || '',
  portaria_data: props.orgao.portaria_data || '',

  // Quantitativos
  qtd_efetivo: props.orgao.qtd_efetivo ?? 0,
  qtd_nupdec: props.orgao.qtd_nupdec ?? 0,

  // Contato
  email: props.orgao.email || '',
  email_secundario: props.orgao.email_secundario || '',
  email_terciario: props.orgao.email_terciario || '',
  telefone: props.orgao.telefone || '',
  telefone_secundario: props.orgao.telefone_secundario || '',
  fax: props.orgao.fax || '',
  endereco: props.orgao.endereco || '',

  // Capacidades estruturais (booleans)
  tem_sede_propria: !!props.orgao.tem_sede_propria,
  tem_viatura: !!props.orgao.tem_viatura,
  tem_mapeamento_risco: !!props.orgao.tem_mapeamento_risco,
  tem_simulado: !!props.orgao.tem_simulado,
  tem_cartao_pdc: !!props.orgao.tem_cartao_pdc,

  // Capacidades soft (jsonb)
  capacidades: {
    tem_computador: !!capacidadesIniciais.tem_computador,
    tem_curso_gestao: !!capacidadesIniciais.tem_curso_gestao,
    data_curso_gestao: capacidadesIniciais.data_curso_gestao || '',
    tem_curso_sco: !!capacidadesIniciais.tem_curso_sco,
    data_curso_sco: capacidadesIniciais.data_curso_sco || '',
    tem_workshop_pdc: !!capacidadesIniciais.tem_workshop_pdc,
    data_workshop_pdc: capacidadesIniciais.data_workshop_pdc || '',
    experiencia_dc: capacidadesIniciais.experiencia_dc || '',
    tipo_experiencia_dc: capacidadesIniciais.tipo_experiencia_dc || '',
    capacitacao_nupdec: capacidadesIniciais.capacitacao_nupdec || '',
    nupdec_org_representado: capacidadesIniciais.nupdec_org_representado || '',
    obs_capacidades: capacidadesIniciais.obs_capacidades || '',
  },

  // Responsavel
  responsavel_nome: props.orgao.responsavel_nome || '',
  responsavel_cpf: props.orgao.responsavel_cpf || '',
  responsavel_email: props.orgao.responsavel_email || '',
  responsavel_telefone: props.orgao.responsavel_telefone || '',
});

function handleSubmit(formData) {
  loading.value = true;
  router.put(route('compdec.update', props.orgao.id), formData, {
    onFinish: () => {
      loading.value = false;
    },
  });
}

function handleBack() {
  router.visit(route('compdec.show', props.orgao.id));
}
</script>

<style scoped>
.orgao-edit {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
