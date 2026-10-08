<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Resultado</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">TTE = maior valor entre o tempo máximo de deslocamento e o estrangulamento.</p>
      </div>
      <span v-if="resultado" class="rounded-full px-3 py-1 text-sm font-semibold" :class="conforme ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'">
        {{ conforme ? 'Conforme' : 'Não conforme' }}
      </span>
    </div>
    <p v-if="!resultado" class="mt-4 text-sm text-slate-500 dark:text-slate-400">Preencha os dados e clique em Simular.</p>
    <template v-else>
      <dl class="mt-4 grid gap-3 sm:grid-cols-3">
        <div v-for="item in tempos" :key="item.rotulo" class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">
          <dt class="text-xs uppercase text-slate-500 dark:text-slate-400">{{ item.rotulo }}</dt>
          <dd class="text-xl font-bold text-slate-900 dark:text-white">{{ item.valor ?? '—' }}</dd>
        </div>
      </dl>
      <ul class="mt-4 space-y-1 text-sm">
        <li :class="resultado.criterio1_conforme ? okClasse : erroClasse">Critério 1, pontos de encontro (menos de 3 pessoas/m²): {{ resultado.criterio1_conforme ? 'atende' : 'não atende' }}</li>
        <li :class="resultado.criterio2_conforme ? okClasse : erroClasse">Critério 2, rotas de fuga (saída antes da chegada da onda): {{ resultado.criterio2_conforme ? 'atende' : 'não atende' }}</li>
        <li v-if="resultado.possui_rota_invalida" :class="erroClasse">Há rota de fuga inválida.</li>
        <li v-if="resultado.possui_setor_inviavel" :class="erroClasse">Há setor sem tempo calculável.</li>
        <li v-if="resultado.excede_declarado" :class="erroClasse">O tempo calculado excede o declarado pelo empreendedor.</li>
      </ul>
      <p v-if="!simulado" class="mt-3 text-sm text-amber-700 dark:text-amber-300">Os dados mudaram desde a última simulação.</p>
    </template>
    <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">A conferência apenas sinaliza: não bloqueia a tramitação nem a emissão do CCPAE.</p>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
});

const okClasse = 'text-green-700 dark:text-green-300';
const erroClasse = 'text-red-700 dark:text-red-300';

const conforme = computed(() => {
  const r = props.resultado;
  return Boolean(r && r.criterio1_conforme && r.criterio2_conforme && !r.possui_rota_invalida && !r.possui_setor_inviavel && !r.excede_declarado);
});

const tempos = computed(() => [
  { rotulo: 'TTE', valor: props.resultado?.tte_fmt },
  { rotulo: 'TMD', valor: props.resultado?.tmd_fmt },
  { rotulo: 'TE', valor: props.resultado?.te_fmt },
]);
</script>
