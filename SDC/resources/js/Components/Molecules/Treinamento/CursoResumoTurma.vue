<script setup>
/**
 * Resumo lateral da turma: situacao das inscricoes, anel de vagas, barra de
 * ocupacao e os numeros principais. A acao (gerenciar ou se inscrever) vem
 * de quem usa, pelo slot #acao.
 */
import { computed } from 'vue';
import { AcademicCapIcon, ClockIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  treinamento: { type: Object, required: true },
});

const RAIO = 26;
const CIRCUNFERENCIA = 2 * Math.PI * RAIO;

const limitado = computed(() => Boolean(props.treinamento.numero_vagas));
const total = computed(() => Number(props.treinamento.numero_vagas) || 0);
const disponiveis = computed(() => Math.max(Number(props.treinamento.vagas_disponiveis) || 0, 0));
const ocupadas = computed(() => Math.max(total.value - disponiveis.value, 0));
const percentualOcupado = computed(() => (limitado.value && total.value > 0
  ? Math.min(Math.round((ocupadas.value / total.value) * 100), 100)
  : 0));

// O anel mostra as vagas ocupadas; ilimitado = anel cheio.
const tracoOcupado = computed(() => (limitado.value ? (percentualOcupado.value / 100) * CIRCUNFERENCIA : CIRCUNFERENCIA));

const inscritos = computed(() => Number(props.treinamento.total_inscricoes) || 0);
const abertas = computed(() => Boolean(props.treinamento.pode_receber_inscricao));
</script>

<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Resumo da turma</p>
    <p class="mt-1 text-base font-semibold" :class="abertas ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'">
      {{ abertas ? 'Inscrições abertas' : 'Inscrições encerradas' }}
    </p>

    <div class="mt-4 flex items-center gap-4">
      <div class="relative h-16 w-16 shrink-0">
        <svg viewBox="0 0 64 64" class="h-16 w-16 -rotate-90" aria-hidden="true">
          <circle cx="32" cy="32" :r="RAIO" fill="none" stroke-width="6" class="stroke-slate-200 dark:stroke-slate-700" />
          <circle
            cx="32"
            cy="32"
            :r="RAIO"
            fill="none"
            stroke-width="6"
            stroke-linecap="round"
            class="stroke-orange-500 transition-all"
            :stroke-dasharray="`${tracoOcupado} ${CIRCUNFERENCIA}`"
          />
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
          <span class="text-lg font-bold leading-none text-slate-900 dark:text-white">{{ limitado ? disponiveis : '∞' }}</span>
          <span class="text-[9px] text-slate-500 dark:text-slate-400">vagas</span>
        </div>
      </div>
      <div class="min-w-0">
        <p class="text-sm font-semibold text-slate-900 dark:text-white">
          {{ limitado ? `${disponiveis} de ${total} disponíveis` : 'Vagas ilimitadas' }}
        </p>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          {{ inscritos }} {{ inscritos === 1 ? 'participante inscrito' : 'participantes inscritos' }}
        </p>
      </div>
    </div>

    <div
      v-if="limitado"
      class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
      role="progressbar"
      :aria-valuenow="percentualOcupado"
      aria-valuemin="0"
      aria-valuemax="100"
      :aria-label="`${percentualOcupado}% das vagas ocupadas`"
    >
      <div class="h-full rounded-full bg-orange-500 transition-all" :style="{ width: `${percentualOcupado}%` }"></div>
    </div>

    <dl class="mt-5 space-y-4 border-t border-slate-200 pt-5 dark:border-slate-700/50">
      <div class="flex items-center gap-3">
        <ClockIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
        <div>
          <dt class="text-xs text-slate-500 dark:text-slate-400">Carga horária</dt>
          <dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ treinamento.carga_horaria }} horas</dd>
        </div>
      </div>
      <div v-if="treinamento.usa_controle_presenca" class="flex items-center gap-3">
        <UserGroupIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
        <div>
          <dt class="text-xs text-slate-500 dark:text-slate-400">Frequência mínima</dt>
          <dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ treinamento.percentual_frequencia_minimo }}%</dd>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <AcademicCapIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
        <div>
          <dt class="text-xs text-slate-500 dark:text-slate-400">Modalidade</dt>
          <dd class="text-sm font-semibold text-slate-900 dark:text-white">{{ treinamento.tipo_label }}</dd>
        </div>
      </div>
    </dl>

    <div v-if="$slots.acao" class="mt-5 border-t border-slate-200 pt-5 dark:border-slate-700/50">
      <slot name="acao" />
    </div>
  </section>
</template>
