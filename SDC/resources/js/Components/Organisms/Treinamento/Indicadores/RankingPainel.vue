<template>
  <TdapPainel :rotulo="rotulo" :titulo="titulo" :subtitulo="subtitulo">
    <ListEmptyState
      v-if="linhas.length === 0"
      :icon="icone"
      :title="vazioTitulo"
      :helper="vazioAjuda"
    />
    <ol v-else class="divide-y divide-slate-200 dark:divide-slate-700/40">
      <li v-for="(linha, indice) in linhas" :key="linha.chave" class="px-5 py-2.5">
        <div class="flex items-center gap-3">
          <span class="w-6 shrink-0 text-right text-xs font-semibold tabular-nums text-slate-400 dark:text-slate-500">{{ indice + 1 }}</span>
          <div class="min-w-0 flex-1">
            <div class="flex items-baseline justify-between gap-3">
              <p class="min-w-0 truncate text-sm font-medium text-slate-800 dark:text-slate-100" :title="linha.nome">{{ linha.nome }}</p>
              <p class="shrink-0 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ formatarNumero(linha.participantes) }}</p>
            </div>
            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
              <div class="h-full rounded-full" :class="corBarra" :style="{ width: `${largura(linha.participantes)}%` }"></div>
            </div>
            <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ linha.detalhe }}</p>
          </div>
        </div>
      </li>
    </ol>
  </TdapPainel>
</template>

<script setup>
/**
 * Lista ranqueada com barra proporcional ao maior valor. Serve ao ranking de
 * municipios e ao de REDECs: cada um monta suas `linhas` e este componente so
 * desenha, sem saber de onde o dado veio.
 */
import { computed } from 'vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import { formatarNumero } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  titulo: { type: String, required: true },
  rotulo: { type: String, default: '' },
  subtitulo: { type: String, default: '' },
  icone: { type: [Object, Function], default: null },
  /** [{ chave, nome, detalhe, participantes }] ja em ordem de ranking. */
  linhas: { type: Array, default: () => [] },
  corBarra: { type: String, default: 'bg-blue-500' },
  vazioTitulo: { type: String, default: 'Nada para ranquear' },
  vazioAjuda: { type: String, default: 'Ajuste os filtros para ver o ranking.' },
});

const maximo = computed(() => Math.max(1, ...props.linhas.map((l) => l.participantes)));

const largura = (valor) => Math.max(2, Math.round((valor / maximo.value) * 100));
</script>
