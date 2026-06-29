<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PmdaWizardPanel from '@/Components/Molecules/Pmda/PmdaWizardPanel.vue';
import PaperClipIcon from '@/Components/Icons/PaperClipIcon.vue';
import DocumentIcon from '@/Components/Icons/DocumentIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';

const props = defineProps({
  plano: { type: Object, required: true },
});

const emit = defineEmits(['prev', 'revisar']);

const uploading = ref('');
const enviando = ref(false);

function upload(colecao, event) {
  const arquivo = event.target.files?.[0];
  if (!arquivo) return;
  uploading.value = colecao;
  router.post(route('pmda.planos.anexos.store', props.plano.id), { colecao, arquivo }, {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => { uploading.value = ''; event.target.value = ''; },
  });
}

function enviar() {
  enviando.value = true;
  router.post(route('pmda.planos.enviar', props.plano.id), {}, {
    preserveScroll: true,
    onFinish: () => { enviando.value = false; },
  });
}

const docs = [
  { colecao: 'termo', titulo: 'Termo de Compromisso', desc: 'Assinado pelo Prefeito e Coordenador COMPDEC (Max 5MB)' },
  { colecao: 'oficio', titulo: 'Ofício de Solicitação', desc: 'Papel timbrado justificando o pedido (Max 5MB)' },
];
</script>

<template>
  <PmdaWizardPanel
    :step="7"
    title="Documentação Anexa"
    subtitle="Upload de termo de compromisso e ofícios."
    :icon="PaperClipIcon"
  >
    <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
      Faça o upload dos documentos obrigatórios digitalizados em formato PDF. Certifique-se de que estão assinados.
    </p>

    <div class="space-y-3">
      <div
        v-for="d in docs"
        :key="d.colecao"
        class="flex flex-col gap-3 rounded-lg border border-slate-200 p-4 dark:border-slate-700/50 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="flex items-start gap-3">
          <DocumentIcon class="h-6 w-6 flex-shrink-0 text-slate-400 dark:text-slate-500" />
          <div>
            <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
              {{ d.titulo }} <span class="text-red-500">*</span>
            </span>
            <span class="block text-xs text-slate-500 dark:text-slate-400">{{ d.desc }}</span>
            <span
              v-if="plano.anexos && plano.anexos[d.colecao]"
              class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400"
            >
              <CheckIcon class="h-3.5 w-3.5" />
              {{ plano.anexos[d.colecao].nome }}
            </span>
          </div>
        </div>
        <label class="cursor-pointer rounded-md border border-slate-300 px-3 py-1.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
          {{ uploading === d.colecao ? 'Enviando...' : 'Escolher arquivo' }}
          <input type="file" accept="application/pdf" class="hidden" @change="(e) => upload(d.colecao, e)" />
        </label>
      </div>
    </div>

    <template #footer>
      <div class="flex w-full flex-col gap-3 rounded-lg bg-slate-800 p-4 text-white sm:flex-row sm:items-center sm:justify-between dark:bg-slate-900">
        <div>
          <span class="block font-semibold">Pronto para envio?</span>
          <span class="block text-sm text-slate-300">Ao enviar, o PMDA será analisado pela CEDEC-MG.</span>
        </div>
        <div class="flex items-center gap-3">
          <button type="button" class="rounded-md border border-slate-500 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-slate-700" @click="emit('revisar')">
            Revisar
          </button>
          <button
            type="button"
            class="pmda-btn-next pmda-btn-send"
            :disabled="enviando"
            @click="enviar"
          >
            <CheckIcon class="h-4 w-4" />
            Enviar Protocolo PMDA
          </button>
        </div>
      </div>
    </template>
  </PmdaWizardPanel>
</template>
