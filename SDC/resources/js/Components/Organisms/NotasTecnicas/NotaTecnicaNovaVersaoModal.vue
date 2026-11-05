<template>
  <Modal :show="show" max-width="lg" @close="fechar">
    <div class="bg-white dark:bg-slate-900">
      <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
        <div>
          <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Enviar nova versão</h2>
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ codigo }}</p>
        </div>
        <ButtonIcon :icon="XMarkIcon" title="Fechar" size="lg" @click="fechar" />
      </header>

      <form class="space-y-4 px-5 py-4" @submit.prevent="enviar">
        <p
          v-if="publicada"
          class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
        >
          A nota está publicada: a versão nova entra em vigor ao enviar, e quem já tinha lido a anterior volta a vê-la como não lida.
        </p>

        <NotaTecnicaCamposVersao :form="form" :errors="form.errors" :limite-mb="limiteMb" />

        <FormActions
          submit-label="Enviar versão"
          :loading="form.processing"
          :disabled="form.processing"
          @cancel="fechar"
          @submit="enviar"
        />
      </form>
    </div>
  </Modal>
</template>

<script setup>
/**
 * Modal de nova versao da nota: PDF, numero (sugerido pelo backend) e changelog.
 * Limpa a cada abertura, para nao reenviar o arquivo da vez anterior.
 */
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import ButtonIcon from '@/Components/Atoms/Button/ButtonIcon.vue';
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import NotaTecnicaCamposVersao from '@/Components/Molecules/NotasTecnicas/NotaTecnicaCamposVersao.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  notaId: { type: Number, required: true },
  codigo: { type: String, default: '' },
  publicada: { type: Boolean, default: false },
  proximaVersao: { type: String, default: '' },
  limiteMb: { type: Number, default: 30 },
});

const emit = defineEmits(['close']);

const form = useForm({ arquivo: null, numero: '', changelog: '' });

watch(() => props.show, (aberto) => {
  if (!aberto) return;

  form.reset();
  form.clearErrors();
  form.numero = props.proximaVersao;
});

function enviar() {
  form.post(route('notas-tecnicas.versoes.store', props.notaId), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => emit('close'),
  });
}

function fechar() {
  if (!form.processing) emit('close');
}
</script>
