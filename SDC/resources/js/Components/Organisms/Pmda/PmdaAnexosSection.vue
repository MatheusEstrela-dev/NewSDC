<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PmdaWizardPanel from '@/Components/Molecules/Pmda/PmdaWizardPanel.vue';
import PaperClipIcon from '@/Components/Icons/PaperClipIcon.vue';
import DocumentIcon from '@/Components/Icons/DocumentIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import { useToast } from '@/Composables/useToast.js';

const props = defineProps({
  plano: { type: Object, required: true },
});

const emit = defineEmits(['prev', 'revisar']);
const { show: toast } = useToast();

const uploading = ref('');
const progresso = ref(0);
const enviando = ref(false);

function upload(colecao, event) {
  const arquivo = event.target.files?.[0];
  if (!arquivo) return;
  uploading.value = colecao;
  progresso.value = 0;
  router.post(route('pmda.planos.anexos.store', props.plano.id), { colecao, arquivo }, {
    forceFormData: true,
    preserveScroll: true,
    onProgress: (e) => { if (e?.percentage != null) progresso.value = Math.round(e.percentage); },
    onSuccess: () => { toast('Documento enviado com sucesso.', 'success'); },
    onError: () => { toast('Falha no envio do documento. Tente novamente.', 'error'); },
    onFinish: () => { uploading.value = ''; progresso.value = 0; event.target.value = ''; },
  });
}

function enviar() {
  enviando.value = true;
  router.post(route('pmda.planos.enviar', props.plano.id), {}, {
    preserveScroll: true,
    onSuccess: () => { toast('PMDA enviado para análise da CEDEC-MG.', 'success'); },
    // O backend recusa o envio nomeando o que falta (comunidade, representantes
    // ou os PDFs). Sem repassar essa mensagem o botao parece quebrado: a acao
    // nao acontece e a tela nao diz por que.
    onError: (errors) => {
      toast(errors.enviar ?? 'Não foi possível enviar o PMDA.', 'error');
    },
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
        <div class="flex flex-col items-stretch gap-2 sm:w-56">
          <label class="cursor-pointer rounded-md border border-slate-300 px-3 py-1.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
            {{ uploading === d.colecao ? `Enviando... ${progresso}%` : 'Escolher arquivo' }}
            <input type="file" accept="application/pdf" class="hidden" :disabled="uploading === d.colecao" @change="(e) => upload(d.colecao, e)" />
          </label>
          <div v-if="uploading === d.colecao" class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
            <div class="h-full rounded-full bg-blue-600 transition-all duration-150" :style="{ width: progresso + '%' }"></div>
          </div>
        </div>
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
