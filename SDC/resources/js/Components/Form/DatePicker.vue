<template>
  <div class="relative" ref="el">
    <!--
      Campo de TEXTO, nao botao. Era botao, e por isso digitar data era
      impossivel: so restava navegar o calendario mes a mes, o que para data de
      nascimento significa dezenas de cliques. O calendario continua, no icone.
    -->
    <div
      class="dt-input w-full flex items-center justify-between gap-2"
      :class="[
        error ? 'dt-input-error' : (modelValue ? 'dt-input-filled' : ''),
        disabled ? 'dt-input-disabled' : '',
        extraClass,
      ]"
    >
      <input
        :id="id || undefined"
        type="text"
        inputmode="numeric"
        class="w-full border-0 bg-transparent p-0 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500"
        :value="textoVisivel"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        maxlength="10"
        @input="aoDigitar"
        @blur="aoSair"
      >
      <button
        v-if="showIcon"
        type="button"
        :disabled="disabled"
        class="flex-shrink-0"
        :aria-label="open ? 'Fechar calendario' : 'Abrir calendario'"
        @click.stop="toggleOpen"
      >
        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </button>
    </div>

    <!-- Teleport to body to escape overflow:hidden and stacking contexts -->
    <Teleport to="body">
      <Transition name="picker-pop">
        <div
          v-if="open"
          ref="dropdownRef"
          :style="dropdownStyle"
          class="w-72 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700/60 p-4 select-none"
        >
          <!-- Month / Year navigation -->
          <div class="flex items-center justify-between mb-3">
            <button type="button" @click="prevMonth" class="picker-nav-btn">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>
            <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 capitalize">
              {{ MONTHS[viewMonth] }} {{ viewYear }}
            </span>
            <button type="button" @click="nextMonth" class="picker-nav-btn">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
          </div>

          <!-- Day names header -->
          <div class="grid grid-cols-7 mb-1">
            <div v-for="d in DAY_NAMES" :key="d"
              class="text-center text-xs font-medium text-slate-400 dark:text-slate-500 py-1">
              {{ d }}
            </div>
          </div>

          <!-- Day grid -->
          <div class="grid grid-cols-7 gap-y-0.5">
            <div v-for="(day, i) in calendarDays" :key="i" class="flex justify-center">
              <button
                v-if="day !== null"
                type="button"
                @click="selectDay(day)"
                class="w-8 h-8 rounded-full text-sm font-medium transition-all duration-150 flex items-center justify-center"
                :class="getDayClass(day)"
              >{{ day }}</button>
              <div v-else class="w-8 h-8" />
            </div>
          </div>

          <!-- Time picker (apenas quando type=datetime) -->
          <div v-if="isDateTime"
            class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700/40 flex items-center justify-between gap-2">
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Hora</span>
            <input type="time" :value="timePart" @input="onTimeInput" class="dt-time-input" />
          </div>

          <!-- Footer -->
          <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700/40 flex items-center justify-between">
            <button type="button" @click="clearDate"
              class="text-xs text-slate-400 dark:text-slate-500 hover:text-red-400 transition-colors">
              Limpar
            </button>
            <button type="button" @click="selectToday"
              class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 transition-colors">
              Hoje
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { onClickOutside } from '@vueuse/core';
import { dataBr, isoDeDataBr } from '@/utils/inputMasks';

const props = defineProps({
  modelValue:  { type: String, default: '' },
  extraClass:  { type: String, default: '' },
  error:       { type: Boolean, default: false },
  id:          { type: String, default: '' },
  placeholder: { type: String, default: 'dd/mm/aaaa' },
  disabled:    { type: Boolean, default: false },
  readonly:    { type: Boolean, default: false },
  required:    { type: Boolean, default: false },
  showIcon:    { type: Boolean, default: true },
  type:        { type: String, default: 'date', validator: (v) => ['date', 'datetime'].includes(v) },
});
const emit = defineEmits(['update:modelValue']);

const isDateTime = computed(() => props.type === 'datetime');

const el          = ref(null);
const dropdownRef = ref(null);
const open        = ref(false);
const dropdownStyle = ref({});

onClickOutside(el, () => { open.value = false; }, { ignore: [dropdownRef] });

function toggleOpen() {
  if (props.disabled || props.readonly) return;
  open.value = !open.value;
}

