<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-x-10"
      enter-to-class="opacity-100 translate-x-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-x-0"
      leave-to-class="opacity-0 translate-x-10"
    >
      <div v-if="show" class="fixed inset-y-0 right-0 z-[60] w-full max-w-2xl bg-[#0b0e14] shadow-2xl border-l border-gray-800 flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-800 flex justify-between items-center shrink-0 bg-gray-900/50">
          <div>
            <div class="flex items-center gap-3">
              <span :class="getLevelColor(log?.level)" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border" :style="{ borderColor: 'currentColor' }">
                {{ log?.level }}
              </span>
              <h3 class="text-sm font-semibold text-gray-200 font-mono">
                Log Detail
              </h3>
            </div>
            <p class="text-[11px] text-gray-500 mt-1 font-mono">
              {{ formatFullDate(log?.timestamp) }}
            </p>
          </div>
          <button @click="$emit('close')" class="text-gray-500 hover:text-white transition-colors p-2 hover:bg-gray-800 rounded-full">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">
          <!-- Main Message -->
          <section>
            <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Message</h4>
            <div class="bg-[#161b22] p-4 rounded-lg border border-gray-800 group relative">
              <pre class="text-sm text-blue-300 whitespace-pre-wrap font-mono break-all leading-relaxed">{{ log?.message }}</pre>
              <button @click="copyText(log?.message)" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity p-1.5 bg-gray-800 rounded text-gray-400 hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              </button>
            </div>
          </section>

          <!-- Context Grid -->
          <section class="grid grid-cols-2 gap-4">
            <div v-if="log?.context?.request_id">
              <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-1">Request ID</h4>
              <p class="text-xs font-mono text-gray-400 truncate bg-gray-900/50 p-2 rounded border border-gray-800">{{ log.context.request_id }}</p>
            </div>
            <div v-if="log?.context?.ip_address">
              <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-1">IP Address</h4>
              <p class="text-xs font-mono text-gray-400 bg-gray-900/50 p-2 rounded border border-gray-800">{{ log.context.ip_address }}</p>
            </div>
            <div v-if="log?.context?.environment">
              <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-1">Environment</h4>
              <p class="text-xs font-mono text-gray-400 bg-gray-900/50 p-2 rounded border border-gray-800 capitalize">{{ log.context.environment }}</p>
            </div>
            <div v-if="log?.file">
              <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-1">File Location</h4>
              <p class="text-xs font-mono text-gray-400 bg-gray-900/50 p-2 rounded border border-gray-800 truncate">{{ log.file }}:{{ log.line }}</p>
            </div>
          </section>

          <!-- Stack Trace -->
          <section v-if="log?.context?.stack_trace?.length">
            <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Stack Trace</h4>
            <div class="space-y-1">
              <div v-for="(frame, i) in log.context.stack_trace" :key="i" class="bg-gray-900/30 p-2 rounded border border-gray-800/50 hover:bg-gray-800/30 transition-colors">
                <div class="flex justify-between items-start">
                  <span class="text-[11px] font-bold text-gray-300 font-mono">{{ frame.class }}{{ frame.class ? '::' : '' }}{{ frame.function }}</span>
                  <span class="text-[10px] text-gray-600 font-mono">#{{ i }}</span>
                </div>
                <div class="text-[10px] text-gray-500 font-mono mt-1 opacity-70">{{ frame.file }}:{{ frame.line }}</div>
              </div>
            </div>
          </section>

          <!-- Full Context JSON -->
          <section v-if="log?.context">
            <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Full Context</h4>
            <div class="bg-[#0b0e14] p-4 rounded-lg border border-gray-800 overflow-x-auto custom-scrollbar">
              <pre class="text-[11px] text-gray-400 font-mono">{{ JSON.stringify(log.context, null, 2) }}</pre>
            </div>
          </section>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-800 bg-gray-900/80 flex justify-between items-center shrink-0">
          <button @click="copyJson" class="text-xs font-medium text-gray-400 hover:text-white flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Copy JSON
          </button>
          <div class="flex gap-3">
             <button @click="$emit('close')" class="px-4 py-1.5 text-xs font-semibold text-gray-300 hover:bg-gray-800 rounded transition-colors border border-gray-700">
              Close
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  show: Boolean,
  log: Object,
})

const emit = defineEmits(['close'])

const getLevelColor = (level) => {
  const colors = {
    debug: 'text-gray-400',
    info: 'text-blue-400',
    notice: 'text-cyan-400',
    warning: 'text-yellow-400',
    error: 'text-red-400',
    critical: 'text-red-500',
    alert: 'text-red-600',
    emergency: 'text-red-700'
  }
  return colors[level?.toLowerCase()] || 'text-gray-300'
}

const formatFullDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
  })
}

const copyText = (text) => {
  navigator.clipboard.writeText(text)
  // TODO: Add toast notification
}

const copyJson = () => {
  navigator.clipboard.writeText(JSON.stringify(props.log, null, 2))
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #1f2937;
  border-radius: 10px;
}
</style>
