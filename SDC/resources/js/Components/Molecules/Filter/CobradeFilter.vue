<template>
  <div class="space-y-2">
    <!-- Search Input -->
    <div class="relative">
      <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Buscar tipo de desastre..."
        class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border
               bg-white dark:bg-slate-800
               border-slate-300 dark:border-slate-600
               text-slate-900 dark:text-slate-100
               placeholder-slate-400 dark:placeholder-slate-500
               focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
      />
    </div>

    <!-- Selected Count -->
    <div v-if="selectedIds.length > 0" class="text-sm text-primary-600 dark:text-primary-400">
      {{ selectedIds.length }} tipo(s) selecionado(s)
    </div>

    <!-- Hierarchical Tree -->
    <div class="max-h-64 overflow-y-auto border rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
      <div v-for="grupo in filteredHierarchy" :key="grupo.label" class="border-b border-slate-100 dark:border-slate-700 last:border-b-0">
        <!-- Grupo Header -->
        <button
          type="button"
          class="w-full flex items-center justify-between p-3 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
          @click="toggleGrupo(grupo.label)"
        >
          <div class="flex items-center gap-2">
            <input
              type="checkbox"
              :checked="isGrupoSelected(grupo)"
              :indeterminate="isGrupoIndeterminate(grupo)"
              class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-primary-500 focus:ring-primary-500"
              @click.stop="toggleGrupoSelection(grupo)"
            />
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ grupo.label }}</span>
            <span class="text-xs text-slate-500 dark:text-slate-400">({{ grupo.ids.length }})</span>
          </div>
          <ChevronRightIcon
            class="w-4 h-4 text-slate-400 transition-transform duration-200"
            :class="{ 'rotate-90': expandedGrupos.has(grupo.label) }"
          />
        </button>

        <!-- Subgrupos -->
        <Transition
          enter-active-class="transition-colors duration-200 ease-out"
          enter-from-class="opacity-0 max-h-0"
          enter-to-class="opacity-100 max-h-screen"
          leave-active-class="transition-colors duration-150 ease-in"
          leave-from-class="opacity-100 max-h-screen"
          leave-to-class="opacity-0 max-h-0"
        >
          <div v-show="expandedGrupos.has(grupo.label)" class="pl-4 bg-slate-50/50 dark:bg-slate-900/30">
            <div v-for="subgrupo in grupo.subgrupos" :key="subgrupo.label" class="border-t border-slate-100 dark:border-slate-700/50">
              <!-- Subgrupo Header -->
              <button
                type="button"
                class="w-full flex items-center justify-between p-2 pl-3 text-left hover:bg-slate-100 dark:hover:bg-slate-700/30 transition-colors"
                @click="toggleSubgrupo(grupo.label, subgrupo.label)"
              >
                <div class="flex items-center gap-2">
                  <input
                    type="checkbox"
                    :checked="isSubgrupoSelected(subgrupo)"
                    :indeterminate="isSubgrupoIndeterminate(subgrupo)"
                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-primary-500 focus:ring-primary-500"
                    @click.stop="toggleSubgrupoSelection(subgrupo)"
                  />
                  <span class="text-sm text-slate-700 dark:text-slate-300">{{ subgrupo.label }}</span>
                  <span class="text-xs text-slate-400">({{ subgrupo.ids.length }})</span>
                </div>
                <ChevronRightIcon
                  v-if="subgrupo.items.length > 1"
                  class="w-3 h-3 text-slate-400 transition-transform duration-200"
                  :class="{ 'rotate-90': expandedSubgrupos.has(`${grupo.label}:${subgrupo.label}`) }"
                />
              </button>

              <!-- Items -->
              <Transition
                enter-active-class="transition-colors duration-150 ease-out"
                enter-from-class="opacity-0 max-h-0"
                enter-to-class="opacity-100 max-h-screen"
                leave-active-class="transition-colors duration-100 ease-in"
                leave-from-class="opacity-100 max-h-screen"
                leave-to-class="opacity-0 max-h-0"
              >
                <div
                  v-show="expandedSubgrupos.has(`${grupo.label}:${subgrupo.label}`) && subgrupo.items.length > 1"
                  class="pl-6 py-1 space-y-1"
                >
                  <label
                    v-for="item in subgrupo.items"
                    :key="item.id"
                    class="flex items-center gap-2 p-1.5 rounded cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/30 transition-colors"
                  >
                    <input
                      type="checkbox"
                      :checked="selectedIds.includes(item.id)"
                      class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-primary-500 focus:ring-primary-500"
                      @change="toggleItem(item.id)"
                    />
                    <span class="text-sm text-slate-600 dark:text-slate-400">
                      <span class="text-xs text-slate-400 mr-1">{{ item.cobrade }}</span>
                      {{ item.label }}
                    </span>
                  </label>
                </div>
              </Transition>
            </div>
          </div>
        </Transition>
      </div>

      <!-- Empty State -->
      <div v-if="filteredHierarchy.length === 0" class="p-4 text-center text-slate-500 dark:text-slate-400">
        Nenhum tipo de desastre encontrado
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, Transition } from 'vue';
import ChevronRightIcon from '@/Components/Icons/ChevronRightIcon.vue';
import MagnifyingGlassIcon from '@/Components/Icons/MagnifyingGlassIcon.vue';