function calculatePosition() {
  if (!el.value) return;
  const rect = el.value.getBoundingClientRect();
  const spaceBelow = window.innerHeight - rect.bottom;
  const dropdownH = 340; // approximate height
  const top = spaceBelow >= dropdownH
    ? rect.bottom + 8
    : rect.top - dropdownH - 8;

  dropdownStyle.value = {
    position: 'fixed',
    top: `${top}px`,
    left: `${rect.left}px`,
    zIndex: 9999,
  };
}

const now = new Date();

/**
 * Data no fuso LOCAL como 'YYYY-MM-DD'.
 *
 * `toISOString()` converte para UTC: no horario de Brasilia, depois das 21h, o
 * botao "Hoje" gravava a data do dia seguinte e o calendario circulava o dia
 * errado. O calendario ja monta os dias em horario local (ver isoFor), entao a
 * unica fonte divergente era o UTC.
 */
function isoLocal(data) {
  return [
    data.getFullYear(),
    String(data.getMonth() + 1).padStart(2, '0'),
    String(data.getDate()).padStart(2, '0'),
  ].join('-');
}

const hojeIso = isoLocal(now);

function parseSelectedDate() {
  if (!props.modelValue) return { m: now.getMonth(), y: now.getFullYear() };
  const dateOnly = props.modelValue.split('T')[0];
  const d = new Date(dateOnly + 'T00:00');
  return isNaN(d) ? { m: now.getMonth(), y: now.getFullYear() } : { m: d.getMonth(), y: d.getFullYear() };
}

const initial   = parseSelectedDate();
const viewMonth = ref(initial.m);
const viewYear  = ref(initial.y);

watch(() => props.modelValue, (v) => {
  if (v) {
    const dateOnly = v.split('T')[0];
    const d = new Date(dateOnly + 'T00:00');
    if (!isNaN(d)) {
      viewMonth.value = d.getMonth();
      viewYear.value  = d.getFullYear();
    }
  }
});

watch(open, async (isOpen) => {
  if (!isOpen) return;
  await nextTick();
  calculatePosition();
});

const MONTHS   = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const DAY_NAMES = ['D','S','T','Q','Q','S','S'];

const timePart = computed(() => {
  if (!props.modelValue) return '';
  const t = props.modelValue.split('T')[1];
  return t ? t.slice(0, 5) : '';
});

const displayDate = computed(() => {
  if (!props.modelValue) return '';
  const [datePart, rawTime] = props.modelValue.split('T');
  const [y, m, d] = datePart.split('-');
  let out = `${d}/${m}/${y}`;
  if (isDateTime.value && rawTime) out += ` ${rawTime.slice(0, 5)}`;
  return out;
});

/**
 * Digitacao manual da data, alem do calendario.
 *
 * `textoRascunho` guarda o que esta sendo digitado enquanto a data ainda nao
 * fecha: sem ele, cada tecla seria descartada, porque o modelValue so aceita
 * data completa e valida. Quando esta vazio, a fonte da verdade e o modelValue
 * -- e assim que a escolha pelo calendario aparece no campo.
 *
 * No datetime o rascunho cobre so a parte da data; a hora continua vindo do
 * seletor de hora, e buildValue() a preserva.
 */
const textoRascunho = ref('');

const textoVisivel = computed(() => (
  textoRascunho.value !== '' ? textoRascunho.value : displayDate.value
));

// Escolher no calendario tem que apagar o rascunho, senao o texto digitado
// antes continuaria na tela sobrepondo a data escolhida.
watch(() => props.modelValue, () => { textoRascunho.value = ''; });

function aoDigitar(evento) {
  const elemento = evento.target;
  const texto = dataBr(elemento.value);

  // Mesma armadilha do TextInput: caractere rejeitado deixa o valor mascarado
  // igual ao anterior, o Vue nao repinta, e a letra fica visivel no DOM.
  if (elemento.value !== texto) {
    elemento.value = texto;
  }

  textoRascunho.value = texto;

  const iso = isoDeDataBr(texto);

  if (iso) {
    emit('update:modelValue', buildValue(iso));
  } else if (props.modelValue) {
    // Apagar ou corromper uma data ja escolhida tem que limpar o modelo. Sem
    // isto o campo mostraria o rascunho e enviaria a data antiga.
    emit('update:modelValue', '');
  }
}

/**
 * Ao sair, texto incompleto e descartado: mostrar "19/08" num campo de data e
 * dizer que ha algo la quando o formulario nao tem nada.
 */
