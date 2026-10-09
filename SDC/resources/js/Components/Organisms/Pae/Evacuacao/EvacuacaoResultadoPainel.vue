<template>
  <CollapsibleSection namespace="pae" section-id="evacuacao-resultado" title="Resultado" subtitle="TTE = maior valor entre o tempo máximo de deslocamento e o estrangulamento." :icon="ChartBarIcon" :tom="conforme || !resultado ? 'info' : 'danger'">
    <p v-if="!resultado" class="text-sm text-slate-500 dark:text-slate-400">Preencha os dados e clique em Simular.</p>
    <template v-else>
      <dl class="grid gap-3 sm:grid-cols-3">
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
      <PaeAviso v-if="!simulado" tom="aviso" class="mt-3">Os dados mudaram desde a última simulação.</PaeAviso>
    </template>
    <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">A conferência apenas sinaliza: não bloqueia a tramitação nem a emissão do CCPAE.</p>
  </CollapsibleSection>
</template>

<script setup>
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import { ChartBarIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
});

const okClasse = 'text-green-700 dark:text-green-300';
const erroClasse = 'text-red-700 dark:text-red-300';

// A regra de conformidade e do servidor (CalculoEvacuacaoAnexoE::conforme); aqui so se exibe.
const conforme = computed(() => Boolean(props.resultado?.conforme));

const tempos = computed(() => [
  { rotulo: 'TTE', valor: props.resultado?.tte_fmt },
  { rotulo: 'TMD', valor: props.resultado?.tmd_fmt },
  { rotulo: 'TE', valor: props.resultado?.te_fmt },
]);
</script>