const props = defineProps({
  hierarchy: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:modelValue']);

const searchQuery = ref('');
const expandedGrupos = ref(new Set());
const expandedSubgrupos = ref(new Set());

const selectedIds = computed(() => props.modelValue || []);

const filteredHierarchy = computed(() => {
  if (!searchQuery.value.trim()) return props.hierarchy;

  const query = searchQuery.value.toLowerCase();
  return props.hierarchy
    .map(grupo => {
      const filteredSubgrupos = grupo.subgrupos
        .map(subgrupo => {
          const filteredItems = subgrupo.items.filter(item =>
            item.label.toLowerCase().includes(query) ||
            (item.cobrade && item.cobrade.toLowerCase().includes(query))
          );
          if (filteredItems.length === 0) return null;
          return { ...subgrupo, items: filteredItems, ids: filteredItems.map(i => i.id) };
        })
        .filter(Boolean);

      if (filteredSubgrupos.length === 0) return null;
      return {
        ...grupo,
        subgrupos: filteredSubgrupos,
        ids: filteredSubgrupos.flatMap(s => s.ids),
      };
    })
    .filter(Boolean);
});

function toggleGrupo(label) {
  if (expandedGrupos.value.has(label)) {
    expandedGrupos.value.delete(label);
  } else {
    expandedGrupos.value.add(label);
  }
}

function toggleSubgrupo(grupoLabel, subgrupoLabel) {
  const key = `${grupoLabel}:${subgrupoLabel}`;
  if (expandedSubgrupos.value.has(key)) {
    expandedSubgrupos.value.delete(key);
  } else {
    expandedSubgrupos.value.add(key);
  }
}

function isGrupoSelected(grupo) {
  return grupo.ids.every(id => selectedIds.value.includes(id));
}

function isGrupoIndeterminate(grupo) {
  const selectedCount = grupo.ids.filter(id => selectedIds.value.includes(id)).length;
  return selectedCount > 0 && selectedCount < grupo.ids.length;
}

function isSubgrupoSelected(subgrupo) {
  return subgrupo.ids.every(id => selectedIds.value.includes(id));
}

function isSubgrupoIndeterminate(subgrupo) {
  const selectedCount = subgrupo.ids.filter(id => selectedIds.value.includes(id)).length;
  return selectedCount > 0 && selectedCount < subgrupo.ids.length;
}

function toggleGrupoSelection(grupo) {
  const currentIds = [...selectedIds.value];
  const allSelected = isGrupoSelected(grupo);

  let newIds;
  if (allSelected) {
    newIds = currentIds.filter(id => !grupo.ids.includes(id));
  } else {
    const idsToAdd = grupo.ids.filter(id => !currentIds.includes(id));
    newIds = [...currentIds, ...idsToAdd];
  }

  emit('update:modelValue', newIds);
}

function toggleSubgrupoSelection(subgrupo) {
  const currentIds = [...selectedIds.value];
  const allSelected = isSubgrupoSelected(subgrupo);

  let newIds;
  if (allSelected) {
    newIds = currentIds.filter(id => !subgrupo.ids.includes(id));
  } else {
    const idsToAdd = subgrupo.ids.filter(id => !currentIds.includes(id));
    newIds = [...currentIds, ...idsToAdd];
  }

  emit('update:modelValue', newIds);
}

function toggleItem(itemId) {
  const currentIds = [...selectedIds.value];
  const index = currentIds.indexOf(itemId);

  if (index > -1) {
    currentIds.splice(index, 1);
  } else {
    currentIds.push(itemId);
  }

  emit('update:modelValue', currentIds);
}
</script>
