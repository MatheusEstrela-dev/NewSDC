<template>
  <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/70">
      <div class="min-w-0">
        <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">Kits</h3>
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Composição e quantos dá para montar com o saldo disponível</p>
      </div>
      <button v-if="canCreate" type="button" class="text-xs font-semibold text-sky-600 hover:underline" @click="emit('novo')">+ Kit</button>
    </div>
    <div class="divide-y divide-slate-200 dark:divide-slate-800">
      <article v-for="kit in kits" :key="kit.id" class="p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ kit.nome }}</h4>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ kit.codigo }} · montáveis agora: <strong>{{ kit.montaveis }}</strong></p>
          </div>
          <button
            v-if="canMove"
            type="button"
            class="rounded-md border border-blue-300 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50 disabled:opacity-40 dark:border-blue-500/40 dark:text-blue-300"
            :disabled="kit.montaveis === 0"
            @click="emit('saida', kit)"
          >
            Saída de kits
          </button>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
          <span v-for="componente in kit.componentes" :key="componente" class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
            {{ componente }}
          </span>
        </div>
      </article>
      <p v-if="kits.length === 0" class="p-4 text-sm text-slate-500 dark:text-slate-400">Nenhum kit cadastrado.</p>
    </div>
  </section>
</template>

<script setup>
defineProps({
  kits: { type: Array, default: () => [] },
  canMove: { type: Boolean, default: false },
  canCreate: { type: Boolean, default: false },
});
const emit = defineEmits(['saida', 'novo']);
</script>
