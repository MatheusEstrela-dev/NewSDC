<script setup>
import { computed } from 'vue';
import { ehCapacitacaoLivre } from '@/Support/categoriaOferta';

/*
 * Opcao "Aprovar inscricoes automaticamente" do cadastro/edicao da capacitacao.
 * Capacitacao livre aprova sempre na hora (Treinamento::aprovaInscricaoNaHora):
 * nela a opcao da lugar a um aviso; so tutoria, presencial e eventos escolhem.
 */
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  tipo: { type: String, default: null },
  categoria: { type: String, default: null },
  comTutoria: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const livre = computed(() => ehCapacitacaoLivre(props));
</script>

<template>
  <p v-if="livre" class="text-sm text-slate-600 dark:text-slate-400">
    Capacitação livre: a inscrição é aprovada na hora, sem passar por um administrador.
  </p>
  <label v-else class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-300">
    <input
      type="checkbox"
      :checked="modelValue"
      class="mt-0.5 rounded border-slate-300 dark:border-slate-600"
      @change="emit('update:modelValue', $event.target.checked)"
    />
    <span>
      Aprovar inscrições automaticamente
      <span class="block text-xs text-slate-500 dark:text-slate-400">
        Sem isso, cada inscrição fica pendente até um administrador aprovar.
      </span>
    </span>
  </label>
</template>
