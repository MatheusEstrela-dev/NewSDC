<template>
  <ListSurface>
    <template v-if="title || $slots.headerActions" #header>
      <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <h3 v-if="title" class="truncate text-sm font-bold text-slate-800 dark:text-slate-100 sm:text-base">{{ title }}</h3>
          <p v-if="subtitle" class="mt-0.5 hidden text-xs text-slate-400 sm:block">{{ subtitle }}</p>
        </div>
        <div class="flex items-center gap-2">
          <span
            v-if="showTotalBadge"
            class="rounded-lg border border-blue-200 bg-blue-100 px-2 py-1 text-xs font-bold text-blue-600 dark:border-blue-500/30 dark:bg-blue-500/20 dark:text-blue-300 sm:px-3 sm:py-1.5"
          >
            {{ totalLabel }}
          </span>
          <slot name="headerActions" />
        </div>
      </div>
    </template>

    <div class="overflow-x-auto -mx-px">
      <table class="min-w-full text-sm">
        <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold bg-slate-100 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/50">
          <tr>
            <th
              v-for="(column, idx) in normalizedColumns"
              :key="column.key ?? idx"
              :class="['px-4 py-3 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider', alignClass(column.align)]"
            >
              {{ column.label }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/30">
          <template v-if="rows.length > 0">
            <slot name="row" v-for="(row, idx) in rows" :row="row" :index="idx" />
          </template>
          <tr v-else>
            <td :colspan="normalizedColumns.length" class="px-4 py-12 text-center text-slate-400 dark:text-slate-500">
              <slot name="empty">{{ emptyText }}</slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="hasPagination"
      class="px-4 py-3 border-t border-slate-200 dark:border-slate-700/50 flex items-center justify-between gap-3"
    >
      <p class="text-xs text-slate-500 dark:text-slate-400">
        Página {{ pagination.current_page }} de {{ pagination.last_page }} ({{ pagination.total }} registros)
      </p>
      <div class="space-x-2">
        <Link
          v-for="(link, i) in pagination.links || []"
          :key="i"
          :href="link.url || '#'"
          v-html="link.label"
          class="px-3 py-1 text-sm rounded border"
          :class="link.active
            ? 'bg-blue-600 text-white border-blue-600'
            : 'border-slate-300 text-slate-700 dark:text-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
        />
      </div>
    </div>
  </ListSurface>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import ListSurface from '@/Components/Organisms/Table/ListSurface.vue';

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
  rows: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: null,
  },
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
  total: {
    type: [Number, String, null],
    default: null,
  },
  emptyText: {
    type: String,
    default: 'Nenhum registro encontrado.',
  },
});

const normalizedColumns = computed(() =>
  props.columns.map((c) => (typeof c === 'string' ? { label: c, align: 'left' } : { align: 'left', ...c }))
);

const hasPagination = computed(() => props.pagination && props.pagination.last_page > 1);

const totalLabel = computed(() => {
  if (props.total !== null && props.total !== undefined) return props.total;
  return props.pagination?.total ?? props.rows.length;
});

const showTotalBadge = computed(() => totalLabel.value !== null && totalLabel.value !== undefined);

function alignClass(align) {
  if (align === 'right') return 'text-right';
  if (align === 'center') return 'text-center';
  return 'text-left';
}
</script>
