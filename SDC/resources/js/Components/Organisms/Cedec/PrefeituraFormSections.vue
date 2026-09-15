<template>
  <div class="space-y-6">
    <CollapsibleSection
      namespace="cedec"
      section-id="prefeitura-prefeito"
      title="Prefeito"
      subtitle="Nome, partido e contato pessoal do prefeito"
      :icon="UserIcon"
      tom="info"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <FormField
          v-model="form.prefeito_nome"
          label="Nome do prefeito"
          placeholder="Nome completo"
          maxlength="255"
          :readonly="!podeEditar"
          :error="errors.prefeito_nome"
        />
        <FormField
          v-if="camposInstitucionais"
          v-model="form.prefeito_partido"
          label="Partido"
          placeholder="Sigla do partido"
          maxlength="60"
          :readonly="!podeEditar"
          :error="errors.prefeito_partido"
        />
        <FormField
          v-model="form.prefeito_telefone"
          label="Telefone do prefeito"
          mask="telefone"
          inputmode="tel"
          maxlength="15"
          placeholder="(31) 3333-3333"
          :readonly="!podeEditar"
          :error="errors.prefeito_telefone"
        />
        <FormField
          v-model="form.prefeito_celular"
          label="Celular do prefeito"
          mask="telefone"
          inputmode="tel"
          maxlength="15"
          placeholder="(31) 99999-9999"
          :readonly="!podeEditar"
          :error="errors.prefeito_celular"
        />
        <FormField
          v-model="form.prefeito_email"
          label="E-mail do prefeito"
          type="email"
          inputmode="email"
          maxlength="255"
          placeholder="prefeito@municipio.mg.gov.br"
          :readonly="!podeEditar"
          :error="errors.prefeito_email"
        />
      </div>
    </CollapsibleSection>

    <!--
      Contatos institucionais sao as colunas acrescentadas na fase 1. O
      UpsertPrefeituraRequest do Compdec nao as valida, e campo que o backend descarta
      em silencio e pior que campo ausente: por isso a secao inteira fica atras de
      camposInstitucionais.
    -->
    <CollapsibleSection
      v-if="camposInstitucionais"
      namespace="cedec"
      section-id="prefeitura-contatos"
      title="Contatos institucionais"
      subtitle="E-mails e telefones da prefeitura, nao do prefeito"
      :icon="EnvelopeIcon"
      tom="success"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <FormField
          v-model="form.email_prefeitura"
          label="E-mail institucional 1"
          type="email"
          inputmode="email"
          maxlength="255"
          placeholder="prefeitura@municipio.mg.gov.br"
          hint="Alimenta o relatório de contatos da CEDEC"
          :readonly="!podeEditar"
          :error="errors.email_prefeitura"
        />
        <FormField
          v-model="form.email_prefeitura_2"
          label="E-mail institucional 2"
          type="email"
          inputmode="email"
          maxlength="255"
          :readonly="!podeEditar"
          :error="errors.email_prefeitura_2"
        />
        <FormField
          v-model="form.email_prefeitura_3"
          label="E-mail institucional 3"
          type="email"
          inputmode="email"
          maxlength="255"
          :readonly="!podeEditar"
          :error="errors.email_prefeitura_3"
        />
        <FormField
          v-model="form.tel_prefeitura"
          label="Telefone da prefeitura 1"
          mask="telefone"
          inputmode="tel"
          maxlength="15"
          placeholder="(31) 3333-3333"
          :readonly="!podeEditar"
          :error="errors.tel_prefeitura"
        />
        <FormField
          v-model="form.tel_prefeitura_2"
          label="Telefone da prefeitura 2"
          mask="telefone"
          inputmode="tel"
          maxlength="15"
          :readonly="!podeEditar"
          :error="errors.tel_prefeitura_2"
        />
        <FormField
          v-model="form.fax_prefeitura"
          label="Fax da prefeitura"
          mask="telefone"
          inputmode="tel"
          maxlength="15"
          :readonly="!podeEditar"
          :error="errors.fax_prefeitura"
        />
      </div>
    </CollapsibleSection>

    <CollapsibleSection
      namespace="cedec"
      section-id="prefeitura-endereco"
      title="Endereço"
      subtitle="Logradouro, bairro, CEP e coordenada da sede"
      :icon="MapPinIcon"
      tom="info"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <FormField
          v-model="form.endereco"
          label="Logradouro"
          placeholder="Rua, número"
          :readonly="!podeEditar"
          :error="errors.endereco"
          class="sm:col-span-2 lg:col-span-3"
        />
        <FormField
          v-model="form.bairro"
          label="Bairro"
          placeholder="Centro"
          maxlength="120"
          :readonly="!podeEditar"
          :error="errors.bairro"
        />
        <FormField
          v-model="form.cep"
          label="CEP"
          mask="cep"
          inputmode="numeric"
          maxlength="9"
          placeholder="00000-000"
          :readonly="!podeEditar"
          :error="errors.cep"
        />
        <div class="hidden lg:block"></div>
        <FormField
          v-model="form.latitude"
          label="Latitude"
          type="number"
          step="0.0000001"
          inputmode="decimal"
          placeholder="-19.9166813"
          hint="Entre -90 e 90, com ponto decimal"
          :readonly="!podeEditar"
          :error="errors.latitude"
        />
        <FormField
          v-model="form.longitude"
          label="Longitude"
          type="number"
          step="0.0000001"
          inputmode="decimal"
          placeholder="-43.9344931"
          hint="Entre -180 e 180, com ponto decimal"
          :readonly="!podeEditar"
          :error="errors.longitude"
        />
      </div>
    </CollapsibleSection>

    <CollapsibleSection
      namespace="cedec"
      section-id="prefeitura-inss"
      title="INSS"
      subtitle="Cobrança do município sobre serviços prestados"
      :icon="BanknotesIcon"
      tom="warning"
    >
      <div class="space-y-4">
        <div :class="podeEditar ? '' : 'pointer-events-none opacity-60'">
          <ToggleField
            v-model="form.inss_tem_cobranca"
            label="Possui cobrança de INSS"
            description="Ligue para informar alíquota, lei e responsável"
          />
        </div>

        <div v-if="form.inss_tem_cobranca" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <FormField
            v-model="form.inss_aliquota"
            label="Alíquota (%)"
            type="number"
            step="0.01"
            inputmode="decimal"
            placeholder="2.50"
            hint="Entre 0 e 100"
            :readonly="!podeEditar"
            :error="errors.inss_aliquota"
          />
          <FormField
            v-model="form.inss_lei_cobranca"
            label="Lei de cobrança"
            placeholder="Lei municipal 1234/2020"
            maxlength="120"
            :readonly="!podeEditar"
            :error="errors.inss_lei_cobranca"
          />
          <FormField
            v-model="form.inss_responsavel"
            label="Responsável pelo recolhimento"
            placeholder="Nome do responsável ou setor"
            maxlength="255"
            :readonly="!podeEditar"
            :error="errors.inss_responsavel"
          />
        </div>
      </div>
    </CollapsibleSection>
  </div>
</template>

<script setup>
import {
  BanknotesIcon,
  EnvelopeIcon,
  MapPinIcon,
  UserIcon,
} from '@heroicons/vue/24/outline';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';

/**
 * Campos da prefeitura, em quatro secoes. E o UNICO lugar onde esses campos existem:
 * a aba do Compdec e o Edit estadual do Cedec montam este mesmo organismo.
 *
 * Nao conhece rota, nao conhece useForm e nao consulta permissao. Quem sabe se pode
 * editar e o consumidor, que informa em podeEditar -- os dois donos tem slug
 * diferente (compdec.prefeitura.edit contra cedec.prefeituras.edit) e uma checagem
 * interna teria de escolher um.
 */
defineProps({
  /** Objeto reativo de campos: useForm do Inertia ou reactive simples. */
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  podeEditar: { type: Boolean, default: false },
  /**
   * Liga as sete colunas acrescentadas na fase 1 (partido e contatos
   * institucionais). Fica em false na aba do Compdec, cujo UpsertPrefeituraRequest
   * ainda nao as valida.
   */
  camposInstitucionais: { type: Boolean, default: true },
});
</script>
