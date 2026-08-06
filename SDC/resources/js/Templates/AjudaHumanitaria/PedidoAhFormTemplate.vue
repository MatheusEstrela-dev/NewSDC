<template>
  <div class="pedido-ah-form">
    <PageHeader
      title="Novo Pedido de Ajuda Humanitária"
      description="Solicitação de material do município ao CEDEC"
      :icon="HeartIcon"
      :icon-image="moduleIcon('ajuda-humanitaria')"
      variant="gradient"
    />

    <form class="mt-6 space-y-6" @submit.prevent="$emit('submit')">
      <FormSection title="Dados do Pedido" :icon="DocumentTextIcon">
        <div class="grid gap-4 md:grid-cols-2">
          <FormSelect
            :model-value="form.municipio_id"
            label="Município"
            :options="municipios"
            :required="true"
            :disabled="municipioFixo !== null"
            :error="errors.municipio_id"
            placeholder="Selecione o município"
            @update:model-value="atualizar('municipio_id', $event)"
          />

          <FormSelect
            :model-value="form.cobrade_id"
            label="COBRADE"
            :options="cobrades"
            :required="true"
            :error="errors.cobrade_id"
            placeholder="Selecione o desastre"
            @update:model-value="atualizar('cobrade_id', $event)"
          />

          <FormField
            :model-value="form.pop_atendida"
            label="População atendida"
            type="number"
            :required="true"
            :error="errors.pop_atendida"
            placeholder="Número de pessoas"
            @update:model-value="atualizar('pop_atendida', $event)"
          />
        </div>

        <FormTextarea
          class="mt-4"
          :model-value="form.esforcos_realizados"
          label="Esforços já realizados pelo município"
          :rows="4"
          :required="true"
          :maxlength="1000"
          :error="errors.esforcos_realizados"
          placeholder="Descreva as ações que o município já executou"
          @update:model-value="atualizar('esforcos_realizados', $event)"
        />
      </FormSection>

      <FormSection title="Decreto" :icon="ClipboardIcon">
        <ToggleField
          :model-value="form.decreto_se_ecp_vig"
          label="Possui decreto de SE ou ECP vigente"
          @update:model-value="atualizar('decreto_se_ecp_vig', $event)"
        />

        <div v-if="form.decreto_se_ecp_vig" class="mt-4 grid gap-4 md:grid-cols-3">
          <FormSelect
            :model-value="form.tipo_decreto"
            label="Tipo"
            :options="tiposDecreto"
            :error="errors.tipo_decreto"
            placeholder="Selecione"
            @update:model-value="atualizar('tipo_decreto', $event)"
          />

          <FormField
            :model-value="form.numero_decreto"
            label="Número do decreto"
            :error="errors.numero_decreto"
            placeholder="Ex.: 123/2026"
            @update:model-value="atualizar('numero_decreto', $event)"
          />

          <FormDateField
            :model-value="form.vigencia_decreto"
            label="Vigência até"
            :error="errors.vigencia_decreto"
            @update:model-value="atualizar('vigencia_decreto', $event)"
          />
        </div>
      </FormSection>

      <FormSection
        title="Coordenador da COMPDEC"
        subtitle="Preenchido a partir da equipe cadastrada no órgão"
        :icon="UsersIcon"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormField
            :model-value="form.nome_coordenador"
            label="Nome"
            :error="errors.nome_coordenador"
            @update:model-value="atualizar('nome_coordenador', $event)"
          />
          <FormField
            :model-value="form.email_coordenador"
            label="E-mail"
            type="email"
            :error="errors.email_coordenador"
            @update:model-value="atualizar('email_coordenador', $event)"
          />
          <FormField
            :model-value="form.tel_coordenador"
            label="Telefone"
            :error="errors.tel_coordenador"
            @update:model-value="atualizar('tel_coordenador', $event)"
          />
          <FormField
            :model-value="form.cel_coordenador"
            label="Celular"
            :error="errors.cel_coordenador"
            @update:model-value="atualizar('cel_coordenador', $event)"
          />
        </div>
      </FormSection>

      <FormSection title="Prefeito" :icon="BuildingIcon">
        <div class="grid gap-4 md:grid-cols-2">
          <FormField
            :model-value="form.nome_prefeito"
            label="Nome"
            :error="errors.nome_prefeito"
            @update:model-value="atualizar('nome_prefeito', $event)"
          />
          <FormField
            :model-value="form.email_prefeito"
            label="E-mail"
            type="email"
            :error="errors.email_prefeito"
            @update:model-value="atualizar('email_prefeito', $event)"
          />
          <FormField
            :model-value="form.tel_prefeito"
            label="Telefone"
            :error="errors.tel_prefeito"
            @update:model-value="atualizar('tel_prefeito', $event)"
          />
          <FormField
            :model-value="form.cel_prefeito"
            label="Celular"
            :error="errors.cel_prefeito"
            @update:model-value="atualizar('cel_prefeito', $event)"
          />
        </div>
      </FormSection>

      <FormActions
        submit-label="Abrir pedido"
        cancel-label="Cancelar"
        :disabled="processing"
        @submit="$emit('submit')"
        @cancel="$emit('cancel')"
      />
    </form>
  </div>
</template>

<script setup>
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';
import FormSection from '@/Components/Organisms/FormSection.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import HeartIcon from '@/Components/Icons/HeartIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  form: { type: Object, required: true },
  municipios: { type: Array, default: () => [] },
  cobrades: { type: Array, default: () => [] },
  tiposDecreto: { type: Array, default: () => [] },
  municipioFixo: { type: Number, default: null },
  errors: { type: Object, default: () => ({}) },
  processing: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'cancel', 'campo-alterado']);

function atualizar(campo, valor) {
  emit('campo-alterado', { campo, valor });
}
</script>
