<script setup>
/**
 * Unico ponto do sistema que conhece o FullCalendar.
 *
 * Tudo mais fala com este componente por props e eventos, entao trocar a
 * biblioteca de calendario um dia significa reescrever este arquivo e nenhum
 * outro.
 *
 * RESPONSIVIDADE. A grade mensal so e legivel a partir de ~1000px: sao sete
 * colunas, e cada uma precisa caber "Sgt Fulano" mais o horario. Abaixo disso a
 * visao vira `listWeek`, uma lista vertical da semana -- que e a forma como o
 * plantonista realmente consulta a escala.
 *
 * O corte e em `lg` (1024px), o MESMO da sidebar, e nao em `md`: entre 768 e
 * 1023px a grade ainda cabe na tela mas fica espremida a ponto de o nome nao
 * caber na celula, que era o sintoma. Um corte so para o layout inteiro tambem
 * evita a faixa em que sidebar e calendario discordam sobre o que e "tela
 * pequena".
 *
 * A decisao segue o `useMobile`, que le matchMedia e nao innerWidth: e a MESMA
 * medida das media queries do Tailwind, entao o componente nunca discorda do
 * CSS ao redor.
 */
import { useMobile } from '@/Composables/useMobile';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import ptBrLocale from '@fullcalendar/core/locales/pt-br';
import FullCalendar from '@fullcalendar/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  eventos: {
    type: Array,
    default: () => [],
  },
  // yyyy-mm-dd do primeiro dia do mes exibido.
  dataInicial: {
    type: String,
    required: true,
  },
  // Habilita clique em dia vazio para preencher vaga.
  podeMontar: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['selecionar-dia', 'selecionar-vaga', 'mudar-mes']);

const calendarRef = ref(null);
const { isDesktop } = useMobile();

// Lista semanal em tudo que nao for desktop: mobile E tablet.
const usaLista = computed(() => !isDesktop.value);

const viewInicial = computed(() => (usaLista.value ? 'listWeek' : 'dayGridMonth'));

/**
 * Troca a visao quando o dispositivo cruza o breakpoint -- girar o telefone,
 * redimensionar a janela. `initialView` so vale na montagem, entao sem este
 * watch a grade mensal ficaria presa numa tela estreita.
 */
watch(usaLista, (lista) => {
  const api = calendarRef.value?.getApi();
  if (!api) return;

  const alvo = lista ? 'listWeek' : 'dayGridMonth';
  if (api.view.type !== alvo) api.changeView(alvo);
});

/**
 * Navegar pelo calendario precisa recarregar o mes no servidor: as vagas nao
 * ficam todas no cliente. Emite a competencia nova e o pai decide o reload.
 */
const aoMudarIntervalo = (info) => {
  // `view.currentStart` e o primeiro dia do periodo exibido -- na visao de
  // lista semanal isso pode cair no mes anterior, entao a competencia sai da
  // data central, que sempre pertence ao mes que o usuario acha que esta
  // vendo.
  const centro = new Date(
    (info.view.currentStart.getTime() + info.view.currentEnd.getTime()) / 2,
  );

  emit('mudar-mes', { ano: centro.getFullYear(), mes: centro.getMonth() + 1 });
};

const aoClicarEvento = (info) => {
  emit('selecionar-vaga', info.event.extendedProps);
};

const aoClicarDia = (info) => {
  if (!props.podeMontar) return;
  emit('selecionar-dia', info.dateStr);
};

const opcoes = computed(() => ({
  plugins: [dayGridPlugin, listPlugin, interactionPlugin],
  locale: ptBrLocale,
  initialView: viewInicial.value,
  initialDate: props.dataInicial,
  events: props.eventos,
  headerToolbar: {
    left: 'prev,next',
    center: 'title',
    right: usaLista.value ? '' : 'today',
  },
  // Altura fixa quebra em telefone: o conteudo da lista semanal varia muito.
  height: 'auto',
  // Sem isto, um dia com tres turnos estica a celula e desalinha a grade.
  dayMaxEvents: usaLista.value ? false : 3,
  moreLinkContent: (args) => `+${args.num}`,
  firstDay: 0,
  // Some o cabecalho de horario duplicado: a hora ja vai no titulo do evento.
  displayEventTime: !usaLista.value,
  eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
  noEventsContent: 'Nenhum plantao escalado nesta semana.',
  datesSet: aoMudarIntervalo,
  eventClick: aoClicarEvento,
  dateClick: aoClicarDia,
  eventClassNames: (arg) =>
    arg.event.extendedProps?.ehMinha ? ['escala-vaga-minha'] : [],
}));
</script>

<template>
  <div class="escala-calendario">
    <FullCalendar ref="calendarRef" :options="opcoes" />
  </div>
</template>

<style scoped>
/*
 * O FullCalendar v6 injeta o proprio CSS pelo JS e usa variaveis CSS para
 * tematizar. Ajustar por variavel -- em vez de sobrescrever seletores internos
 * -- e o que sobrevive a uma atualizacao menor da biblioteca.
 *
 * As cores sao literais e nao classes Tailwind de proposito: a cor de cada
 * turno vem do banco, e o Tailwind purgaria qualquer classe montada em tempo
 * de execucao.
 */
.escala-calendario :deep(.fc) {
  --fc-border-color: rgb(226 232 240);
  --fc-today-bg-color: rgb(239 246 255);
  --fc-page-bg-color: transparent;
  --fc-neutral-bg-color: rgb(248 250 252);
  font-size: 0.875rem;
}

.escala-calendario :deep(.fc .fc-toolbar-title) {
  font-size: 1.125rem;
  font-weight: 600;
}

/* Barra de ferramentas empilha no telefone em vez de espremer os botoes. */
@media (max-width: 767px) {
  .escala-calendario :deep(.fc .fc-toolbar) {
    flex-direction: column;
    gap: 0.5rem;
  }

  .escala-calendario :deep(.fc .fc-toolbar-title) {
    font-size: 1rem;
  }
}

/* Alvo de toque: 44px e o minimo confortavel em tela sensivel. */
.escala-calendario :deep(.fc .fc-button) {
  min-height: 2.25rem;
  padding: 0.375rem 0.75rem;
}

.escala-calendario :deep(.fc-event) {
  cursor: pointer;
  border-radius: 0.25rem;
  padding: 0.0625rem 0.25rem;
}

/* Destaque das proprias vagas: contorno, nao cor de fundo -- a cor de fundo ja
   carrega a informacao de qual turno e. */
.escala-calendario :deep(.escala-vaga-minha) {
  outline: 2px solid rgb(15 23 42);
  outline-offset: 1px;
  font-weight: 600;
}

.escala-calendario :deep(.fc .fc-daygrid-day.fc-day-today) {
  font-weight: 600;
}
</style>
