<template>
  <CollapsibleSection namespace="pae" section-id="evacuacao-pontos" title="Pontos de encontro (Critério 1)" subtitle="Atende quando a população estimada por m² é menor que 3." :icon="MapPinIcon">
    <div v-if="!somenteLeitura" class="mb-3 flex justify-end">
      <Button variant="outline" size="sm" @click="$emit('adicionar')">Adicionar ponto</Button>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr><th class="px-2 py-2">Local</th><th class="px-2 py-2">Endereço</th><th class="px-2 py-2">População</th><th class="px-2 py-2">Área (m²)</th><th class="px-2 py-2">Pessoas/m²</th><th class="px-2 py-2">&lt; 3?</th><th class="px-2 py-2"></th></tr>
        </thead>
        <tbody>
          <tr v-for="(ponto, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="min-w-[9rem] px-2 py-2"><FormField v-model="ponto.nome" size="sm" :aria-label="`Local do ponto de encontro ${i + 1}`" :disabled="somenteLeitura" maxlength="255" :error="erros[`pontos_encontro.${i}.nome`]" /></td>
            <td class="min-w-[10rem] px-2 py-2"><FormField v-model="ponto.endereco" size="sm" :aria-label="`Endereço do ponto de encontro ${i + 1}`" :disabled="somenteLeitura" maxlength="500" :error="erros[`pontos_encontro.${i}.endereco`]" /></td>
            <td class="min-w-[7rem] px-2 py-2"><FormField v-model="ponto.populacao" type="number" step="1" size="sm" :aria-label="`População do ponto de encontro ${i + 1}`" :disabled="somenteLeitura" :error="erros[`pontos_encontro.${i}.populacao`]" /></td>
            <td class="min-w-[7rem] px-2 py-2"><FormField v-model="ponto.area" type="number" step="0.01" size="sm" :aria-label="`Área do ponto de encontro ${i + 1}`" :disabled="somenteLeitura" :error="erros[`pontos_encontro.${i}.area`]" /></td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ numero(calculo(i)?.densidade) }}</td>
            <td class="px-2 py-2">
              <span v-if="calculo(i)" :class="calculo(i).conforme ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">{{ calculo(i).conforme ? 'Sim' : 'Não' }}</span>
            </td>
            <td class="px-2 py-2"><Button v-if="!somenteLeitura && itens.length > 1" variant="danger" size="sm" @click="$emit('remover', i)">Remover</Button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.pontos_encontro" />
  </CollapsibleSection>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import InputError from '@/Components/InputError.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import { numero } from '@/utils/paeEvacuacao';
import { MapPinIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const calculo = (indice) => (props.simulado ? props.resultado?.pontos_encontro?.[indice] ?? null : null);
</script>
