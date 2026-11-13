<template>
  <div v-if="acompanhamento" class="modal-serie-cartao rounded-xl p-4 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div class="min-w-0">
        <p class="text-xs modal-serie-apoio">Situação atual</p>
        <h4 class="text-base font-semibold modal-serie-titulo">{{ acompanhamento.status_label }}</h4>
      </div>
      <div class="flex items-center gap-2">
        <Badge :variant="varianteTom" size="sm">{{ etapaAtual?.label }}</Badge>
        <Badge v-if="acompanhamento.arquivado" variant="neutral" size="sm">Arquivado</Badge>
      </div>
    </div>

    <ol class="grid grid-cols-3 gap-x-2 gap-y-3 sm:grid-cols-6">
      <li v-for="etapa in acompanhamento.etapas" :key="etapa.value" class="flex flex-col items-center gap-1 text-center">
        <span class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-semibold" :class="classeEtapa(etapa)">
          <XMarkIcon v-if="etapa.ordem === acompanhamento.etapa && acompanhamento.tom === 'erro'" class="h-4 w-4" />
          <CheckIcon v-else-if="concluida(etapa)" class="h-4 w-4" />
          <template v-else>{{ etapa.ordem }}</template>
        </span>
        <span class="text-xs font-medium leading-tight modal-serie-titulo">{{ etapa.label }}</span>
        <span class="text-[11px] leading-tight modal-serie-apoio">{{ etapa.responsavel }}</span>
      </li>
    </ol>

    <div class="flex flex-wrap gap-2">
      <div v-if="acompanhamento.responsavel_agora" class="inline-flex items-center gap-2 modal-serie-pilula rounded-full px-3 py-1 text-xs">
        <UsersIcon class="h-4 w-4 modal-serie-apoio" />
        <span class="modal-serie-apoio">Responsável agora:</span>
        <span class="modal-serie-valor">{{ acompanhamento.responsavel_agora }}</span>
      </div>
      <div v-if="prazoAnalise" class="inline-flex items-center gap-2 modal-serie-pilula rounded-full px-3 py-1 text-xs">
        <ClockIcon class="h-4 w-4 modal-serie-apoio" />
        <span class="modal-serie-apoio">Limite da análise:</span>
        <span class="modal-serie-valor">{{ prazoAnalise }}</span>
      </div>
    </div>

    <!-- Ciclo de notificacoes: o que o analista mais consulta para saber se pode agir. -->
    <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700/60">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm font-semibold modal-serie-titulo">
          Ciclo de notificações
          <span class="font-normal modal-serie-apoio">
            ({{ notificacoes.length }} de {{ acompanhamento.max_ciclos_automaticos }} automáticos)
          </span>
        </p>
        <Badge v-if="ciclosEsgotadosEm" variant="danger" size="sm">Ciclos esgotados</Badge>
        <Badge v-else-if="cicloAberto?.vencida" variant="danger" size="sm">Prazo vencido</Badge>
        <Badge v-else-if="cicloAberto" variant="warning" size="sm">Aguardando resposta</Badge>
      </div>

      <ol class="mt-3 flex flex-wrap items-center gap-2">
        <li v-for="ciclo in ciclos" :key="ciclo.numero" class="flex items-center gap-2">
          <span
            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
            :class="classeCiclo(ciclo.estado)"
            :title="ciclo.dica"
          >
            <BellIcon class="h-3.5 w-3.5" />
            {{ ciclo.numero }}º · {{ ROTULO_CICLO[ciclo.estado] }}
          </span>
        </li>
      </ol>

      <p class="mt-2 text-xs modal-serie-apoio">{{ resumoCiclo }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import BellIcon from '@/Components/Icons/BellIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import { formatarData } from '@/utils/paeTela';

const props = defineProps({
  // Leitura do fluxo vinda do backend (AcompanhamentoProtocolo).
  acompanhamento: { type: Object, default: null },
  notificacoes: { type: Array, default: () => [] },
  prazos: { type: Object, default: null },
});

const ROTULO_CICLO = {
  respondido: 'respondido',
  aberto: 'em aberto',
  vencido: 'vencido',
  futuro: 'não emitido',
};

const etapaAtual = computed(() => props.acompanhamento?.etapas.find((e) => e.ordem === props.acompanhamento.etapa));

const varianteTom = computed(() => ({ erro: 'danger', alerta: 'warning' }[props.acompanhamento?.tom] ?? (props.acompanhamento?.concluido ? 'success' : 'info')));

const ciclosEsgotadosEm = computed(() => props.prazos?.ciclos_esgotados_em ?? null);

const prazoAnalise = computed(() => (props.prazos?.limite_analise ? formatarData(props.prazos.limite_analise) : null));

const cicloAberto = computed(() => {
  const ultima = props.notificacoes.at(-1);
  return ultima && !ultima.dt_devolutiva ? ultima : null;
});

// Os ciclos emitidos e, ate o limite automatico, os que ainda podem vir.
const ciclos = computed(() => {
  const emitidos = props.notificacoes.map((n) => ({
    numero: n.ciclo,
    estado: n.dt_devolutiva ? 'respondido' : (n.vencida ? 'vencido' : 'aberto'),
    dica: `SEI ${n.num_sei} · notificado em ${formatarData(n.dt_notificacao)} · prazo ${formatarData(n.prazo_final)}`,
  }));
  const limite = props.acompanhamento?.max_ciclos_automaticos ?? 0;
  const futuros = Array.from({ length: Math.max(0, limite - emitidos.length) }, (_, i) => ({
    numero: emitidos.length + i + 1,
    estado: 'futuro',
    dica: 'Ciclo ainda não emitido',
  }));
  return [...emitidos, ...futuros];
});

const resumoCiclo = computed(() => {
  if (ciclosEsgotadosEm.value) {
    return `Ciclos automáticos esgotados em ${formatarData(ciclosEsgotadosEm.value)}: a próxima notificação é decisão da CEDEC.`;
  }
  if (cicloAberto.value) {
    const prazo = formatarData(cicloAberto.value.prazo_final);
    return cicloAberto.value.vencida
      ? `O ${cicloAberto.value.ciclo}º ciclo venceu em ${prazo} sem resposta do empreendedor.`
      : `O empreendedor tem até ${prazo} para responder o ${cicloAberto.value.ciclo}º ciclo.`;
  }
  if (!props.notificacoes.length) {
    return 'Nenhuma notificação emitida para este protocolo.';
  }
  return 'Sem ciclo em aberto: a última notificação já foi respondida.';
});

function concluida(etapa) {
  const atual = props.acompanhamento.etapa;
  return etapa.ordem < atual || (etapa.ordem === atual && props.acompanhamento.concluido);
}

function classeEtapa(etapa) {
  if (concluida(etapa)) return 'bg-emerald-600 text-white';
  if (etapa.ordem !== props.acompanhamento.etapa) return 'bg-slate-200 text-slate-500 dark:bg-slate-700/60 dark:text-slate-400';

  return {
    erro: 'bg-red-600 text-white ring-4 ring-red-500/20',
    alerta: 'bg-amber-500 text-white ring-4 ring-amber-500/20',
  }[props.acompanhamento.tom] ?? 'bg-blue-600 text-white ring-4 ring-blue-500/20';
}

function classeCiclo(estado) {
  return {
    respondido: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    aberto: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    vencido: 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
    futuro: 'bg-slate-100 text-slate-500 dark:bg-slate-700/50 dark:text-slate-400',
  }[estado];
}
</script>
