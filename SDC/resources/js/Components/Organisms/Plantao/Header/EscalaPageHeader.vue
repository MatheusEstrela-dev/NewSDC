<script setup>
/**
 * Cabecalho da Escala: titulo, situacao e as acoes do mes.
 *
 * Existe pelo mesmo motivo do RatPageHeader: os botoes do topo sao um bloco com
 * regra propria — quais aparecem depende de permissao E do estado da escala — e
 * essa regra nao pertence ao template da pagina, que ja orquestra calendario,
 * cards e filtros.
 *
 * O template consome isto e nao sabe mais nada sobre quando "Publicar" aparece.
 *
 * MOBILE. A barra de acoes era o ponto que quebrava em tela estreita: tres
 * botoes com rotulo inteiro estouravam a largura do header. Aqui cada rotulo tem
 * a forma curta em `sm:hidden` e a longa em `hidden sm:inline`, e o container
 * usa `flex-wrap` com `w-full sm:w-auto` para os botoes ocuparem a linha inteira
 * no telefone em vez de espremerem.
 */
import Button from '@/Components/Atoms/Button/Button.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import EscalaStatusBadge from '@/Components/Molecules/Plantao/EscalaStatusBadge.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
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
  can: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['criar-escala', 'publicar', 'gerir-plantonistas']);

const podeAbrirMes = computed(() => !props.escala && !!props.can?.criar);

const podePublicar = computed(
  () => !!props.escala && !props.escala.publicada && !!props.can?.publicar,
);
</script>

<template>
  <PageHeader
    title="Escala de Plantão"
    :description="`Planejamento de turnos — ${competencia.rotulo}`"
    :icon="CalendarIcon"
    :icon-image="moduleIcon('plantao')"
    variant="gradient"
    icon-class="text-blue-600 dark:text-blue-400"
  >
    <template #actions>
      <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:gap-3">
        <EscalaStatusBadge
          v-if="escala"
          :status="escala.status_valor"
          :label="escala.status_label"
        />

        <Button
          v-if="can.gerir_plantonistas"
          variant="secondary"
          size="md"
          :icon="UsersIcon"
          icon-position="left"
          @click="emit('gerir-plantonistas')"
        >
          <span class="hidden sm:inline">Plantonistas</span>
          <span class="sm:hidden">Pessoal</span>
        </Button>

        <Button
          v-if="podeAbrirMes"
          variant="primary"
          size="md"
          :icon="PlusIcon"
          icon-position="left"
          @click="emit('criar-escala')"
        >
          <span class="hidden sm:inline">Abrir {{ competencia.rotulo }}</span>
          <span class="sm:hidden">Abrir mês</span>
        </Button>

        <Button
          v-if="podePublicar"
          variant="success"
          size="md"
          :icon="CheckCircleIcon"
          icon-position="left"
          @click="emit('publicar')"
        >
          <span class="hidden sm:inline">Publicar escala</span>
          <span class="sm:hidden">Publicar</span>
        </Button>
      </div>
    </template>
  </PageHeader>
</template>
