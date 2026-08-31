<script setup>
/**
 * Unico ponto do sistema que conhece o FullCalendar.
 *
 * Tudo mais fala com este componente por props e eventos, entao trocar a
 * biblioteca de calendario um dia significa reescrever este arquivo e nenhum
 * outro.
 *
 * RESPONSIVIDADE em tres degraus, todos CLICAVEIS:
 *
 *   >= 1024px  dayGridMonth  mes inteiro, sete colunas com folga
 *   768-1023   dayGridWeek   sete colunas de ~110px, nome ainda legivel
 *   < 768px    timeGridDay   um dia por vez, com as horas na vertical
 *
 * O que estava aqui antes era `listWeek` abaixo de lg, e isso QUEBRAVA o
 * lancamento: a visao de lista do FullCalendar nao dispara `dateClick`, entao
 * no celular o montador nao conseguia tocar num dia para preencher vaga. Era
 * uma tela de leitura se passando por tela de trabalho.
 *
 * `timeGridDay` no telefone e nao `dayGridWeek`: em 375px sete colunas dao
 * ~53px cada, onde nao cabe nem a hora. Um dia por vez usa a largura toda, e a
 * grade de horas mostra visualmente que 06h-16h e 16h-02h se encostam -- que e
 * a leitura que o plantonista faz.
 *
 * O corte de cima e `lg`, o MESMO da sidebar, para nao existir faixa em que
 * sidebar e calendario discordem sobre o que e "tela pequena".
 *
 * A decisao segue o `useMobile`, que le matchMedia e nao innerWidth: e a MESMA
 * medida das media queries do Tailwind, entao o componente nunca discorda do
 * CSS ao redor.
 */
import { useMobile } from '@/Composables/useMobile';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
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
const { isMobile, isDesktop } = useMobile();

const viewAlvo = computed(() => {
  if (isMobile.value) return 'timeGridDay';
  if (!isDesktop.value) return 'dayGridWeek';
  return 'dayGridMonth';
});

// Telefone e tablet: sem "hoje" na barra, que nao caberia junto do titulo.
const telaEstreita = computed(() => !isDesktop.value);

/**
 * Troca a visao quando o dispositivo cruza o breakpoint -- girar o telefone,
 * redimensionar a janela. `initialView` so vale na montagem, entao sem este
 * watch a grade mensal ficaria presa numa tela estreita.
 */
watch(viewAlvo, (alvo) => {
  const api = calendarRef.value?.getApi();
  if (!api) return;

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

  // `dateStr` vem como data pura no dayGrid ("2026-08-28") mas com hora e fuso
  // no timeGrid ("2026-08-28T06:00:00-03:00"). O modal alimenta um input
  // type=date, que rejeita a segunda forma em silencio -- o campo abriria
  // vazio. O recorte serve as duas.
  emit('selecionar-dia', info.dateStr.slice(0, 10));
};

const opcoes = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  locale: ptBrLocale,
  initialView: viewAlvo.value,
  initialDate: props.dataInicial,
  events: props.eventos,
  headerToolbar: {
    left: 'prev,next',
    center: 'title',
    right: telaEstreita.value ? '' : 'today',
  },
  // Altura fixa quebra em telefone: o conteudo da lista semanal varia muito.
  height: 'auto',
  // Sem isto, um dia com tres turnos estica a celula e desalinha a grade.
  dayMaxEvents: telaEstreita.value ? false : 3,
  moreLinkContent: (args) => `+${args.num}`,
  firstDay: 0,
  // Some o cabecalho de horario duplicado: a hora ja vai no titulo do evento.
  displayEventTime: true,
  // Recorte de horas: fora de 05h-23h nao ha turno comecando, e mostrar as 24h
  // obrigaria a rolar para achar o plantao no telefone.
  slotMinTime: '05:00:00',
  slotMaxTime: '23:00:00',
  slotDuration: '01:00:00',
  allDaySlot: false,
  expandRows: true,
  nowIndicator: true,
  eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
  noEventsContent: 'Nenhum plantao escalado neste periodo.',
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