function aoSair() {
  if (! isoDeDataBr(textoRascunho.value)) {
    textoRascunho.value = '';
  }
}

const calendarDays = computed(() => {
  const firstDow    = new Date(viewYear.value, viewMonth.value, 1).getDay();
  const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate();
  const arr = Array(firstDow).fill(null);
  for (let d = 1; d <= daysInMonth; d++) arr.push(d);
  return arr;
});

function isoFor(day) {
  return `${viewYear.value}-${String(viewMonth.value + 1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
}

function getDayClass(day) {
  const iso      = isoFor(day);
  const todayIso = hojeIso;
  const selIso   = props.modelValue ? props.modelValue.split('T')[0] : '';
  if (selIso === iso)
    return 'bg-emerald-500 text-white shadow-md shadow-emerald-500/30';
  if (todayIso === iso)
    return 'ring-1 ring-emerald-500 text-emerald-600 dark:text-emerald-400';
  return 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60';
}

// Monta o valor emitido conforme o tipo (date -> YYYY-MM-DD, datetime -> YYYY-MM-DDTHH:mm)
function buildValue(dateIso) {
  if (!dateIso) return '';
  if (!isDateTime.value) return dateIso;
  return `${dateIso}T${timePart.value || '00:00'}`;
}

function selectDay(day) {
  emit('update:modelValue', buildValue(isoFor(day)));
  if (!isDateTime.value) open.value = false;
}

function onTimeInput(e) {
  const time = e.target.value;
  if (!time) return;
  const dateIso = (props.modelValue ? props.modelValue.split('T')[0] : '') || hojeIso;
  emit('update:modelValue', `${dateIso}T${time}`);
}

function clearDate() { emit('update:modelValue', ''); open.value = false; }

function selectToday() {
  const t = hojeIso;
  viewMonth.value = now.getMonth();
  viewYear.value  = now.getFullYear();
  if (isDateTime.value) {
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    emit('update:modelValue', `${t}T${hh}:${mm}`);
  } else {
    emit('update:modelValue', t);
    open.value = false;
  }
}

function prevMonth() {
  if (viewMonth.value === 0) { viewMonth.value = 11; viewYear.value--; }
  else viewMonth.value--;
}

function nextMonth() {
  if (viewMonth.value === 11) { viewMonth.value = 0; viewYear.value++; }
  else viewMonth.value++;
}
</script>

<style scoped>
/* Estilo proprio (portavel): o componente nao depende mais do CSS do RAT.
   Espelha .dt-input do modulo RAT para manter consistencia visual. */
.dt-input {
  @apply px-3 py-2 sm:px-4 sm:py-2.5 text-sm sm:text-base rounded-lg bg-slate-50 dark:bg-slate-900/50
    text-slate-900 dark:text-slate-200
    border border-slate-300 dark:border-slate-700/50
    focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500
    transition-all duration-200;
}
/* O foco agora e do <input> interno, e nao mais do elemento que desenha a
   borda. Sem focus-within o campo nao daria nenhum sinal de estar ativo -- as
   variantes focus: acima ficaram inertes quando o gatilho deixou de ser botao. */
.dt-input:focus-within {
  @apply outline-none ring-2 ring-indigo-500/40 border-indigo-500;
}
.dt-input-filled {
  /* !important para vencer .dark .dt-input (que tem especificidade maior pelo .dark) */
  @apply !border-2 !border-emerald-500/60 hover:!border-emerald-500/80;
}
.dt-input-error {
  /* Campo obrigatorio nao preenchido ao salvar: alerta em vermelho */
  @apply !border-2 !border-red-500/70 hover:!border-red-500/80;
}
.dt-input-disabled {
  @apply opacity-60 cursor-not-allowed;
}

.dt-time-input {
  @apply px-2 py-1 text-sm rounded-lg bg-slate-50 dark:bg-slate-900/50
    text-slate-900 dark:text-slate-200
    border border-slate-300 dark:border-slate-700/50
    focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500;
}

.picker-nav-btn {
  @apply w-7 h-7 rounded-lg flex items-center justify-center
    text-slate-500 dark:text-slate-400
    hover:bg-slate-100 dark:hover:bg-slate-700/60
    hover:text-slate-900 dark:hover:text-white
    transition-colors;
}

.picker-pop-enter-active,
.picker-pop-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.picker-pop-enter-from,
.picker-pop-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.97);
}
</style>
