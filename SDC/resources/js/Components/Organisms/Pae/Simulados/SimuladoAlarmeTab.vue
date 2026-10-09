<template>
  <CollapsibleSection namespace="pae" section-id="simulado-alarme" title="Sistema de alarme (critério 2)" subtitle="Se o som não foi audível em todos os pontos da ZAS, indicar o morador que informou (nome e localização)." :icon="SpeakerWaveIcon">
    <div class="space-y-4">
      <RadioGroup v-model="alarme.audivel_todos" name="alarme-audivel" label="O som das sirenes foi audível em todos os pontos da ZAS?" :options="OPCOES" :disabled="somenteLeitura" :error="erros['alarme.audivel_todos']" />
      <div v-if="alarme.audivel_todos === false" class="grid gap-4 sm:grid-cols-2">
        <FormField v-model="alarme.morador_nome" label="Morador que informou não ouvir (nome)" maxlength="255" :disabled="somenteLeitura" :error="erros['alarme.morador_nome']" />
        <FormField v-model="alarme.morador_localizacao" label="Localização do morador" maxlength="500" :disabled="somenteLeitura" :error="erros['alarme.morador_localizacao']" />
      </div>
      <ul v-if="(indicios[2] ?? []).length" class="list-disc space-y-1 pl-5 text-sm text-amber-800 dark:text-amber-300">
        <li v-for="(indicio, posicao) in indicios[2]" :key="posicao">{{ descricaoIndicio(indicio) }}</li>
      </ul>
    </div>
  </CollapsibleSection>
</template>

<script setup>
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import RadioGroup from '@/Components/Molecules/Form/RadioGroup.vue';
import { descricaoIndicio } from '@/utils/paeSimulado';
import { SpeakerWaveIcon } from '@heroicons/vue/24/outline';

defineProps({
  alarme: { type: Object, required: true },
  indicios: { type: Object, default: () => ({}) },
  erros: { type: Object, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

const OPCOES = [{ value: true, label: 'Sim, em todos os pontos' }, { value: false, label: 'Não' }];
</script>
