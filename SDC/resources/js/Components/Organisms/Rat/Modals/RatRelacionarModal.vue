<template>
  <teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="$emit('close')" />

      <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Relacionar RATs</h2>
          <button
            type="button"
            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
            @click="$emit('close')"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Search -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
          <input
            v-model="search"
            type="text"
            placeholder="Buscar por número ou responsável..."
            class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- List -->
        <div class="flex-1 overflow-y-auto p-4 space-y-2">
          <div v-if="loading" class="text-center py-8 text-slate-500 dark:text-slate-400 text-sm">
            Carregando...
          </div>
          <div v-else-if="errorMsg" class="text-center py-8 text-red-500 text-sm">
            {{ errorMsg }}
          </div>
          <div v-else-if="!filteredRats.length" class="text-center py-8 text-slate-500 dark:text-slate-400 text-sm">
            Nenhum outro RAT encontrado para relacionar.
          </div>
          <label
            v-for="rat in filteredRats"
            :key="rat.id"
            class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
            :class="
              selectedIds.has(rat.id)
                ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700'
                : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50'
            "
          >
            <input
              type="checkbox"
              :checked="selectedIds.has(rat.id)"
              class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
              @change="toggleSelection(rat.id)"
            />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                {{ rat.numero_bos || `RAT #${rat.id}` }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ rat.creator?.name || 'Sistema' }} · {{ formatDate(rat.created_at) }}
              </p>
            </div>
          </label>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between p-4 border-t border-slate-200 dark:border-slate-700">
          <span class="text-sm text-slate-500 dark:text-slate-400">
            {{ selectedIds.size }} selecionado(s)
          </span>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
              @click="$emit('close')"
            >
              Cancelar
            </button>
            <button
              type="button"
              :disabled="saving || !selectedIds.size"
              class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg transition-colors"
              @click="saveRelacionados"
            >
              {{ saving ? 'Salvando...' : 'Vincular' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  indexUrl: {
    type: String,
    default: null,
  },
  storeUrl: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['close', 'saved']);

const loading = ref(false);
const saving = ref(false);
const errorMsg = ref('');
const search = ref('');
const rats = ref([]);
const selectedIds = ref(new Set());

watch(
  () => props.show,
  async (val) => {
    if (val && props.indexUrl) {
      await fetchRelacionados();
    } else if (!val) {
      search.value = '';
      rats.value = [];
      errorMsg.value = '';
      selectedIds.value = new Set();
    }
  }
);

async function getAxios() {
  if (window.axios) return window.axios;
  const mod = await import('axios');
  return mod.default;
}

async function fetchRelacionados() {
  loading.value = true;
  errorMsg.value = '';
  try {
    const ax = await getAxios();
    const { data } = await ax.get(props.indexUrl);
    rats.value = data.data?.data ?? data.data ?? [];
    selectedIds.value = new Set(data.relacionados_ids ?? []);
  } catch (e) {
    errorMsg.value = 'Erro ao carregar lista de RATs.';
    console.error('[RatRelacionarModal] fetchRelacionados error:', e?.response?.data ?? e);
  } finally {
    loading.value = false;
  }
}

const filteredRats = computed(() => {
  const q = search.value.toLowerCase().trim();
  if (!q) return rats.value;
  return rats.value.filter(
    (r) =>
      (r.numero_bos ?? '').toLowerCase().includes(q) ||
      (r.creator?.name ?? '').toLowerCase().includes(q)
  );
});

function toggleSelection(id) {
  const next = new Set(selectedIds.value);
  if (next.has(id)) {
    next.delete(id);
  } else {
    next.add(id);
  }
  selectedIds.value = next;
}

async function saveRelacionados() {
  saving.value = true;
  try {
    const ax = await getAxios();
    await ax.post(props.storeUrl, { ids: [...selectedIds.value] });
    emit('saved');
    emit('close');
  } catch (e) {
    console.error('[RatRelacionarModal] saveRelacionados error:', e?.response?.data ?? e);
  } finally {
    saving.value = false;
  }
}

function formatDate(d) {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('pt-BR');
}
</script>
