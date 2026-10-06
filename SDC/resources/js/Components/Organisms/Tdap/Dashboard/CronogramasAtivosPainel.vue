<template>
  <TdapPainel
    titulo="Cronogramas em andamento"
    :subtitulo="subtitulo"
    :icone="TruckIcon"
    :link-href="podeAbrir ? linkTodos : ''"
  >
    <ListEmptyState
      v-if="cronogramas.length === 0"
      :icon="TruckIcon"
      title="Nenhum cronograma ativo no momento"
      helper="Os cronogramas aparecem aqui quando são ativados."
    />

    <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700/40">
      <li v-for="c in cronogramas" :key="c.id">
        <component
          :is="podeAbrir ? Link : 'div'"
          :href="podeAbrir ? route('tdap.cronogramas.show', c.id) : undefined"
          class="flex items-center gap-4 px-5 py-4"
          :class="{ 'transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/30': podeAbrir }"
        >
          <span class="shrink-0 rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/25">
            {{ c.numero }}
          </span>

          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ c.prestador_nome ?? '—' }}</p>
            <p class="truncate text-xs text-slate-500 dark:text-slate-400">
              {{ c.municipio_nome ?? '—' }}<span v-if="c.municipio_uf">/{{ c.municipio_uf }}</span>
              · {{ diaMesCurto(c.dt_inicio) }} — {{ diaMesCurto(c.dt_final) }}
            </p>
          </div>

          <div class="hidden w-40 shrink-0 sm:block">
            <p class="mb-1 text-xs text-slate-500 dark:text-slate-400">
              {{ c.caminhoes_count }} {{ c.caminhoes_count === 1 ? 'caminhão' : 'caminhões' }}
            </p>
            <CronogramaViagensBar :previstas="c.viagens_previstas" :realizadas="c.viagens_realizadas" :dias-restantes="c.dias_restantes" />
          </div>

          <ChevronRightIcon v-if="podeAbrir" class-name="h-4 w-4 shrink-0 text-slate-400" />
        </component>
      </li>
    </ul>
  </TdapPainel>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import CronogramaViagensBar from '@/Components/Organisms/Tdap/CronogramaViagensBar.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import ChevronRightIcon from '@/Components/Icons/ChevronRightIcon.vue';
import { diaMesCurto } from '@/Support/dataLocal';

const props = defineProps({
  cronogramas: { type: Array, default: () => [] },
  totalAtivos: { type: Number, default: 0 },
  /** Sem tdap.cronogramas.view a linha nao vira link (a ficha daria 403). */
  podeAbrir: { type: Boolean, default: false },
  /** Listagem com o mesmo recorte do painel (estado e municipio de quem olha). */
  linkTodos: { type: String, default: '' },
});

const subtitulo = computed(() => (props.cronogramas.length === 0
  ? ''
  : `${props.cronogramas.length} de ${props.totalAtivos} ativos · início mais recente primeiro`));
</script>
