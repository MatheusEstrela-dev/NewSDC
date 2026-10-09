<template>
  <div class="space-y-4">
    <CollapsibleSection v-for="categoria in CATEGORIAS_TEMPO" :key="categoria.chave" namespace="pae" :section-id="`simulado-tempos-${categoria.chave}`" :title="categoria.titulo" :subtitle="categoria.criterio ? `Alimenta os indícios do critério ${categoria.criterio}.` : 'Informativo: sem critério no item 8.1.'" :icon="ClockIcon">
      <div v-if="!somenteLeitura" class="mb-3 flex justify-end">
        <Button variant="outline" size="sm" @click="$emit('adicionar', categoria.chave)">Adicionar linha</Button>
      </div>
      <p v-if="!tempos[categoria.chave].length" class="text-sm text-slate-500 dark:text-slate-400">Nenhuma linha informada.</p>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
            <tr>
              <th class="px-2 py-2">{{ categoria.nome }}</th>
              <th v-if="categoria.comPopulacao" class="px-2 py-2">População</th>
              <th class="px-2 py-2">Chegada da onda (mm:ss)</th><th class="px-2 py-2">Saída (mm:ss)</th>
              <th class="px-2 py-2">Houve problemas</th><th class="px-2 py-2">Ponto válido</th><th class="px-2 py-2">Estimativa</th>
              <th v-if="categoria.nivel" class="px-2 py-2">Nível indicado</th><th class="px-2 py-2"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(linha, i) in tempos[categoria.chave]" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
              <td class="min-w-[10rem] px-2 py-2"><FormField v-model="linha.nome" size="sm" maxlength="255" :aria-label="`${categoria.nome} ${i + 1}`" :disabled="somenteLeitura" :error="erros[`tempos.${categoria.chave}.${i}.nome`]" /></td>
              <td v-if="categoria.comPopulacao" class="min-w-[6rem] px-2 py-2"><FormField v-model="linha.populacao" type="number" step="1" size="sm" :aria-label="`População de ${categoria.nome} ${i + 1}`" :disabled="somenteLeitura" :error="erros[`tempos.${categoria.chave}.${i}.populacao`]" /></td>
              <td class="min-w-[6rem] px-2 py-2"><FormField v-model="linha.chegada_onda" size="sm" placeholder="15:00" maxlength="6" :aria-label="`Chegada da onda de ${categoria.nome} ${i + 1}`" :disabled="somenteLeitura" :error="erros[`tempos.${categoria.chave}.${i}.chegada_onda`]" /></td>
              <td class="min-w-[6rem] px-2 py-2"><FormField v-model="linha.saida" size="sm" placeholder="12:30" maxlength="6" :aria-label="`Saída de ${categoria.nome} ${i + 1}`" :disabled="somenteLeitura" :error="erros[`tempos.${categoria.chave}.${i}.saida`]" /></td>
              <td class="px-2 py-2"><ToggleInput v-model="linha.houve_problemas" :aria-label="`Houve problemas em ${categoria.nome} ${i + 1}`" :class="somenteLeitura ? 'pointer-events-none opacity-60' : ''" /></td>
              <td class="px-2 py-2"><ToggleInput v-model="linha.ponto_valido" :aria-label="`Ponto de encontro válido em ${categoria.nome} ${i + 1}`" :class="somenteLeitura ? 'pointer-events-none opacity-60' : ''" /></td>
              <td class="px-2 py-2"><ToggleInput v-model="linha.estimativa" :aria-label="`Estimativa em ${categoria.nome} ${i + 1}`" :class="somenteLeitura ? 'pointer-events-none opacity-60' : ''" /></td>
              <td v-if="categoria.nivel" class="min-w-[6rem] px-2 py-2"><FormSelect v-model="linha.nivel_emergencia" size="sm" placeholder="" :options="OPCOES_NIVEL_LINHA" :aria-label="`Nível indicado de ${categoria.nome} ${i + 1}`" :disabled="somenteLeitura" :error="erros[`tempos.${categoria.chave}.${i}.nivel_emergencia`]" /></td>
              <td class="px-2 py-2"><Button v-if="!somenteLeitura" variant="danger" size="sm" @click="$emit('remover', categoria.chave, i)">Remover</Button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="erros[`tempos.${categoria.chave}`]" class="mt-2 text-sm text-red-600 dark:text-red-300">{{ erros[`tempos.${categoria.chave}`] }}</p>
    </CollapsibleSection>
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ToggleInput from '@/Components/Atoms/Input/ToggleInput.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import { CATEGORIAS_TEMPO } from '@/utils/paeSimulado';
import { ClockIcon } from '@heroicons/vue/24/outline';

defineProps({
  tempos: { type: Object, required: true },
  erros: { type: Object, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const OPCOES_NIVEL_LINHA = [{ value: 2, label: '2' }, { value: 3, label: '3' }];
</script>
