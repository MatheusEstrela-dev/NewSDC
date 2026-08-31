<template>
  <form class="space-y-4" @submit.prevent="$emit('submit')">
    <CollapsibleSection
      namespace="cisterna"
      section-id="vistoria-responsavel"
      title="Responsavel tecnico"
      subtitle="Engenheiro, data e local do relatorio"
      :icon="WrenchScrewdriverIcon"
      tom="info"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <FormField v-model="form.engenheiro_nome" label="Engenheiro" maxlength="150" required :error="erros.engenheiro_nome" />
        <FormField v-model="form.engenheiro_crea" label="CREA" maxlength="30" required :error="erros.engenheiro_crea" />
        <FormDateField v-model="form.data_relatorio" label="Data do relatorio" required :error="erros.data_relatorio" />
        <FormField v-model="form.local_relatorio" label="Local do relatorio" maxlength="255" required :error="erros.local_relatorio" />

        <!--
          O numero de instalacao so existe na etapa do fornecedor: e ela que
          aloca. Nas outras o Request marca o campo como `prohibited`, entao
          mostrar aqui geraria erro de validacao sem o usuario entender.
          Vazio = a sequence do Postgres decide o proximo.
        -->
        <FormField
          v-if="alocaNumero"
          v-model="form.numero_instalacao"
          label="Nº de instalacao"
          type="number"
          :error="erros.numero_instalacao"
          hint="Em branco, o sistema atribui o proximo numero livre"
        />
      </div>
    </CollapsibleSection>

    <!--
      Dados administrativos so na etapa da CEDEC: obrigatorios ali e proibidos
      nas outras. No legado nada impedia enviar processo_sei numa vistoria de
      fornecedor, e o campo era ignorado em silencio.
    -->
    <CollapsibleSection
      v-if="exigeAdministrativos"
      namespace="cisterna"
      section-id="vistoria-administrativo"
      title="Dados administrativos"
      subtitle="Processo, contrato e empenho que amparam a fiscalizacao"
      :icon="DocumentTextIcon"
      tom="warning"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <FormField v-model="form.processo_sei" label="Processo SEI" maxlength="100" required :error="erros.processo_sei" />
        <FormField v-model="form.contrato" label="Contrato" maxlength="100" required :error="erros.contrato" />
        <FormField v-model="form.empenho" label="Empenho" maxlength="100" required :error="erros.empenho" />
        <!-- Inteiro no Request, nao texto: e a quantidade de placas. -->
        <FormField v-model="form.placa_obras" label="Placas de obra" type="number" required :error="erros.placa_obras" />
        <!--
          A ART e exigida so aqui. Fora da CEDEC o Request a marca como
          `prohibited`, entao ela nao pode aparecer nas outras etapas.
        -->
        <FormField v-model="form.engenheiro_art" label="ART" maxlength="50" required :error="erros.engenheiro_art" />
      </div>
    </CollapsibleSection>

    <CollapsibleSection
      namespace="cisterna"
      section-id="vistoria-local"
      title="Local da instalacao"
      subtitle="Endereco e coordenada conferidos no local"
      :icon="MapPinIcon"
      tom="info"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <FormField v-model="form.endereco" label="Endereco" maxlength="150" :error="erros.endereco" />
        <FormField v-model="form.bairro" label="Bairro" maxlength="100" :error="erros.bairro" />

        <div class="sm:col-span-2">
          <CoordenadaField
            v-model:latitude="form.latitude"
            v-model:longitude="form.longitude"
            :error="erros"
          />
        </div>
      </div>
    </CollapsibleSection>

    <ChecklistItens v-model="form.itens" :itens="itens" />

    <CollapsibleSection
      namespace="cisterna"
      section-id="vistoria-anexos"
      title="Assinatura e observacoes"
      subtitle="Assinatura do engenheiro e anotacoes da vistoria"
      :icon="PencilSquareIcon"
      tom="neutro"
    >
      <div class="space-y-3">
        <ArquivoField
          label="Assinatura do engenheiro"
          :error="erros.assinatura_engenheiro"
          @change="(f) => $emit('arquivo', { campo: 'assinatura_engenheiro', arquivo: f })"
        />
        <FormTextarea v-model="form.observacoes" label="Observacoes" :rows="4" maxlength="1000" :error="erros.observacoes" />
      </div>
    </CollapsibleSection>

    <!-- Ver BeneficiarioForm: sem `@submit` o botao nao dispara nada, porque o
         botao do FormActions e type="button". -->
    <FormActions
      :loading="processando"
      :submit-label="modo === 'editar' ? 'Salvar relatorio' : 'Registrar relatorio'"
      @submit="$emit('submit')"
      @cancel="$emit('cancel')"
    />
  </form>
</template>

<script setup>
import { computed } from 'vue';
import {
  WrenchScrewdriverIcon,
  DocumentTextIcon,
  MapPinIcon,
  PencilSquareIcon,
} from '@heroicons/vue/24/outline';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import CoordenadaField from '@/Components/Molecules/Cisterna/CoordenadaField.vue';
import ArquivoField from '@/Components/Molecules/Cisterna/ArquivoField.vue';
import ChecklistItens from '@/Components/Organisms/Cisterna/ChecklistItens.vue';

/**
 * UM formulario para as tres etapas, com o que muda vindo da etapa.
 *
 * No legado cada etapa tinha duas views -- uma para preencher e outra para
 * editar -- e so a da CEDEC somava 1.216 linhas quase identicas. As diferencas
 * reais entre etapas sao duas: quem aloca o numero de instalacao (fornecedor) e
 * quem exige dados administrativos (CEDEC).
 */
const props = defineProps({
  form: { type: Object, required: true },
  /** ItemInstalacao::options(), com unidade e aceita_detalhes. */
  itens: { type: Array, default: () => [] },
  etapa: { type: String, required: true },
  processando: { type: Boolean, default: false },
  modo: { type: String, default: 'criar' },
});

defineEmits(['submit', 'cancel', 'arquivo']);

const erros = computed(() => props.form?.errors ?? {});

const alocaNumero = computed(() => props.etapa === 'fornecedor');

const exigeAdministrativos = computed(() => props.etapa === 'cedec');
</script>
