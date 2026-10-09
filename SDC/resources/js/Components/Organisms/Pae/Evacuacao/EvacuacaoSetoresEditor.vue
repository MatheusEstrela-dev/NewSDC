<template>
  <CollapsibleSection namespace="pae" section-id="evacuacao-setores" title="Setores de evacuação" subtitle="Sem calçada, a largura da rua desconta 2,90 m (mão única) ou 5,80 m (mão dupla)." :icon="Squares2X2Icon">
    <div v-if="!somenteLeitura" class="mb-3 flex justify-end">
      <Button variant="outline" size="sm" @click="$emit('adicionar')">Adicionar setor</Button>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr>
            <th class="px-2 py-2">Setor</th><th class="px-2 py-2">Moradores</th><th class="px-2 py-2">Comercial</th>
            <th class="px-2 py-2">Via</th><th class="px-2 py-2">Largura (m)</th><th class="px-2 py-2">Lados</th>
            <th class="px-2 py-2">Distância (m)</th><th class="px-2 py-2">Terreno</th>
            <th class="px-2 py-2">Densidade</th><th class="px-2 py-2">Velocidade</th><th class="px-2 py-2">Tempo</th><th class="px-2 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(setor, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="min-w-[5rem] px-2 py-2"><FormField v-model="setor.id" size="sm" :aria-label="`Identificador do setor ${i + 1}`" :disabled="somenteLeitura" maxlength="10" :error="erros[`setores.${i}.id`]" /></td>
            <td class="min-w-[7rem] px-2 py-2"><FormField v-model="setor.populacao" type="number" step="1" size="sm" :aria-label="`Moradores do setor ${i + 1}`" :disabled="somenteLeitura" :error="erros[`setores.${i}.populacao`]" /></td>
            <td class="px-2 py-2"><ToggleInput v-model="setor.comercial" :aria-label="`Área comercial do setor ${i + 1}`" :class="somenteLeitura ? 'pointer-events-none opacity-60' : ''" /></td>
            <td class="min-w-[9rem] px-2 py-2"><FormSelect v-model="setor.via" size="sm" placeholder="" :options="OPCOES_VIA" :aria-label="`Tipo de via do setor ${i + 1}`" :disabled="somenteLeitura" /></td>
            <td class="min-w-[6rem] px-2 py-2"><FormField v-model="setor.largura" type="number" step="0.01" size="sm" :aria-label="`Largura do setor ${i + 1}`" :disabled="somenteLeitura" :error="erros[`setores.${i}.largura`]" /></td>
            <td class="min-w-[5rem] px-2 py-2">
              <FormSelect v-if="setor.via === 'calcada'" v-model="setor.lados" size="sm" placeholder="" :options="OPCOES_LADOS" :aria-label="`Lados da calçada do setor ${i + 1}`" :disabled="somenteLeitura" :error="erros[`setores.${i}.lados`]" />
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="min-w-[7rem] px-2 py-2"><FormField v-model="setor.distancia" type="number" step="0.01" size="sm" :aria-label="`Distância do setor ${i + 1}`" :disabled="somenteLeitura" :error="erros[`setores.${i}.distancia`]" /></td>
            <td class="min-w-[9rem] px-2 py-2"><FormSelect v-model="setor.terreno" size="sm" placeholder="" :options="OPCOES_TERRENO" :aria-label="`Terreno do setor ${i + 1}`" :disabled="somenteLeitura" /></td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ numero(calculo(setor)?.densidade) }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ numero(calculo(setor)?.velocidade) }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">
              {{ calculo(setor)?.tempo_fmt ?? '—' }}
              <span v-if="SITUACOES_SETOR[calculo(setor)?.situacao]" class="block text-xs text-red-700 dark:text-red-300">{{ SITUACOES_SETOR[calculo(setor).situacao] }}</span>
            </td>
            <td class="px-2 py-2"><Button v-if="!somenteLeitura && itens.length > 1" variant="danger" size="sm" @click="$emit('remover', i)">Remover</Button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.setores" />
  </CollapsibleSection>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ToggleInput from '@/Components/Atoms/Input/ToggleInput.vue';
import InputError from '@/Components/InputError.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import { SITUACOES_SETOR, numero } from '@/utils/paeEvacuacao';
import { Squares2X2Icon } from '@heroicons/vue/24/outline';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const OPCOES_VIA = [{ value: 'calcada', label: 'Calçada' }, { value: 'rua_mao_unica', label: 'Rua mão única' }, { value: 'rua_mao_dupla', label: 'Rua mão dupla' }];
const OPCOES_LADOS = [{ value: 1, label: '1' }, { value: 2, label: '2' }];
const OPCOES_TERRENO = [{ value: 'plano', label: 'Plano' }, { value: 'inclinado', label: 'Inclinado (> 5%)' }];

const calculo = (setor) => props.simulado ? (props.resultado?.setores?.[setor.id] ?? null) : null;
</script>
