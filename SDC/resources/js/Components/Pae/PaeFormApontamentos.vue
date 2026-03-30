<template>
  <div class="space-y-4">
    <h3 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
      <span class="w-1 h-6 bg-blue-500 rounded-full flex-shrink-0" />
      4. Apontamentos Técnicos Observados
    </h3>

    <div class="space-y-4">
      <div
        v-for="(item, index) in items"
        :key="item.id"
        class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4"
      >
        <div class="flex gap-3">
          <div class="flex-shrink-0 w-7 h-7 rounded-md bg-blue-600 text-white flex items-center justify-center text-sm font-bold">
            {{ index + 1 }}
          </div>

          <div class="flex-1 space-y-3">
            <textarea
              v-model="item.text"
              rows="3"
              :placeholder="`Digite o apontamento técnico ${index + 1}...`"
              class="w-full bg-transparent border border-slate-300 dark:border-slate-600 rounded-lg p-3 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 resize-y focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500"
            />

            <div v-if="item.children.length" class="ml-4 space-y-2">
              <div
                v-for="(child, childIndex) in item.children"
                :key="child.id"
                class="flex items-start gap-2"
              >
                <span class="flex-shrink-0 mt-2.5 px-1.5 py-0.5 rounded text-xs font-bold bg-cyan-600 text-white">
                  {{ index + 1 }}.{{ childIndex + 1 }}
                </span>
                <textarea
                  v-model="child.text"
                  rows="2"
                  :placeholder="`Sub-item ${index + 1}.${childIndex + 1}...`"
                  class="flex-1 bg-transparent border border-slate-300 dark:border-slate-600 rounded-lg p-2 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 resize-y focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500"
                />
                <button
                  type="button"
                  @click="$emit('remove-sub', index, childIndex)"
                  class="flex-shrink-0 mt-2 text-red-500/50 hover:text-red-500 transition-colors"
                  :title="`Remover sub-item ${index + 1}.${childIndex + 1}`"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
              </div>
            </div>

            <button
              type="button"
              @click="$emit('add-sub', index)"
              class="flex items-center gap-1.5 text-sm text-blue-400 hover:text-blue-300 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              Adicionar Sub-item ({{ index + 1 }}.x)
            </button>
          </div>

          <button
            type="button"
            @click="$emit('remove-item', index)"
            :disabled="items.length === 1"
            class="flex-shrink-0 text-red-500/50 hover:text-red-500 disabled:opacity-20 disabled:cursor-not-allowed transition-colors"
            title="Remover apontamento"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          </button>
        </div>
      </div>

      <button
        type="button"
        @click="$emit('add-item')"
        class="w-full py-4 flex items-center justify-center gap-2 border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-blue-500/50 dark:hover:border-blue-500/50 rounded-xl text-slate-500 dark:text-slate-400 hover:text-blue-400 font-medium text-sm transition-colors"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        Adicionar Apontamento (4.x)
      </button>
    </div>

    <div class="flex justify-end pt-2">
      <button
        type="button"
        :disabled="saving"
        @click="$emit('save')"
        class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors"
      >
        <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
        Salvar Apontamentos Técnicos
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  items: {
    type: Array,
    required: true,
  },
  saving: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['save', 'add-item', 'remove-item', 'add-sub', 'remove-sub']);
</script>