<script setup>
import { computed } from 'vue';
import { admiteTutoria } from '@/Support/categoriaOferta';

/*
 * Opcao "Com tutoria" do cadastro/edicao da capacitacao. So aparece para EAD fora
 * da categoria Evento (admiteTutoria). Online e semipresencial ja sao "com
 * tutoria" por natureza, e o backend grava false nos demais casos de qualquer forma.
 */

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  tipo: { type: String, default: null },
  categoria: { type: String, default: null },
  erro: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue']);

const visivel = computed(() => admiteTutoria(props));
</script>

<template>
  <div v-if="visivel">
    <label class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-300">
      <input
        type="checkbox"
        :checked="modelValue"
        class="mt-0.5 rounded border-slate-300 dark:border-slate-600"
        @change="emit('update:modelValue', $event.target.checked)"
      />
      <span>
        Com tutoria (turmas com período e tutor)
        <span class="block text-xs text-slate-500 dark:text-slate-400">
          Aparece em "Capacitações com tutoria". Sem isso, fica em "Capacitações livres", feita direto no ritmo do aluno.
        </span>
      </span>
    </label>
    <p v-if="erro" class="mt-1 text-xs text-red-500">{{ erro }}</p>
  </div>
</template>
