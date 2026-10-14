<template>
  <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="mb-3 flex items-center justify-between gap-2">
      <div>
        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Ocupação por almoxarifado</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">Saldo em unidades sobre a capacidade cadastrada</p>
      </div>
      <button v-if="canCreate" type="button" class="text-xs font-semibold text-sky-600 hover:underline" @click="$emit('novo')">+ Almoxarifado</button>
    </div>
    <ul class="space-y-3">
      <li v-for="a in almoxarifados" :key="a.id">
        <button
          type="button"
          class="w-full text-left"
          :class="selecionado === String(a.id) ? 'opacity-100' : 'opacity-90 hover:opacity-100'"
          @click="$emit('filtrar', a.id)"
        >
          <div class="flex items-center justify-between text-sm">
            <span class="font-medium text-slate-800 dark:text-slate-200" :class="selecionado === String(a.id) ? 'underline' : ''">{{ a.nome }}</span>
            <span class="text-xs text-slate-500 dark:text-slate-400">
              {{ a.saldo }}<template v-if="a.capacidade"> / {{ a.capacidade }} ({{ a.percentual }}%)</template>
            </span>
          </div>
          <div class="mt-1 h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" role="progressbar" :aria-valuenow="a.percentual ?? 0" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full rounded-full transition-all" :class="cor(a.percentual)" :style="{ width: `${a.percentual ?? 0}%` }"></div>
          </div>
          <p v-if="!a.capacidade" class="mt-0.5 text-[11px] text-slate-400">Capacidade não informada</p>
        </button>
      </li>
    </ul>
  </section>
</template>

<script setup>
defineProps({
  almoxarifados: { type: Array, default: () => [] },
  selecionado: { type: String, default: '' },
  canCreate: { type: Boolean, default: false },
});
defineEmits(['filtrar', 'novo']);

const cor = (p) => (p === null || p === undefined ? 'bg-slate-300'
  : p >= 90 ? 'bg-red-500' : p >= 70 ? 'bg-amber-500' : 'bg-emerald-500');
</script>
