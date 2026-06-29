<script setup>
import PmdaWizardPanel from '@/Components/Molecules/Pmda/PmdaWizardPanel.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';

defineProps({
  form: { type: Object, required: true },
  saving: { type: Boolean, default: false },
});

defineEmits(['next', 'prev']);

const opcoes = [
  { campo: 'acao_decreto_se', titulo: 'Decretação de Situação de Emergência (SE)', desc: 'O município possui decreto válido e reconhecido pelo Estado/União.' },
  { campo: 'acao_caminhao_pipa', titulo: 'Uso de Caminhão-Pipa Próprio ou Locado', desc: 'A prefeitura já está operando distribuição com recursos próprios.' },
  { campo: 'acao_cestas_basicas', titulo: 'Distribuição de Cestas Básicas', desc: 'Apoio a famílias que perderam subsistência agrícola.' },
];
</script>

<template>
  <PmdaWizardPanel
    :step="6"
    title="Ações Executadas pelo Município"
    subtitle="Ações adotadas para enfrentar a estiagem."
    :icon="CheckBadgeIcon"
    :saving="saving"
    @next="$emit('next')"
    @prev="$emit('prev')"
  >
    <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
      Assinale e descreva as ações prévias que o município já tomou para mitigar os efeitos da
      estiagem antes de solicitar apoio estadual.
    </p>

    <div class="space-y-3">
      <label
        v-for="op in opcoes"
        :key="op.campo"
        class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 transition-colors hover:bg-slate-50 dark:border-slate-700/50 dark:hover:bg-slate-800/40"
      >
        <input
          v-model="form[op.campo]"
          type="checkbox"
          class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 dark:border-slate-600 dark:bg-slate-800"
        />
        <span>
          <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">{{ op.titulo }}</span>
          <span class="block text-xs text-slate-500 dark:text-slate-400">{{ op.desc }}</span>
        </span>
      </label>
    </div>

    <div class="mt-5">
      <label class="pmda-field-label">Justificativa da Necessidade de Apoio Estadual <span class="req">*</span></label>
      <textarea
        v-model="form.justificativa_apoio"
        rows="5"
        class="atom-input atom-input-normal atom-input-md w-full"
        placeholder="Justifique por que a capacidade de resposta do município foi ultrapassada..."
      ></textarea>
    </div>
  </PmdaWizardPanel>
</template>
