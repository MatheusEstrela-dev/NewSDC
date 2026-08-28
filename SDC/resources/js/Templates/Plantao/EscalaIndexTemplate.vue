<script setup>
/**
 * Escala do plantao: calendario da equipe + os proximos turnos de quem olha.
 *
 * Segue o molde do PlantaoIndexTemplate -- container do modulo, header
 * gradiente, StatCards e FilterSection -- para que a escala nao pareca uma tela
 * de outro sistema.
 *
 * Este arquivo so ORQUESTRA. Titulo, situacao e botoes vivem no
 * EscalaPageHeader; os avisos, no EscalaAvisoBanner. Nao ha HTML de botao nem
 * de pill solto aqui.
 *
 * A tela serve dois publicos de uma vez. O montador precisa das vagas e dos
 * selects; o plantonista comum so quer saber quando trabalha. A diferenca vem
 * pronta do servidor em `can`, e nao por rota separada, para que o link do
 * lembrete leve todo mundo ao mesmo lugar.
 *
 * MOBILE. Abaixo de lg a ordem se inverte: "meus proximos plantoes" sobe para
 * cima do calendario. No telefone a pergunta e sempre "quando eu trabalho?", e
 * a grade da equipe vira contexto secundario -- em `listWeek`, tratado pelo
 * EscalaCalendario.
 */
import EscalaAvisoBanner from '@/Components/Molecules/Plantao/EscalaAvisoBanner.vue';
import MinhasVagasList from '@/Components/Molecules/Plantao/MinhasVagasList.vue';
import EscalaCalendario from '@/Components/Organisms/Plantao/EscalaCalendario.vue';
import EscalaFiltersSection from '@/Components/Organisms/Plantao/EscalaFiltersSection.vue';
import EscalaPageHeader from '@/Components/Organisms/Plantao/Header/EscalaPageHeader.vue';
import EscalaStatsCards from '@/Components/Organisms/Plantao/EscalaStatsCards.vue';
import { computed } from 'vue';

const props = defineProps({
  competencia: {
    type: Object,
    required: true,
  },
  escala: {
    type: Object,
    default: null,
  },
  statistics: {
    type: Object,
    default: () => ({}),
  },
  eventos: {
    type: Array,
    default: () => [],
  },
  minhasVagas: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({ tiposTurno: [], plantonistas: [] }),
  },
  can: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits([
  'criar-escala',
  'publicar',
  'mudar-mes',
  'selecionar-dia',
  'selecionar-vaga',
  'assumir',
  'gerir-plantonistas',
  'filtrar',
  'limpar-filtros',
]);

const podeMontarAgora = computed(
  () => !!props.can?.montar && !!props.escala?.editavel,
);

</script>

<template>
  <div class="plantao-container">
    <EscalaPageHeader
      :competencia="competencia"
      :escala="escala"
      :can="can"
      @criar-escala="emit('criar-escala')"
      @publicar="emit('publicar')"
      @gerir-plantonistas="emit('gerir-plantonistas')"
    />

    <!-- Avisos de situacao da escala -->
    <EscalaAvisoBanner
      v-if="escala && !escala.publicada && can.montar"
      tom="aviso"
    >
      Esta escala está em rascunho: ninguém foi notificado ainda e ela não
      aparece para os plantonistas. Publique quando o mês estiver fechado.
    </EscalaAvisoBanner>

    <EscalaAvisoBanner v-else-if="!escala" tom="neutro">
      Não há escala montada para {{ competencia.rotulo }}.
    </EscalaAvisoBanner>

    <!-- Smart Cards -->
    <EscalaStatsCards
      v-if="escala"
      :statistics="statistics"
      class="mb-6"
      @filtrar-meus="emit('filtrar', { ...filters, somente_meus: '1' })"
    />

    <!-- Filtros -->
    <EscalaFiltersSection
      v-if="escala"
      :filters="filters"
      :filter-options="filterOptions"
      @filter-change="(f) => emit('filtrar', f)"
      @filter-reset="emit('limpar-filtros')"
    />

    <!--
      Ordem invertida no telefone: `order-first` sobe as vagas pessoais acima do
      calendario abaixo de lg, e `lg:order-none` devolve a coluna lateral no
      desktop.
    -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:gap-6">
      <div class="order-first lg:order-none lg:col-span-1">
        <MinhasVagasList
          :vagas="minhasVagas"
          :pode-assumir="true"
          @assumir="(id) => emit('assumir', id)"
        />
      </div>

      <div
        class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-2 sm:p-4 lg:col-span-2 dark:border-slate-700/50 dark:bg-slate-800/60"
      >
        <EscalaCalendario
          :eventos="eventos"
          :data-inicial="competencia.inicio"
          :pode-montar="podeMontarAgora"
          @selecionar-dia="(data) => emit('selecionar-dia', data)"
          @selecionar-vaga="(vaga) => emit('selecionar-vaga', vaga)"
          @mudar-mes="(comp) => emit('mudar-mes', comp)"
        />

        <p
          v-if="podeMontarAgora"
          class="mt-3 px-1 text-xs text-slate-500 dark:text-slate-400"
        >
          Toque num dia para preencher uma vaga, ou num turno já escalado para
          trocar o plantonista.
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.plantao-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
