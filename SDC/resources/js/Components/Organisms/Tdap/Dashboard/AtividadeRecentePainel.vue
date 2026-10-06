<template>
  <TdapPainel
    titulo="Atividade recente"
    subtitulo="Últimas movimentações"
    :icone="ClockIcon"
    :link-href="route('tdap.historicos.index')"
    link-rotulo="Auditoria"
  >
    <ListEmptyState
      v-if="eventos.length === 0"
      :icon="ClockIcon"
      title="Sem eventos registrados ainda"
      helper=""
    />

    <!-- Legenda: a cor e do TIPO de acao, a mesma da tela de Auditoria. -->
    <ul v-if="eventos.length > 0" class="flex flex-wrap gap-x-3 gap-y-1 border-b border-slate-200 px-5 py-2.5 dark:border-slate-700/40" aria-label="Legenda das cores">
      <li v-for="acao in LEGENDA" :key="acao.rotulo" class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
        <span class="h-2 w-2 rounded-full" :class="acao.ponto"></span>
        {{ acao.rotulo }}
      </li>
    </ul>

    <ol v-if="eventos.length > 0" class="px-5 py-4">
      <li v-for="(ev, i) in eventos" :key="ev.id" class="relative flex gap-3 pb-5 last:pb-0">
        <span
          v-if="i < eventos.length - 1"
          class="absolute left-[4px] top-4 h-full w-px bg-slate-200 dark:bg-slate-700/60"
          aria-hidden="true"
        ></span>
        <span class="relative mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ring-4 ring-white dark:ring-slate-900" :class="acaoDoEvento(ev.tipo_evento).ponto"></span>

        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 flex-wrap items-center gap-2">
              <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ rotuloDoEvento(ev.tipo_evento) }}</p>
              <span class="rounded px-1.5 py-0.5 text-xs font-semibold uppercase tracking-wide" :class="acaoDoEvento(ev.tipo_evento).badge">
                {{ acaoDoEvento(ev.tipo_evento).rotulo }}
              </span>
            </div>
            <time :datetime="ev.data_evento" :title="formatDateTime(ev.data_evento)" class="shrink-0 text-xs text-slate-400 dark:text-slate-500">
              {{ tempoRelativo(ev.data_evento) }}
            </time>
          </div>
          <p class="truncate text-xs text-slate-500 dark:text-slate-400" :title="ev.obs">
            {{ ev.obs || '—' }}<span v-if="ev.user_name"> · {{ ev.user_name }}</span>
          </p>
        </div>
      </li>
    </ol>

    <template v-if="viagensPendentes > 0" #rodape>
      <div class="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-500/25 dark:bg-amber-500/10">
        <span class="shrink-0 rounded-lg bg-amber-100 p-2 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
          <ClockIcon class-name="h-4 w-4" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
            {{ viagensPendentes }} {{ viagensPendentes === 1 ? 'viagem aguarda' : 'viagens aguardam' }} validação
          </p>
          <p class="text-xs text-amber-700/80 dark:text-amber-300/70">Revise os registros para manter os dados atualizados.</p>
        </div>
        <Button variant="warning" size="sm" :href="route('tdap.viagens.pendentes')">Revisar</Button>
      </div>
    </template>
  </TdapPainel>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import { formatDateTime } from '@/utils/dateFormatter';
import { tempoRelativo } from '@/Support/dataLocal';
import { ACOES, acaoDoEvento, rotuloDoEvento } from '@/Support/historicoTdap';

defineProps({
  eventos: { type: Array, default: () => [] },
  viagensPendentes: { type: Number, default: 0 },
});

// "Outro" fica fora da legenda: e o que sobra, nao uma acao.
const LEGENDA = Object.entries(ACOES)
  .filter(([chave]) => chave !== 'outro')
  .map(([, acao]) => acao);
</script>
