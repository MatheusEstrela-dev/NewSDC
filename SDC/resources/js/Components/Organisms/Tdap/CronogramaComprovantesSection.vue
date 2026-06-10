<template>
  <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">
        Comprovantes ({{ comprovantes.length }})
      </h3>
    </div>

    <!-- Lista -->
    <ul v-if="comprovantes.length" class="divide-y divide-slate-200 dark:divide-slate-700/40 mb-4">
      <li v-for="cp in comprovantes" :key="cp.id" class="py-2 flex items-center justify-between gap-3">
        <div class="min-w-0">
          <a :href="cp.download_url" class="text-sm text-blue-600 hover:text-blue-800 font-medium truncate block">
            {{ cp.nome_original }}
          </a>
          <p class="text-xs text-slate-400">
            {{ cp.tamanho_formatado }}<span v-if="cp.descricao"> — {{ cp.descricao }}</span>
          </p>
        </div>
        <button
          v-if="canEdit"
          type="button"
          @click="remover(cp)"
          class="text-xs text-red-600 hover:text-red-800 flex-shrink-0"
        >
          Remover
        </button>
      </li>
    </ul>
    <p v-else class="text-sm text-slate-400 mb-4">Nenhum comprovante anexado.</p>

    <!-- Upload -->
    <form v-if="canEdit" @submit.prevent="enviar" class="space-y-3 border-t border-slate-200 dark:border-slate-700/40 pt-4">
      <input
        ref="fileInput"
        type="file"
        multiple
        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.odt,.xls,.xlsx"
        @change="onFiles"
        class="block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
      />
      <TextInput v-model="form.descricao" type="text" maxlength="255" placeholder="Descrição (opcional)" class="block w-full" />
      <InputError :message="form.errors.comprovantes || form.errors['comprovantes.0'] || form.errors.descricao" />
      <div class="flex justify-end">
        <PrimaryButton type="submit" :disabled="form.processing || form.comprovantes.length === 0">
          {{ form.processing ? 'Enviando...' : 'Anexar comprovante(s)' }}
        </PrimaryButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
  cronogramaId: { type: [Number, String], required: true },
  comprovantes: { type: Array, default: () => [] },
  canEdit:      { type: Boolean, default: false },
});

const fileInput = ref(null);
const form = useForm({
  comprovantes: [],
  descricao: '',
});

function onFiles(e) {
  form.comprovantes = Array.from(e.target.files || []);
}

function enviar() {
  form.post(route('tdap.cronogramas.comprovantes.store', props.cronogramaId), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      if (fileInput.value) fileInput.value.value = '';
    },
  });
}

function remover(cp) {
  if (!confirm(`Remover o comprovante "${cp.nome_original}"?`)) return;
  router.delete(route('tdap.cronogramas.comprovantes.destroy', [props.cronogramaId, cp.id]), {
    preserveScroll: true,
  });
}
</script>
