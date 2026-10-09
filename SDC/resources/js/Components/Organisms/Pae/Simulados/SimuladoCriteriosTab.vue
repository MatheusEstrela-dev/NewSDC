<template>
  <CollapsibleSection namespace="pae" section-id="simulado-criterios" title="Critérios do item 8.1" subtitle="O analista decide cada critério; os indícios do sistema apoiam, não reprovam sozinhos. Validado somente com os 8 critérios reprováveis atendidos." :icon="ClipboardDocumentCheckIcon">
    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
      <Button v-if="!somenteLeitura" class="w-full sm:w-auto" variant="outline" size="sm" :loading="atualizando" @click="$emit('atualizar-indicios')">Atualizar indícios</Button>
      <span v-if="erroIndicios" class="text-sm text-red-600 dark:text-red-300">{{ erroIndicios }}</span>
    </div>
    <div class="space-y-4">
      <article v-for="item in catalogo" :key="item.numero" class="grid gap-4 rounded-lg border border-slate-200 p-3 dark:border-slate-700 sm:p-4 lg:grid-cols-2">
        <div class="space-y-3">
          <div>
            <h3 class="font-semibold text-slate-900 dark:text-white">{{ item.numero }}. {{ item.indice }}</h3>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ item.criterio }}</p>
            <Badge v-if="!item.reprovavel" variant="neutral" size="sm" class="mt-2">Informativo (Art. 100): não reprova</Badge>
          </div>
          <RadioGroup v-model="criterios[item.numero].atende" :name="`criterio-${item.numero}`" :options="OPCOES_ATENDE" :disabled="somenteLeitura" :error="erros[`criterios.${item.numero}.atende`]" />
          <FormTextarea v-if="criterios[item.numero].atende === false" v-model="criterios[item.numero].justificativa" :label="item.reprovavel ? 'Justificativa (obrigatória)' : 'Justificativa'" :rows="2" :disabled="somenteLeitura" :error="erros[`criterios.${item.numero}.justificativa`]" />
        </div>
        <div>
          <h4 class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Indícios do sistema</h4>
          <p v-if="!(indicios[item.numero] ?? []).length" class="mt-2 text-sm text-slate-500 dark:text-slate-400">Nenhum indício.</p>
          <ul v-else class="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-800 dark:text-amber-300">
            <li v-for="(indicio, posicao) in indicios[item.numero]" :key="posicao">{{ descricaoIndicio(indicio) }}</li>
          </ul>
        </div>
      </article>
    </div>
    <p v-if="erros.criterios" class="mt-3 text-sm text-red-600 dark:text-red-300">{{ erros.criterios }}</p>
  </CollapsibleSection>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import RadioGroup from '@/Components/Molecules/Form/RadioGroup.vue';
import { descricaoIndicio } from '@/utils/paeSimulado';
import { ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline';

defineProps({
  criterios: { type: Object, required: true },
  catalogo: { type: Array, required: true },
  indicios: { type: Object, default: () => ({}) },
  erros: { type: Object, required: true },
  somenteLeitura: { type: Boolean, default: false },
  atualizando: { type: Boolean, default: false },
  erroIndicios: { type: String, default: '' },
});

defineEmits(['atualizar-indicios']);

const OPCOES_ATENDE = [{ value: true, label: 'Atende' }, { value: false, label: 'Não atende' }];
</script>
