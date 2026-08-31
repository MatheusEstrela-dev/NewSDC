<template>
  <div>
    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">
      {{ label }}
      <span v-if="obrigatorio" class="text-red-600">*</span>
    </label>

    <input
      ref="entrada"
      type="file"
      :accept="ACEITOS"
      class="block w-full cursor-pointer rounded-md border border-slate-300 bg-white text-sm text-slate-700 file:mr-3 file:cursor-pointer file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:file:bg-slate-700"
      @change="aoEscolher"
    >

    <!--
      Arquivo ja salvo torna o envio opcional: e substituicao, nao exigencia
      nova. E o que o UpdateBeneficiarioRequest faz do lado do servidor, e a tela
      precisa dizer o mesmo, senao o usuario acha que precisa reenviar tudo.
    -->
    <p v-if="existente && !escolhido" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
      Arquivo atual: {{ existente }}. Envie outro apenas se quiser substituir.
    </p>

    <p v-if="escolhido" class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
      Selecionado: {{ escolhido }}
    </p>

    <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ error }}</p>

    <p v-else class="mt-1 text-xs text-slate-400">PDF, JPG ou PNG, ate 2 MB.</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';

/**
 * Campo de arquivo unico. Nao guarda o File no estado: emite para a pagina, que
 * e quem monta o payload do Inertia -- atomo/molecula nao conhece transporte.
 */
defineProps({
  label: { type: String, required: true },
  obrigatorio: { type: Boolean, default: false },
  /** Nome do arquivo ja salvo, quando houver. */
  existente: { type: String, default: '' },
  error: { type: String, default: '' },
});

const emit = defineEmits(['change']);

const ACEITOS = '.pdf,.jpg,.jpeg,.png';

const entrada = ref(null);
const escolhido = ref('');

function aoEscolher(evento) {
  const arquivo = evento.target.files?.[0] ?? null;

  escolhido.value = arquivo?.name ?? '';
  emit('change', arquivo);
}
</script>
