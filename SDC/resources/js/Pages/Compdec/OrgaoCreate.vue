<template>

    <div class="orgao-create">
      <div class="header-section">
        <Button variant="ghost" @click="handleBack">
          ← Voltar
        </Button>

        <Heading level="1">Criar Novo Órgão</Heading>
        <Text variant="muted">Preencha os dados para cadastrar um novo órgão de defesa civil</Text>
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
import Button from '@/Components/Atoms/Button/Button.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import OrgaoForm from '@/Components/Organisms/Compdec/OrgaoForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

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
  tipo: 'compdec',
  codigo: '',
  nome: '',
  status: 'ativo',
  municipio_id: '',
  orgao_superior_id: '',
  email: '',
  telefone: '',
  endereco: '',
  responsavel_nome: '',
  responsavel_cpf: '',
  responsavel_email: '',
  responsavel_telefone: '',
});

const handleSubmit = (formData) => {
  loading.value = true;
  router.post(route('compdec.store'), formData, {
    onFinish: () => {
      loading.value = false;
    },
  });
};

const handleBack = () => {
  router.visit(route('compdec.index'));
};
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
