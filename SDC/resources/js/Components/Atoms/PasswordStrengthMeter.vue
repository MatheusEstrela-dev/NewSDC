<template>
  <div class="space-y-1.5">
    <div class="flex h-1.5 gap-1">
      <span
        v-for="i in 4"
        :key="i"
        class="flex-1 rounded-full transition-colors duration-200"
        :class="i <= score ? barColor : 'bg-slate-200 dark:bg-slate-700'"
      />
    </div>
    <div class="flex items-center justify-between text-[11px]">
      <span :class="labelColor">{{ label }}</span>
      <span class="text-slate-400">{{ password.length }} caracteres</span>
    </div>
    <ul class="grid grid-cols-2 gap-x-3 gap-y-1 text-[11px] text-slate-500 dark:text-slate-400 pt-1">
      <li
        v-for="req in requirements"
        :key="req.label"
        class="flex items-center gap-1.5"
        :class="req.ok ? 'text-emerald-600 dark:text-emerald-400' : ''"
      >
        <span
          class="inline-block w-1.5 h-1.5 rounded-full"
          :class="req.ok ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'"
        />
        {{ req.label }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  password: { type: String, default: '' },
});

const requirements = computed(() => [
  { label: 'Mínimo 8 caracteres', ok: props.password.length >= 8 },
  { label: 'Letra minúscula', ok: /[a-z]/.test(props.password) },
  { label: 'Letra maiúscula', ok: /[A-Z]/.test(props.password) },
  { label: 'Número', ok: /\d/.test(props.password) },
  { label: 'Símbolo', ok: /[^A-Za-z0-9]/.test(props.password) },
]);

const score = computed(() => {
  if (!props.password) return 0;
  const passed = requirements.value.filter((r) => r.ok).length;
  // 5 requisitos -> escala 0..4: cada bar representa 25% de robustez
  if (passed <= 1) return 1;
  if (passed === 2) return 2;
  if (passed === 3 || passed === 4) return 3;
  return 4;
});

const label = computed(() => {
  if (!props.password) return 'Defina uma senha';
  return ['Muito fraca', 'Fraca', 'Razoavel', 'Boa', 'Forte'][score.value] || 'Forte';
});

const barColor = computed(() => {
  return {
    1: 'bg-rose-500',
    2: 'bg-orange-500',
    3: 'bg-amber-500',
    4: 'bg-emerald-500',
  }[score.value] || 'bg-slate-300';
});

const labelColor = computed(() => {
  return {
    1: 'text-rose-600 dark:text-rose-400',
    2: 'text-orange-600 dark:text-orange-400',
    3: 'text-amber-600 dark:text-amber-400',
    4: 'text-emerald-600 dark:text-emerald-400',
  }[score.value] || 'text-slate-500';
});
</script>
