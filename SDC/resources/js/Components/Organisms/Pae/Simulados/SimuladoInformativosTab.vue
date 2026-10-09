<template>
  <CollapsibleSection namespace="pae" section-id="simulado-informativos" title="Informativos (Art. 100)" subtitle="Participação, ensino e recursos são registrados, mas nunca reprovam o simulado." :icon="InformationCircleIcon">
    <div class="space-y-5">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <FormField v-model="informativos.participacao.populacao_zas" type="number" step="1" label="População da ZAS" :disabled="somenteLeitura" :error="erros['informativos.participacao.populacao_zas']" />
        <FormField v-model="informativos.participacao.participantes" type="number" step="1" label="Participantes do simulado" :disabled="somenteLeitura" :error="erros['informativos.participacao.participantes']" />
        <FormField v-model="informativos.participacao.cadastrados_pae" type="number" step="1" label="Cadastrados no PAE" :disabled="somenteLeitura" :error="erros['informativos.participacao.cadastrados_pae']" />
      </div>

      <div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <h3 class="font-medium text-slate-900 dark:text-white">Participação em anos anteriores</h3>
          <Button v-if="!somenteLeitura" class="w-full sm:w-auto" variant="outline" size="sm" @click="$emit('adicionar-ano')">Adicionar ano</Button>
        </div>
        <p v-if="!informativos.participacao.anos_anteriores.length" class="mt-2 text-sm text-slate-500 dark:text-slate-400">Nenhum ano informado.</p>
        <div v-for="(item, i) in informativos.participacao.anos_anteriores" :key="i" class="mt-2 grid grid-cols-1 items-start gap-3 sm:grid-cols-[8rem_1fr_auto]">
          <FormField v-model="item.ano" type="number" step="1" size="sm" :aria-label="`Ano anterior ${i + 1}`" :disabled="somenteLeitura" :error="erros[`informativos.participacao.anos_anteriores.${i}.ano`]" />
          <FormField v-model="item.participantes" type="number" step="1" size="sm" :aria-label="`Participantes do ano anterior ${i + 1}`" :disabled="somenteLeitura" :error="erros[`informativos.participacao.anos_anteriores.${i}.participantes`]" />
          <Button v-if="!somenteLeitura" class="w-full sm:w-auto" variant="danger" size="sm" @click="$emit('remover-ano', i)">Remover</Button>
        </div>
      </div>

      <FormTextarea v-model="informativos.ensino_observacoes" label="Observações sobre unidades de ensino (objetivo VII)" :rows="2" :disabled="somenteLeitura" :error="erros['informativos.ensino_observacoes']" />
      <FormTextarea v-model="informativos.recursos_observacoes" label="Observações sobre recursos humanos, materiais e logísticos (objetivo VIII)" :rows="2" :disabled="somenteLeitura" :error="erros['informativos.recursos_observacoes']" />
      <FormSelect v-model="informativos.conclusao_compdec" label="Conclusão declarada pela COMPDEC" :options="OPCOES_CONCLUSAO" placeholder="Não informada" :disabled="somenteLeitura" :error="erros['informativos.conclusao_compdec']" />
    </div>
  </CollapsibleSection>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import { InformationCircleIcon } from '@heroicons/vue/24/outline';

defineProps({
  informativos: { type: Object, required: true },
  erros: { type: Object, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar-ano', 'remover-ano']);

const OPCOES_CONCLUSAO = [{ value: 'sim', label: 'Sim, o exercício atingiu os critérios' }, { value: 'nao', label: 'Não' }];
</script>
