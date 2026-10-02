<script setup>
import { computed } from 'vue';

/**
 * Escolha dos pontos de captacao de um cronograma.
 *
 * As opcoes chegam ja filtradas pelo backend (PoliticaPontoCaptacao): com a
 * regra do PMDA ligada, so os pontos ATIVOS do PMDA aprovado do municipio,
 * mais os que o cronograma ja tinha (marcados "Legado"). Este componente nao
 * decide nada sobre elegibilidade, so mostra e seleciona.
 */
const props = defineProps({
  modelValue:  { type: Array, default: () => [] },
  pontos:      { type: Array, default: () => [] },
  disabled:    { type: Boolean, default: false },
  exigePmda:   { type: Boolean, default: true },
  podeVerPmda: { type: Boolean, default: false },
  error:       { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const selecionados = computed(() => new Set(props.modelValue.map(Number)));

function alternar(id) {
  const ids = new Set(selecionados.value);
  ids.has(id) ? ids.delete(id) : ids.add(id);
  // Mantem a ordem da lista, nao a ordem dos cliques.
  emit('update:modelValue', props.pontos.map(p => p.id).filter(pid => ids.has(pid)));
}

function fmtCapacidade(v) {
  const n = Number(v || 0);
  return n > 0 ? `${n.toLocaleString('pt-BR', { maximumFractionDigits: 2 })} m³` : '—';
}
</script>

<template>
  <div>
    <div
      class="overflow-x-auto rounded-lg border dark:border-slate-700/50"
      :class="error ? 'border-2 border-red-500/70' : (modelValue.length ? 'border-2 border-emerald-500/60' : 'border-slate-200')"
    >
      <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-800/40">
          <tr class="text-left text-slate-500 dark:text-slate-400">
            <th class="w-10 px-4 py-2"><span class="sr-only">Selecionar</span></th>
            <th class="px-4 py-2 font-medium">Ponto</th>
            <th class="px-4 py-2 font-medium">Tipo</th>
            <th class="px-4 py-2 text-right font-medium">Capacidade</th>
            <th class="px-4 py-2 font-medium">Origem</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr
            v-for="p in pontos"
            :key="p.id"
            class="text-slate-700 dark:text-slate-300"
            :class="disabled ? 'opacity-60' : 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/30'"
            @click="!disabled && alternar(p.id)"
          >
            <td class="px-4 py-2.5">
              <input
                type="checkbox"
                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                :checked="selecionados.has(p.id)"
                :disabled="disabled"
                :aria-label="`Selecionar ${p.nome}`"
                @click.stop
                @change="alternar(p.id)"
              />
            </td>
            <td class="px-4 py-2.5 font-medium">{{ p.nome }}</td>
            <td class="px-4 py-2.5">{{ p.tipo_nome }}</td>
            <td class="px-4 py-2.5 text-right font-mono">{{ fmtCapacidade(p.capacidade) }}</td>
            <td class="px-4 py-2.5">
              <span
                v-if="p.no_pmda"
                class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
              >
                PMDA {{ p.protocolo || `#${p.pmda_plano_id}` }}
              </span>
              <span
                v-else
                class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"
                :title="exigePmda ? 'Ponto já vinculado antes da exigência do PMDA aprovado' : 'Ponto fora do PMDA aprovado'"
              >
                {{ exigePmda ? 'Legado' : 'Fora do PMDA' }}
              </span>
            </td>
          </tr>
          <tr v-if="pontos.length === 0">
            <td colspan="5" class="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
              <template v-if="disabled">Selecione o município primeiro.</template>
              <template v-else-if="exigePmda">
                Este município não tem PMDA aprovado com ponto de captação ATIVO.
                Os pontos são cadastrados no PMDA do município.
                <a
                  v-if="podeVerPmda"
                  :href="route('pmda.planos.index')"
                  class="font-medium text-blue-600 hover:underline dark:text-blue-400"
                  @click.stop
                >Abrir PMDA</a>
              </template>
              <template v-else>Nenhum ponto de captação cadastrado para este município.</template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
  </div>
</template>
