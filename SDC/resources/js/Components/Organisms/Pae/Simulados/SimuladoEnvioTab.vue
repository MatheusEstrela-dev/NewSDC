<template>
  <CollapsibleSection namespace="pae" section-id="simulado-envio" title="Envio do relatório" subtitle="Datas, nível de emergência e PDF assinado do Anexo C (a CEDEC não confere assinaturas)." :icon="DocumentArrowUpIcon">
    <div class="grid gap-4 sm:grid-cols-2">
      <FormDateField v-model="form.dt_realizacao" label="Data de realização do simulado" :disabled="somenteLeitura" :error="form.errors.dt_realizacao" required />
      <FormSelect v-model="form.nivel_emergencia" label="Nível de emergência simulado" :options="OPCOES_NIVEL" placeholder="" :disabled="somenteLeitura" :error="form.errors.nivel_emergencia" required />
      <FormDateField v-model="form.dt_apresentacao" label="Apresentado à CEDEC em" :disabled="somenteLeitura" :error="form.errors.dt_apresentacao" required />
      <FormField v-model="form.num_sei" label="Número SEI" maxlength="100" :disabled="somenteLeitura" :error="form.errors.num_sei" required />
      <FormDateField v-model="form.aviso_cedec_em" label="Aviso prévio à CEDEC em (Art. 94)" hint="Antecedência mínima de 7 dias; menos que isso gera alerta informativo." :disabled="somenteLeitura" :error="form.errors.aviso_cedec_em" />
      <FormFileField v-if="!somenteLeitura" v-model="form.arquivo" label="Relatório em PDF (até 20 MiB)" :error="form.errors.arquivo" required />
      <div v-else class="text-sm text-slate-700 dark:text-slate-200">
        <p class="font-medium">Arquivo</p>
        <a v-if="selecionado && canView" :href="route('pae.protocolo.simulados.relatorios.download', [protocoloId, selecionado.id])" class="mt-1 inline-block font-semibold text-blue-700 underline dark:text-blue-300">{{ selecionado.arquivo_nome_original }}</a>
        <span v-else class="text-slate-500 dark:text-slate-400">—</span>
      </div>
      <ToggleField v-model="form.integrado" label="Simulado integrado (Art. 102)" description="Barragens que compartilham a mesma ZAS; cada protocolo registra o seu relatório." :disabled="somenteLeitura" />
      <FormTextarea v-if="form.integrado" v-model="form.barragens_integradas" label="Barragens integradas" :rows="2" :disabled="somenteLeitura" :error="form.errors.barragens_integradas" required />
      <FormTextarea v-model="form.observacao" class="sm:col-span-2" label="Observação" :rows="2" :disabled="somenteLeitura" :error="form.errors.observacao" />
    </div>
  </CollapsibleSection>
</template>

<script setup>
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormFileField from '@/Components/Molecules/Form/FormFileField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';
import { OPCOES_NIVEL } from '@/utils/paeSimulado';
import { DocumentArrowUpIcon } from '@heroicons/vue/24/outline';

defineProps({
  form: { type: Object, required: true },
  somenteLeitura: { type: Boolean, default: false },
  selecionado: { type: Object, default: null },
  protocoloId: { type: Number, required: true },
  canView: { type: Boolean, default: false },
});
</script>
