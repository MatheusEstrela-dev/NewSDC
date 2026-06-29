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

// Termo de compromisso versionado (arquivo estatico em public/docs/pmda).
const TERMO_URL = '/docs/pmda/termo-compromisso-2026.pdf';
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
        <strong v-if="protocolo">Protocolo Nº: {{ protocolo }}</strong>
        Para envio do PMDA é obrigatório fazer o Download do <strong>TERMO DE COMPROMISSO</strong>,
        preencher, assinar e enviar cópia digitalizada anexada na aba "Anexos", juntamente com a
        cópia de ofício em papel timbrado da Prefeitura Municipal.
      </div>

      <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
          <label class="pmda-field-label" for="pmda-motivo">Motivo / Descrição do Pedido</label>
          <textarea
            id="pmda-motivo"
            v-model="form.motivo"
            rows="6"
            class="atom-input atom-input-normal atom-input-md w-full"
            placeholder="Descreva brevemente a situação de estiagem/seca no município..."
          ></textarea>
        </div>

        <div class="flex flex-col items-center justify-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-5 dark:border-slate-700/50 dark:bg-slate-800/40">
          <DownloadIcon class="h-7 w-7 text-slate-400 dark:text-slate-500" />
          <a
            :href="TERMO_URL"
            target="_blank"
            class="pmda-btn-next"
            download
          >
            Baixar Termo de Compromisso (PDF)
          </a>
          <span class="text-xs text-slate-400 dark:text-slate-500">Versão atualizada 2026</span>
        </div>
      </div>
    </div>
  </PmdaWizardPanel>
</template>
