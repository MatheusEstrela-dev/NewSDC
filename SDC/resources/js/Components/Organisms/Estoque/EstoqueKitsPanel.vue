<template>
  <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/70">
      <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">Composicao de kits</h3>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Ficha tecnica, custo medio composto e montagem</p>
        </div>
        <span class="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-500/25 dark:bg-emerald-500/15 dark:text-emerald-300">
          {{ kits.length }}
        </span>
      </div>
    </div>

    <div class="divide-y divide-slate-200 dark:divide-slate-800">
      <article v-for="kit in kits" :key="kit.id" class="p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ kit.nome }}</h4>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
              {{ kit.codigo }} / saldo {{ kit.saldo }} / montaveis {{ kit.montaveis }}
            </p>
          </div>
          <ActionButton
            module="estoque"
            resource="kits"
            action="create"
            :allowed="canAssemble"
            size="sm"
            label="Montar Kit"
            @click="emit('assemble', kit)"
          />
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
          <span
            v-for="componente in kit.componentes"
            :key="componente"
            class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
          >
            {{ componente }}
          </span>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';

defineProps({
  kits: {
    type: Array,
    default: () => [],
  },
  canAssemble: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['assemble']);
</script>
