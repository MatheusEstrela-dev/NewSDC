<template>
  <Modal :show="show" max-width="lg" @close="fechar">
    <div class="bg-white dark:bg-slate-900">
      <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
        <div>
          <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Publicar versão {{ rotulo }}</h2>
          <p class="text-xs text-slate-500 dark:text-slate-400">Os alunos da versão anterior serão avisados do que mudou.</p>
        </div>
        <ButtonIcon :icon="XMarkIcon" title="Fechar" size="lg" @click="fechar" />
      </header>

      <form class="space-y-4 px-5 py-4" @submit.prevent="confirmar">
        <FormTextarea
          v-model="notas"
          label="O que mudou"
          placeholder="Resumo das mudanças em relação à versão anterior."
          :rows="4"
          :maxlength="1000"
          :error="erro"
        />

        <FormActions
          submit-label="Publicar versão"
          submit-variant="success"
          :loading="processando"
          :disabled="processando || !notas.trim()"
          @cancel="fechar"
          @submit="confirmar"
        />
      </form>
    </div>
  </Modal>
</template>

<script setup>
/**
 * Modal exibido ao publicar um rascunho de versao > 1: pede o "O que mudou",
 * que vira o aviso aos alunos e o historico da versao. Limpa a cada abertura.
 */
import { ref, watch } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import ButtonIcon from '@/Components/Atoms/Button/ButtonIcon.vue';
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  rotulo: { type: String, default: '' },
  processando: { type: Boolean, default: false },
  erro: { type: String, default: '' },
});

const emit = defineEmits(['close', 'confirmar']);

const notas = ref('');

watch(() => props.show, (aberto) => {
  if (aberto) notas.value = '';
});

function confirmar() {
  const texto = notas.value.trim();
  if (texto && !props.processando) emit('confirmar', texto);
}

function fechar() {
  if (!props.processando) emit('close');
}
</script>
