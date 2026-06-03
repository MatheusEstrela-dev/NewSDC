<template>
  <div class="relative" ref="el">
    <button
      type="button"
      @click.stop="toggleOpen"
      class="dt-input w-full flex items-center justify-between gap-2"
      :class="[modelValue ? 'dt-input-filled' : '', extraClass]"
    >
      <span :class="modelValue ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500'">
        {{ displayDate || 'dd/mm/aaaa' }}
      </span>
      <svg class="w-4 h-4 flex-shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
    </button>

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

const props = defineProps({
  modelValue: { type: String, default: '' },
  extraClass: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const el          = ref(null);
const dropdownRef = ref(null);
const open        = ref(false);
const dropdownStyle = ref({});

onClickOutside(el, () => { open.value = false; }, { ignore: [dropdownRef] });

function toggleOpen() { open.value = !open.value; }

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

const displayDate = computed(() => {
  if (!props.modelValue) return '';
  const dateOnly = props.modelValue.split('T')[0];
  const [y, m, d] = dateOnly.split('-');
  return `${d}/${m}/${y}`;
});

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
  const todayIso = now.toISOString().split('T')[0];
  const selIso   = props.modelValue ? props.modelValue.split('T')[0] : '';
  if (selIso === iso)
    return 'bg-emerald-500 text-white shadow-md shadow-emerald-500/30';
  if (todayIso === iso)
    return 'ring-1 ring-emerald-500 text-emerald-600 dark:text-emerald-400';
  return 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60';
}

function selectDay(day) {
  emit('update:modelValue', isoFor(day));
  open.value = false;
}

function clearDate() { emit('update:modelValue', ''); open.value = false; }

function selectToday() {
  const t = now.toISOString().split('T')[0];
  viewMonth.value = now.getMonth();
  viewYear.value  = now.getFullYear();
  emit('update:modelValue', t);
  open.value = false;
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
