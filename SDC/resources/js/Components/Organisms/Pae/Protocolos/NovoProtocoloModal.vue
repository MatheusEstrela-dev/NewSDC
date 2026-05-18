<template>
  <Modal :show="show" max-width="md" align="center" @close="close">
    <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-slate-900 text-left shadow-2xl transition-all border border-slate-200 dark:border-slate-800">

      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-900 to-primary-800 dark:from-primary-950 dark:to-primary-900 px-6 py-5 border-b border-primary-800/30">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-4">
            <div class="bg-white/10 p-2.5 rounded-xl shadow-inner border border-white/5">
              <ClipboardDocumentListIcon class="w-6 h-6 text-blue-200" />
            </div>
            <div>
              <h3 class="text-lg font-bold leading-6 text-white mb-0.5">
                Novo Protocolo PAE
              </h3>
              <p class="text-sm font-medium text-blue-200/80 m-0">
                Informe o empreendedor e a estrutura
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
      <div class="px-6 py-6 pb-8 space-y-5">
        <!-- Empreendedor -->
        <div>
          <label for="empreendedor_nome" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
            Empreendedor <span class="text-red-500">*</span>
          </label>
          <input
            id="empreendedor_nome"
            v-model="form.empreendedor_nome"
            list="empreendedores-list"
            type="text"
            placeholder="Ex: CEMIG GT, Vale S.A."
            class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-800 ring-1 ring-inset ring-slate-300 dark:ring-slate-700 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6 shadow-sm transition-all"
            autocomplete="off"
          />
          <datalist id="empreendedores-list">
            <option v-for="emp in normalizedEmpreendedores" :key="emp.label" :value="emp.label" />
          </datalist>
          <InputError v-if="form.errors.empreendedor_nome" :message="form.errors.empreendedor_nome" class="mt-2" />
        </div>

        <!-- Estrutura -->
        <div>
          <label for="estrutura_nome" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
            Estrutura <span class="text-red-500">*</span>
          </label>
          <input
            id="estrutura_nome"
            v-model="form.estrutura_nome"
            type="text"
            placeholder="Ex: Barragem B1, Mina XYZ"
            class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-800 ring-1 ring-inset ring-slate-300 dark:ring-slate-700 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6 shadow-sm transition-all"
            autocomplete="off"
          />
          <InputError v-if="form.errors.estrutura_nome" :message="form.errors.estrutura_nome" class="mt-2" />
        </div>

        <!-- SEI -->
        <div>
          <label for="sei_numero" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
            Numero SEI <span class="text-slate-400 font-normal text-xs">(opcional)</span>
          </label>
          <input
            id="sei_numero"
            v-model="form.sei_numero"
            type="text"
            placeholder="Ex: 1234.01.0001234/2026-89"
            class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-800 ring-1 ring-inset ring-slate-300 dark:ring-slate-700 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6 shadow-sm transition-all"
            autocomplete="off"
          />
          <InputError v-if="form.errors.sei_numero" :message="form.errors.sei_numero" class="mt-2" />
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
          :disabled="form.processing || !canSubmit"
          @click="submit"
          :icon="PlusIcon"
          icon-position="left"
        >
          <span v-if="form.processing">Criando...</span>
          <span v-else>Criar Protocolo</span>
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { ClipboardDocumentListIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  empreendedores: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close', 'created']);

const normalizedEmpreendedores = computed(() =>
  (props.empreendedores || []).filter((e) => e.value !== '' && e.label)
);

const form = useForm({
  empreendedor_nome: '',
  estrutura_nome: '',
  sei_numero: '',
});

const canSubmit = computed(
  () => form.empreendedor_nome.trim().length > 0 && form.estrutura_nome.trim().length > 0
);

const { show: toast } = useToast();

watch(() => props.show, (isVisible) => {
  if (isVisible) {
    form.reset();
    form.clearErrors();
  }
});

function close() {
  form.reset();
  form.clearErrors();
  emit('close');
}

function submit() {
  if (!canSubmit.value) return;

  form.post(route('pae.protocolos.store'), {
    preserveScroll: true,
    onSuccess: () => {
      toast('Protocolo criado com sucesso!', 'success');
      emit('created');
      close();
    },
    onError: () => {
      toast('Erro ao criar protocolo. Verifique os campos.', 'error');
    },
  });
}
</script>
