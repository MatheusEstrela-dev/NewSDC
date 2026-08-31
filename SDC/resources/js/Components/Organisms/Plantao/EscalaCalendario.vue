<script setup>
/**
 * Unico ponto do sistema que conhece o FullCalendar.
 *
 * Tudo mais fala com este componente por props e eventos, entao trocar a
 * biblioteca de calendario um dia significa reescrever este arquivo e nenhum
 * outro.
 *
 * RESPONSIVIDADE em dois degraus, os dois CLICAVEIS:
 *
 *   >= 1024px  dayGridMonth   mes inteiro, sete colunas com folga
 *   <  1024px  timeGridWeek   semana com as 24h na vertical, rolando
 *
 * A referencia da faixa estreita e a semana do Google Agenda: sete colunas
 * estreitas, o dia inteiro disponivel e ROLAGEM vertical em vez de recorte. As
 * duas tentativas anteriores erraram por motivos diferentes e vale registrar:
 *
 *  - `listWeek` nao dispara `dateClick`. Era tela de leitura se passando por
 *    tela de trabalho: no celular o montador nao conseguia lancar vaga.
 *  - `timeGridDay` com as horas recortadas em 05h-23h resolvia o clique mas
 *    desperdicava a largura toda numa coluna so, e o recorte escondia turno
 *    que atravessa a meia-noite -- 16h-02h e 20h-08h, que sao METADE dos
 *    horarios praticados.
 *
 * Agora sao as 24 horas de fato (`slotMinTime` 00:00, `slotMaxTime` 24:00), com
 * altura fixa e rolagem interna. `scrollTime` abre em 05h para o primeiro turno
 * do dia (06h) aparecer sem rolar, e o resto fica a um gesto de distancia.
 *
 * O corte e `lg`, o MESMO da sidebar, para nao existir faixa em que sidebar e
 * calendario discordem sobre o que e "tela pequena". A decisao segue o
 * `useMobile`, que le matchMedia e nao innerWidth: e a MESMA medida das media
 * queries do Tailwind, entao o componente nunca discorda do CSS ao redor.
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
const { isDesktop } = useMobile();

// Telefone e tablet: semana com horas. Desktop: mes.
const telaEstreita = computed(() => !isDesktop.value);

const viewAlvo = computed(() => (telaEstreita.value ? 'timeGridWeek' : 'dayGridMonth'));

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
  // Altura FIXA na faixa estreita: e o que cria a rolagem interna das 24h.
  // Com 'auto' o calendario cresceria para a altura do dia inteiro e a pagina
  // toda passaria a rolar, empurrando cards e filtros para longe. No desktop o
  // mes tem altura previsivel e 'auto' continua melhor.
  height: telaEstreita.value ? 560 : 'auto',
  // Sem isto, um dia com tres turnos estica a celula e desalinha a grade.
  dayMaxEvents: telaEstreita.value ? false : 3,
  moreLinkContent: (args) => `+${args.num}`,
  firstDay: 0,
  // Some o cabecalho de horario duplicado: a hora ja vai no titulo do evento.
  displayEventTime: true,
  // 24 horas de verdade. Recortar escondia turno que atravessa a meia-noite --
  // 16h-02h e 20h-08h sao metade dos horarios praticados no CEDEC.
  slotMinTime: '00:00:00',
  slotMaxTime: '24:00:00',
  slotDuration: '01:00:00',
  slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
  // Abre em 05h: o primeiro turno comeca as 06h e aparece sem rolar.
  scrollTime: '05:00:00',
  // expandRows FALSE de proposito: com true as linhas se esticam para preencher
  // a altura e a rolagem desaparece, que e o oposto do pedido.
  expandRows: false,
  allDaySlot: false,
  nowIndicator: true,
  // "dom 31" em vez de "domingo, 31 de agosto": sao sete colunas estreitas.
  dayHeaderFormat: telaEstreita.value
    ? { weekday: 'short', day: 'numeric', omitCommas: true }
    : { weekday: 'short' },
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
