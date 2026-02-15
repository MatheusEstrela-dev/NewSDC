<template>

    <div class="orgao-edit">
      <div class="header-section">
        <Button variant="ghost" @click="handleBack">
          ← Voltar
        </Button>

        <Heading level="1">Editar Órgão</Heading>
        <Text variant="muted">{{ orgao.codigo }} - {{ orgao.nome }}</Text>
      </div>

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
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import OrgaoForm from '@/Components/Organisms/Compdec/OrgaoForm.vue';

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

const form = reactive({
  tipo: props.orgao.tipo,
  codigo: props.orgao.codigo,
  nome: props.orgao.nome,
  status: props.orgao.status,
  municipio_id: props.orgao.municipio_id || '',
  orgao_superior_id: props.orgao.orgao_superior_id || '',
  email: props.orgao.email || '',
  telefone: props.orgao.telefone || '',
  endereco: props.orgao.endereco || '',
  responsavel_nome: props.orgao.responsavel_nome || '',
  responsavel_cpf: props.orgao.responsavel_cpf || '',
  responsavel_email: props.orgao.responsavel_email || '',
  responsavel_telefone: props.orgao.responsavel_telefone || '',
});

const handleSubmit = (formData) => {
  loading.value = true;
  router.put(route('compdec.update', props.orgao.id), formData, {
    onFinish: () => {
      loading.value = false;
    },
  });
};

const handleBack = () => {
  router.visit(route('compdec.show', props.orgao.id));
};
</script>

<style scoped>
.orgao-edit {
  padding: 2rem;
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
