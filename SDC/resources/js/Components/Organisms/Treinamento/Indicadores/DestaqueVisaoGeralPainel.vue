<template>
  <section class="relative isolate overflow-hidden rounded-xl bg-gradient-to-br from-orange-700 via-orange-800 to-[#431407] p-5 text-white shadow-lg ring-1 ring-orange-500/20 dark:from-[#2a1206] dark:via-slate-900 dark:to-slate-950 dark:ring-orange-500/30">
    <!-- Brilho do canto: so decoracao, atras do conteudo. -->
    <div class="pointer-events-none absolute -right-16 -top-16 -z-10 h-48 w-48 rounded-full bg-orange-400/30 blur-3xl dark:bg-orange-500/20" aria-hidden="true" />
    <p class="text-xs font-semibold uppercase tracking-wider text-orange-100 dark:text-orange-400">Visão geral</p>

    <p class="mt-3 text-5xl font-bold leading-none tabular-nums">{{ formatarNumero(kpis.total_participantes) }}</p>
    <p class="mt-1 text-sm text-orange-100 dark:text-orange-300/90">capacitados</p>

    <dl class="mt-5 grid grid-cols-3 gap-2 border-t border-white/20 pt-4 dark:border-orange-500/20">
      <div v-for="item in subindicadores" :key="item.rotulo" class="min-w-0">
        <dd class="text-xl font-semibold tabular-nums">{{ formatarNumero(item.valor) }}</dd>
        <dt class="text-[11px] leading-tight text-orange-100 dark:text-slate-400">{{ item.rotulo }}</dt>
      </div>
    </dl>

    <div class="mt-5">
      <SeletorCompacto
        rotulo="Capacitação"
        sobre-destaque
        :model-value="valorSelecionado"
        :opcoes="opcoesCapacitacao"
        @update:model-value="$emit('selecionar', $event)"
      />
    </div>
  </section>
</template>

<script setup>
/**
 * Card de destaque: o numero que resume o painel (props.kpis) e o seletor de
 * capacitacao, que e o filtro treinamento_id. Laranja da Defesa Civil: no claro,
 * gradiente a partir do orange-700 (texto branco passa AA; o orange-600 nao); no
 * escuro, base quase preta com brilho e borda laranja.
 */
import { computed } from 'vue';
import SeletorCompacto from '@/Components/Organisms/Treinamento/Indicadores/SeletorCompacto.vue';
import { formatarNumero } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  kpis: { type: Object, required: true },
  filtros: { type: Object, required: true },
  opcoes: { type: Object, required: true },
});

defineEmits(['selecionar']);

const subindicadores = computed(() => [
  { rotulo: 'capacitações', valor: props.kpis.total_capacitacoes },
  { rotulo: 'municípios', valor: props.kpis.municipios_atendidos },
  { rotulo: 'REDECs', valor: props.kpis.redecs_atendidas },
]);

const valorSelecionado = computed(() => (props.filtros.treinamento_id ? String(props.filtros.treinamento_id) : ''));

const opcoesCapacitacao = computed(() => [
  { value: '', label: 'Todas as capacitações' },
  ...(props.opcoes.treinamentos ?? []).map((t) => ({ value: String(t.id), label: t.titulo })),
]);
</script>
