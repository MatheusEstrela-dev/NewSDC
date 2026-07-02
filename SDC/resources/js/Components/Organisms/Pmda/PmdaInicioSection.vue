<script setup>
import PmdaWizardPanel from '@/Components/Molecules/Pmda/PmdaWizardPanel.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';

defineProps({
  form: { type: Object, required: true },
  protocolo: { type: String, default: '' },
  nextLabel: { type: String, default: 'Salvar e Avançar' },
  saving: { type: Boolean, default: false },
});

defineEmits(['next', 'prev']);

// Documentos versionados (arquivos estaticos em public/docs/pmda).
const DOWNLOADS = [
  { url: '/docs/pmda/passo-a-passo-compdec-2026.pdf', label: 'Passo a Passo (PDF)' },
  { url: '/docs/pmda/termo-compromisso-2026.pdf', label: 'Termo de Compromisso (PDF)' },
  { url: '/docs/pmda/declaracao-iss-2026.pdf', label: 'Declaração ISS (PDF)' },
];
</script>

<template>
  <PmdaWizardPanel
    :step="1"
    title="Dados Gerais do PMDA"
    subtitle="Informações iniciais e validação do protocolo."
    :icon="DocumentTextIcon"
    :next-label="nextLabel"
    :saving="saving"
    @next="$emit('next')"
    @prev="$emit('prev')"
  >
    <div class="space-y-5">
      <div class="pmda-info-box info">
        <strong v-if="protocolo" class="block">Protocolo Nº: {{ protocolo }}</strong>
        <p>
          Para envio do PMDA é obrigatório fazer o Download do <strong>TERMO DE COMPROMISSO</strong>,
          preencher, assinar e enviar cópia digitalizada anexada no sistema, e a inserção de
          <strong>cópia de ofício em papel timbrado da Prefeitura Municipal</strong>, contendo as seguintes informações:
        </p>
        <ol class="ml-5 mt-2 list-decimal space-y-1">
          <li>Se o município possui lei que institui cobrança do Imposto sobre Serviços (ISS) ou equivalente;</li>
          <li>Se o imposto incide sobre o serviço de Transporte e Distribuição de Água Potável;</li>
          <li>Se incidente, qual a alíquota aplicável e qual a base de cálculo;</li>
          <li>A quem cabe a responsabilidade pelo pagamento (se ao prestador ou ao contratante).</li>
        </ol>
      </div>

      <div class="grid grid-cols-1 items-stretch gap-5 md:grid-cols-2">
        <div class="flex h-full flex-col">
          <label class="pmda-field-label" for="pmda-motivo">Motivo / Descrição do Pedido</label>
          <textarea
            id="pmda-motivo"
            v-model="form.motivo"
            rows="6"
            class="atom-input atom-input-normal atom-input-md h-full min-h-[12rem] w-full flex-1"
            placeholder="Descreva brevemente a situação de estiagem/seca no município..."
          ></textarea>
        </div>

        <div class="flex h-full flex-col justify-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-5 dark:border-slate-700/50 dark:bg-slate-800/40">
          <span class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <DownloadIcon class="h-5 w-5 text-slate-400 dark:text-slate-500" />
            Documentos para download
          </span>
          <a
            v-for="doc in DOWNLOADS"
            :key="doc.url"
            :href="doc.url"
            target="_blank"
            class="pmda-btn-next w-full justify-center"
            download
          >
            {{ doc.label }}
          </a>
          <span class="text-xs text-slate-400 dark:text-slate-500">Versão atualizada 2026</span>
        </div>
      </div>
    </div>
  </PmdaWizardPanel>
</template>
