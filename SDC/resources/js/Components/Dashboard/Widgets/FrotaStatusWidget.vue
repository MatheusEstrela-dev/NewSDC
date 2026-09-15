<script setup>
/**
 * Situacao da frota do plantao na Visao Geral.
 *
 * Existe porque "tem viatura livre agora?" e a pergunta mais feita sobre a
 * frota, e quem pergunta muitas vezes nao entra no modulo de Plantao. O numero
 * vem do ViaturaService, o mesmo dos cards da tela de Frota -- e nao de uma
 * contagem propria, senao as duas telas discordariam sobre quantos carros
 * estao livres.
 *
 * DISPONIVEL aqui ja desconta as reservadas: viatura com reserva agendada nao
 * pode ser anunciada como livre.
 */
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({ total: 0, disponiveis: 0, reservadas: 0, em_transito: 0, indisponiveis: 0 }),
  },
  // O link para a Frota so aparece para quem tem `plantao.viaturas.view`:
  // mandar o usuario para uma rota que responde 403 e pior que nao oferecer.
  canVerFrota: {
    type: Boolean,
    default: false,
  },
});

// Cores literais no .vue: o Tailwind nao escaneia app/**/*.php, entao classe
// derivada de valor vindo do backend seria purgada do bundle.
const blocos = computed(() => [
  {
    chave: 'disponiveis',
    rotulo: 'Disponiveis',
    valor: props.stats.disponiveis ?? 0,
    classe: 'text-emerald-600 dark:text-emerald-400',
    fundo: 'bg-emerald-50 dark:bg-emerald-900/20',
  },
  {
    chave: 'reservadas',
    rotulo: 'Reservadas',
    valor: props.stats.reservadas ?? 0,
    classe: 'text-amber-600 dark:text-amber-400',
    fundo: 'bg-amber-50 dark:bg-amber-900/20',
  },
  {
    chave: 'em_transito',
    rotulo: 'Em transito',
    valor: props.stats.em_transito ?? 0,
    classe: 'text-sky-600 dark:text-sky-400',
    fundo: 'bg-sky-50 dark:bg-sky-900/20',
  },
  {
    chave: 'indisponiveis',
    rotulo: 'Indisponiveis',
    valor: props.stats.indisponiveis ?? 0,
    classe: 'text-slate-600 dark:text-slate-300',
    fundo: 'bg-slate-100 dark:bg-slate-700/40',
  },
]);

const total = computed(() => props.stats.total ?? 0);
</script>

<template>
  <div class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700/50 dark:bg-slate-800/60">
    <header class="mb-4 flex items-start justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-700/50 dark:text-slate-300">
          <TruckIcon class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Frota de Viaturas</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ total }} viatura(s) ativa(s)</p>
        </div>
      </div>

      <Link
        v-if="canVerFrota"
        :href="route('plantao.viaturas.index')"
        class="shrink-0 text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400"
      >
        Ver frota
      </Link>
    </header>

    <div class="grid grid-cols-2 gap-3">
      <div
        v-for="bloco in blocos"
        :key="bloco.chave"
        class="rounded-xl p-3"
        :class="bloco.fundo"
      >
        <p class="text-2xl font-bold leading-tight" :class="bloco.classe">{{ bloco.valor }}</p>
        <p class="mt-0.5 text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
          {{ bloco.rotulo }}
        </p>
      </div>
    </div>

    <p
      v-if="total === 0"
      class="mt-4 text-xs text-slate-400"
    >
      Nenhuma viatura ativa cadastrada.
    </p>
  </div>
</template>
