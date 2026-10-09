<template>
  <CollapsibleSection namespace="pae" section-id="evacuacao-rotas" title="Rotas de fuga (Critério 2)" :subtitle="`Setores na ordem do percurso, separados por vírgula. Disponíveis: ${setoresDisponiveis.join(', ') || 'nenhum'}.`" :icon="MapIcon">
    <div v-if="!somenteLeitura" class="mb-3 flex justify-end">
      <Button variant="outline" size="sm" @click="$emit('adicionar')">Adicionar rota</Button>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr>
            <th class="px-2 py-2">Rota</th><th class="px-2 py-2">Setores</th><th class="px-2 py-2">Chegada da onda (mm:ss)</th>
            <th class="px-2 py-2">Nível</th><th class="px-2 py-2">TERF</th><th class="px-2 py-2">Saída</th><th class="px-2 py-2">Saída &lt; onda?</th><th class="px-2 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(rota, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="min-w-[5rem] px-2 py-2"><FormField v-model="rota.id" size="sm" :aria-label="`Identificador da rota ${i + 1}`" :disabled="somenteLeitura" maxlength="10" :error="erros[`rotas.${i}.id`]" /></td>
            <td class="min-w-[9rem] px-2 py-2"><FormField v-model="rota.setores_texto" size="sm" placeholder="A, D, E" :aria-label="`Setores da rota ${i + 1}`" :disabled="somenteLeitura" :error="erroDaLinha(erros, `rotas.${i}.setores`)" /></td>
            <td class="min-w-[6rem] px-2 py-2"><FormField v-model="rota.chegada_onda" size="sm" placeholder="15:00" maxlength="6" :aria-label="`Chegada da onda da rota ${i + 1}`" :disabled="somenteLeitura" :error="erros[`rotas.${i}.chegada_onda`]" /></td>
            <td class="min-w-[5rem] px-2 py-2"><FormSelect v-model="rota.nivel_emergencia" size="sm" placeholder="" :options="OPCOES_NIVEL" :aria-label="`Nível de emergência da rota ${i + 1}`" :disabled="somenteLeitura" /></td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ calculo(rota)?.terf_fmt ?? '—' }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ calculo(rota)?.saida_fmt ?? '—' }}</td>
            <td class="px-2 py-2">
              <span v-if="calculo(rota)" :class="calculo(rota).conforme ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">{{ calculo(rota).conforme ? 'Sim' : 'Não' }}</span>
              <span v-if="calculo(rota)?.motivo" class="block text-xs text-red-700 dark:text-red-300">{{ MOTIVOS_ROTA[calculo(rota).motivo] }}</span>
            </td>
            <td class="px-2 py-2"><Button v-if="!somenteLeitura && itens.length > 1" variant="danger" size="sm" @click="$emit('remover', i)">Remover</Button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.rotas" />
  </CollapsibleSection>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import InputError from '@/Components/InputError.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import { MOTIVOS_ROTA, erroDaLinha } from '@/utils/paeEvacuacao';
import { MapIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
  setoresDisponiveis: { type: Array, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const OPCOES_NIVEL = [{ value: 1, label: '1' }, { value: 2, label: '2' }, { value: 3, label: '3' }];

const calculo = (rota) => props.simulado ? (props.resultado?.rotas?.[rota.id] ?? null) : null;
</script>
