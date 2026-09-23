<template>
  <article class="flex flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">{{ nomeModuloRanking(regra.modulo) }}</span>
      <div class="flex flex-wrap gap-1">
        <Badge v-if="regraDemonstracao(regra)" cor="violet" size="sm">Demonstração</Badge>
        <Badge :cor="regra.habilitada ? 'emerald' : 'slate'" size="sm">{{ regra.habilitada ? 'Habilitada' : 'Desabilitada' }}</Badge>
      </div>
    </div>
    <h3 class="mt-3 break-words text-base font-bold text-slate-900 dark:text-slate-100">{{ tituloRegraRanking(regra) }}</h3>
    <div class="my-4 flex flex-wrap items-end justify-between gap-3">
      <p class="text-slate-900 dark:text-white">
        <span class="text-3xl font-extrabold tabular-nums">{{ numero(regra.pontos_base) }}</span>
        <span class="ml-1 text-xs text-slate-500 dark:text-slate-400">pontos de base</span>
      </p>
      <Badge :cor="regra.aceita_bonus ? 'amber' : 'slate'" size="sm">
        {{ regra.aceita_bonus ? `Bônus de ${numero(regra.bonus_percentual)}%` : 'Sem bônus' }}
      </Badge>
    </div>
    <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ disponibilidadeRegraRanking(regra) }}</p>
    <details class="mt-4 border-t border-slate-200 pt-3 dark:border-slate-700">
      <summary class="cursor-pointer rounded text-xs font-semibold text-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-500 dark:text-blue-300">Ver condições</summary>
      <div class="mt-3 space-y-3 text-xs leading-relaxed text-slate-600 dark:text-slate-300">
        <p>{{ regra.aceita_bonus ? `O bônus de ${numero(regra.bonus_percentual)}% só se aplica quando a entrega atende aos critérios de elegibilidade da regra. Não é automático.` : 'Esta regra não prevê bônus. O valor de base é a referência para a pontuação da entrega.' }}</p>
        <dl class="grid grid-cols-2 gap-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-900/60">
          <div><dt class="text-slate-500 dark:text-slate-400">Versão</dt><dd class="font-semibold">{{ regra.versao ?? '—' }}</dd></div>
          <div><dt class="text-slate-500 dark:text-slate-400">Vigência</dt><dd class="font-semibold">{{ data(regra.vigente_de) }}<template v-if="regra.vigente_ate"> até {{ data(regra.vigente_ate) }}</template><template v-else> · sem término definido</template></dd></div>
        </dl>
      </div>
    </details>
  </article>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import { nomeModuloRanking, tituloRegraRanking, regraDemonstracao, disponibilidadeRegraRanking } from '@/Support/rankingRegras';

defineProps({ regra: { type: Object, required: true } });
const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));
const data = (valor) => {
  const partes = String(valor ?? '').match(/^(\d{4})-(\d{2})-(\d{2})/);
  return partes ? `${partes[3]}/${partes[2]}/${partes[1]}` : 'Não informada';
};
</script>
