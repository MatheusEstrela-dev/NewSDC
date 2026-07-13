<template>
  <Modal :show="show" max-width="md" align="center" @close="close">
    <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-slate-900 text-left shadow-2xl transition-all border border-slate-200 dark:border-slate-800">
      
      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-900 to-primary-800 dark:from-primary-950 dark:to-primary-900 px-6 py-5 border-b border-primary-800/30">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-4">
            <div class="bg-white/10 p-2.5 rounded-xl shadow-inner border border-white/5">
              <UserIcon class="w-6 h-6 text-blue-200" />
            </div>
            <div>
              <h3 class="text-lg font-bold leading-6 text-white mb-0.5">
                Atribuir Analista
              </h3>
              <p class="text-sm font-medium text-blue-200/80 m-0">
                Protocolo #{{ protocolo?.protocoloNumero || 'N/D' }}
              </p>
            </div>
          </div>
          <button
            type="button"
            class="rounded-lg text-blue-200 hover:text-white hover:bg-white/10 p-1.5 transition-colors focus:outline-none focus:ring-2 focus:ring-white/20"
            @click="close"
          >
            <span class="sr-only">Fechar</span>
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="px-6 py-6 pb-8">
        <div>
          <label for="analista_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mb-1.5">
            <UserIcon class="w-4 h-4 text-slate-400 dark:text-slate-500" />
            Selecione o Analista
          </label>
          
          <div class="mt-2 relative">
            <select
              id="analista_id"
              name="analista_id"
              v-model="form.analista_id"
              class="block w-full rounded-xl border-0 py-3 pl-4 pr-10 text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-800 ring-1 ring-inset ring-slate-300 dark:ring-slate-700 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6 shadow-sm transition-all"
            >
              <option value="" disabled selected>Selecione um analista...</option>
              <option v-for="analista in filteredAnalistas" :key="analista.value" :value="analista.value">
                {{ analista.label }}
              </option>
            </select>
          </div>
          <p class="mt-2 text-xs text-slate-500 flex items-center gap-1">
            <ExclamationTriangleIcon class="w-3.5 h-3.5 inline text-slate-400" />
            Selecione o analista que será responsável pelo protocolo
          </p>
          
          <InputError v-if="form.errors.analista_id" :message="form.errors.analista_id" class="mt-2" />
        </div>
      </div>

      <!-- Footer -->
      <div class="bg-slate-50 dark:bg-slate-950 px-6 py-4 flex items-center justify-end border-t border-slate-100 dark:border-slate-800 gap-3">
        <button
          type="button"
          class="text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors"
          @click="close"
          :disabled="form.processing"
        >
          Cancelar
        </button>
        <Button
          variant="primary"
          :disabled="form.processing || !form.analista_id"
          @click="assign"
          :icon="UserIcon"
          icon-position="left"
        >
          <span v-if="form.processing">Atribuindo...</span>
          <span v-else>Atribuir Processo</span>
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import UserIcon from '@/Components/Icons/UserIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import InputError from '@/Components/InputError.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  protocolo: {
    type: Object,
    default: null,
  },
  analistas: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close', 'assigned']);

// Filter empty generic options like "Todos os analistas"
const filteredAnalistas = computed(() => {
  return (props.analistas || []).filter(a => a.value !== '');
});

const form = useForm({
  analista_id: '',
});

const { show: toast } = useToast();

watch(() => props.show, (isVisible) => {
  if (isVisible) {
    form.reset();
    form.clearErrors();
    if (props.protocolo && props.protocolo.analista_atual_id) {
      form.analista_id = props.protocolo.analista_atual_id.toString();
    }
  }
});

function close() {
  form.reset();
  form.clearErrors();
  emit('close');
}

function assign() {
  if (!props.protocolo || !form.analista_id) return;

  form.put(route('pae.protocolo.assign', props.protocolo.id), {
    preserveScroll: true,
    onSuccess: () => {
      close();
      toast('Analista atribuído com sucesso!', 'success');
      emit('assigned');
    },
  });
}
</script>
