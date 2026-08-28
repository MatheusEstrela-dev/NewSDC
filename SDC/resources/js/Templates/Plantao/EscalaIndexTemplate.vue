<script setup>
/**
 * Escala do plantao: calendario da equipe + os proximos turnos de quem olha.
 *
 * A tela serve dois publicos de uma vez. O montador precisa das vagas e dos
 * selects; o plantonista comum so quer saber quando trabalha. A diferenca vem
 * pronta do servidor em `can`, e nao por rota separada, para que o link do
 * lembrete leve todo mundo ao mesmo lugar.
 *
 * MOBILE. Abaixo de md a ordem se inverte: "meus proximos plantoes" sobe para
 * cima do calendario. No telefone a pergunta e sempre "quando eu trabalho?",
 * e a grade da equipe vira contexto secundario -- em `listWeek`, tratado pelo
 * EscalaCalendario.
 */
import Button from '@/Components/Atoms/Button/Button.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import MinhasVagasList from '@/Components/Molecules/Plantao/MinhasVagasList.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import EscalaCalendario from '@/Components/Organisms/Plantao/EscalaCalendario.vue';
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
  eventos: {
    type: Array,
    default: () => [],
  },
  minhasVagas: {
    type: Array,
    default: () => [],
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
]);

const podeMontarAgora = computed(
  () => !!props.can?.montar && !!props.escala?.editavel,
);

// Cor do status resolvida em classe LITERAL: enum PHP nao gera classe Tailwind,
// porque o Tailwind nao escaneia app/**/*.php.
const classeStatus = computed(() => {
  switch (props.escala?.status_valor) {
    case 'PUBLICADA':
      return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300';
    case 'ARQUIVADA':
      return 'bg-slate-100 text-slate-600 dark:bg-slate-700/50 dark:text-slate-300';
    default:
      return 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300';
  }
});
</script>

<template>
  <div class="space-y-4 p-4 sm:space-y-6 sm:p-6">
    <PageHeader
      title="Escala de plantao"
      :description="competencia.rotulo"
      :icon="CalendarIcon"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <span
            v-if="escala"
            class="rounded-full px-2.5 py-1 text-xs font-semibold"
            :class="classeStatus"
          >
            {{ escala.status_label }}
          </span>

          <Button
            v-if="can.gerir_plantonistas"
            variant="secondary"
            size="sm"
            @click="emit('gerir-plantonistas')"
          >
            <UsersIcon class="h-4 w-4" />
            <span class="ml-1.5 hidden sm:inline">Plantonistas</span>
          </Button>

          <Button
            v-if="!escala && can.criar"
            variant="primary"
            size="sm"
            @click="emit('criar-escala')"
          >
            <PlusIcon class="h-4 w-4" />
            <span class="ml-1.5">Abrir {{ competencia.rotulo }}</span>
          </Button>

          <Button
            v-if="escala && !escala.publicada && can.publicar"
            variant="primary"
            size="sm"
            @click="emit('publicar')"
          >
            Publicar escala
          </Button>
        </div>
      </template>
    </PageHeader>

    <p
      v-if="escala && !escala.publicada && can.montar"
      class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
    >
      Esta escala esta em rascunho: ninguem foi notificado ainda e ela nao
      aparece para os plantonistas. Publique quando o mes estiver fechado.
    </p>

    <p
      v-else-if="!escala"
      class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-700/50 dark:bg-slate-800/50 dark:text-slate-300"
    >
      Nao ha escala montada para {{ competencia.rotulo }}.
    </p>

    <!--
      Ordem invertida no telefone: `order-first` sobe as vagas pessoais acima do
      calendario abaixo de lg, e o `lg:order-none` devolve a coluna lateral no
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
        class="overflow-x-auto rounded-lg border border-slate-200 bg-white p-2 sm:p-4 lg:col-span-2 dark:border-slate-700/50 dark:bg-slate-800/50"
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
          Toque num dia para preencher uma vaga, ou num turno ja escalado para
          trocar o plantonista.
        </p>
      </div>
    </div>
  </div>
</template>
