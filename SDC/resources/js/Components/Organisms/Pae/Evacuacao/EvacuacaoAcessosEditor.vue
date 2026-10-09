<template>
  <CollapsibleSection namespace="pae" section-id="evacuacao-acessos" title="Acessos à área segura (estrangulamento)" :subtitle="`Abaixo de 1,2 m a rota não pode ser usada (art. 48, §6º). Rotas: ${rotasDisponiveis.join(', ') || 'nenhuma'}.`" :icon="ArrowsPointingInIcon">
    <div v-if="!somenteLeitura" class="mb-3 flex justify-end">
      <Button variant="outline" size="sm" @click="$emit('adicionar')">Adicionar acesso</Button>
    </div>
    <p v-if="!itens.length" class="text-sm text-slate-500 dark:text-slate-400">Sem acesso informado, o tempo total é o tempo máximo de deslocamento.</p>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr><th class="px-2 py-2">Acesso</th><th class="px-2 py-2">Largura (m)</th><th class="px-2 py-2">Terreno</th><th class="px-2 py-2">Rotas</th><th class="px-2 py-2">Pessoas</th><th class="px-2 py-2">TE</th><th class="px-2 py-2"></th></tr>
        </thead>
        <tbody>
          <tr v-for="(acesso, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="min-w-[5rem] px-2 py-2"><FormField v-model="acesso.id" size="sm" :aria-label="`Identificador do acesso ${i + 1}`" :disabled="somenteLeitura" maxlength="10" :error="erros[`acessos.${i}.id`]" /></td>
            <td class="min-w-[6rem] px-2 py-2"><FormField v-model="acesso.largura" type="number" step="0.01" size="sm" :aria-label="`Largura do acesso ${i + 1}`" :disabled="somenteLeitura" :error="erros[`acessos.${i}.largura`]" /></td>
            <td class="min-w-[10rem] px-2 py-2"><FormSelect v-model="acesso.terreno" size="sm" placeholder="" :options="OPCOES_TERRENO" :aria-label="`Terreno do acesso ${i + 1}`" :disabled="somenteLeitura" /></td>
            <td class="min-w-[8rem] px-2 py-2"><FormField v-model="acesso.rotas_texto" size="sm" placeholder="R1, R2" :aria-label="`Rotas do acesso ${i + 1}`" :disabled="somenteLeitura" :error="erroDaLinha(erros, `acessos.${i}.rotas`)" /></td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ calculo(acesso)?.n ?? '—' }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">
              {{ calculo(acesso)?.te_fmt ?? '—' }}
              <span v-if="calculo(acesso)?.invalido" class="block text-xs text-red-700 dark:text-red-300">abaixo de 1,2 m</span>
            </td>
            <td class="px-2 py-2"><Button v-if="!somenteLeitura" variant="danger" size="sm" @click="$emit('remover', i)">Remover</Button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.acessos" />
  </CollapsibleSection>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import InputError from '@/Components/InputError.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import { erroDaLinha, idAparado } from '@/utils/paeEvacuacao';
import { ArrowsPointingInIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
  rotasDisponiveis: { type: Array, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const OPCOES_TERRENO = [{ value: 'plano', label: 'Plano' }, { value: 'inclinado', label: 'Rampa ou escada' }];

const calculo = (acesso) => props.simulado ? (props.resultado?.acessos?.[idAparado(acesso.id)] ?? null) : null;
</script>
