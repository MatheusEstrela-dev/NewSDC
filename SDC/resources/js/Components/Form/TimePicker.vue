<template>
  <div class="relative" ref="el">
    <button
      type="button"
      @click.stop="toggleOpen"
      class="dt-input flex items-center justify-between gap-2"
      :class="[modelValue ? 'dt-input-filled' : '', extraClass]"
    >
      <span :class="modelValue ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500'">
        {{ modelValue || 'hh:mm' }}
      </span>
      <svg class="w-4 h-4 flex-shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </button>

    <!-- Teleport to body to escape overflow:hidden and stacking contexts -->
    <Teleport to="body">
      <Transition name="picker-pop">
        <div
          v-if="open"
          ref="dropdownRef"
          :style="dropdownStyle"
          class="w-52 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700/60 p-4 select-none"
        >
          <!-- Preview -->
          <div class="text-center text-sm font-semibold text-slate-800 dark:text-slate-200 mb-3">
            {{ selectedHour || '--' }}:{{ selectedMinute || '--' }}
          </div>

          <div class="flex gap-2">
            <!-- Hours column -->
            <div class="flex-1">
              <div class="text-center text-xs font-medium text-slate-400 dark:text-slate-500 mb-1.5">Hora</div>
              <div ref="hoursCol" class="h-44 overflow-y-auto hide-scrollbar space-y-0.5">
                <button
                  v-for="h in HOURS"
                  :key="h"
                  type="button"
                  @click="selectHour(h)"
                  class="w-full py-1.5 text-center text-sm font-medium rounded-lg transition-all duration-100"
                  :class="selectedHour === h
                    ? 'bg-emerald-500 text-white shadow-sm shadow-emerald-500/30'
                    : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60'"
                >{{ h }}</button>
              </div>
            </div>

            <div class="flex self-stretch pt-8 justify-center w-4">
              <span class="text-slate-300 dark:text-slate-600 text-xl font-bold leading-none">:</span>
            </div>

            <!-- Minutes column -->
            <div class="flex-1">
              <div class="text-center text-xs font-medium text-slate-400 dark:text-slate-500 mb-1.5">Min</div>
              <div ref="minutesCol" class="h-44 overflow-y-auto hide-scrollbar space-y-0.5">
                <button
                  v-for="m in MINUTES"
                  :key="m"
                  type="button"
                  @click="selectMinute(m)"
                  class="w-full py-1.5 text-center text-sm font-medium rounded-lg transition-all duration-100"
                  :class="selectedMinute === m
                    ? 'bg-emerald-500 text-white shadow-sm shadow-emerald-500/30'
                    : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/60'"
                >{{ m }}</button>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700/40 flex items-center justify-between">
            <button type="button" @click="clearTime"
              class="text-xs text-slate-400 dark:text-slate-500 hover:text-red-400 transition-colors">
              Limpar
            </button>
            <button type="button" @click="selectNow"
              class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 transition-colors">
              Agora
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';
import { onClickOutside } from '@vueuse/core';

const props = defineProps({
  modelValue: { type: String, default: '' },
  extraClass: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const el          = ref(null);
const dropdownRef = ref(null);
const open        = ref(false);
const hoursCol    = ref(null);
const minutesCol  = ref(null);
const dropdownStyle = ref({});

onClickOutside(el, () => { open.value = false; }, { ignore: [dropdownRef] });
function toggleOpen() { open.value = !open.value; }

function calculatePosition() {
  if (!el.value) return;
  const rect = el.value.getBoundingClientRect();
  const spaceBelow = window.innerHeight - rect.bottom;
  const dropdownH = 320;
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

const HOURS   = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));
const MINUTES = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'));

function parseValue(v) {
  if (!v) return { h: '', m: '' };
  const parts = v.split(':');
  return { h: parts[0] || '', m: parts[1] || '' };
}

const { h: initH, m: initM } = parseValue(props.modelValue);
const selectedHour   = ref(initH);
const selectedMinute = ref(initM);

watch(() => props.modelValue, (v) => {
  const { h, m } = parseValue(v);
  selectedHour.value   = h;
  selectedMinute.value = m;
});

watch(open, async (isOpen) => {
  if (!isOpen) return;
  await nextTick();
  calculatePosition();
  scrollToSelected(hoursCol.value,   HOURS.indexOf(selectedHour.value));
  scrollToSelected(minutesCol.value, MINUTES.indexOf(selectedMinute.value));
});

function scrollToSelected(container, idx) {
  if (!container || idx < 0) return;
  const btn = container.querySelectorAll('button')[idx];
  if (btn) btn.scrollIntoView({ block: 'center', behavior: 'instant' });
}

function emitAndClose() {
  emit('update:modelValue', `${selectedHour.value}:${selectedMinute.value}`);
  open.value = false;
}

function selectHour(h) {
  selectedHour.value = h;
  if (selectedMinute.value !== '') emitAndClose();
}

function selectMinute(m) {
  selectedMinute.value = m;
  if (selectedHour.value !== '') emitAndClose();
}

function clearTime() {
  selectedHour.value   = '';
  selectedMinute.value = '';
  emit('update:modelValue', '');
  open.value = false;
}

function selectNow() {
  const now = new Date();
  selectedHour.value   = String(now.getHours()).padStart(2, '0');
  selectedMinute.value = String(now.getMinutes()).padStart(2, '0');
  emitAndClose();
}
</script>

<style scoped>
/* Estilo proprio (portavel): nao depende mais do CSS do RAT. */
.dt-input {
  @apply px-3 py-2 sm:px-4 sm:py-2.5 text-sm sm:text-base rounded-lg bg-slate-50 dark:bg-slate-900/50
    text-slate-900 dark:text-slate-200
    border border-slate-300 dark:border-slate-700/50
    focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500
    transition-all duration-200;
}
.dt-input-filled {
  /* !important para vencer .dark .dt-input (especificidade maior pelo .dark) */
  @apply !border-2 !border-emerald-500/60 hover:!border-emerald-500/80;
}

.hide-scrollbar {
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.hide-scrollbar::-webkit-scrollbar {
  display: none;
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
