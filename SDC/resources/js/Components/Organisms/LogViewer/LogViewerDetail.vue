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
      <div v-if="show && log" class="fixed inset-y-0 right-0 z-[60] w-full max-w-2xl bg-[#0b0e14] shadow-2xl border-l border-gray-800 flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-800 flex justify-between items-start shrink-0 bg-gray-900/50">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
              <span :class="getLevelColor(log.level)" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border" :style="{ borderColor: 'currentColor' }">
                {{ log.level }}
              </span>
              <span v-if="log.layer" class="px-2 py-0.5 rounded text-[10px] font-medium bg-blue-900/50 text-blue-300">
                {{ log.layer }}
              </span>
            </div>
            <p class="text-[11px] text-gray-500 mt-2 font-mono">
              {{ formatFullDate(log.created_at) }}
            </p>
          </div>
          <button @click="$emit('close')" class="text-gray-500 hover:text-white transition-colors p-2 hover:bg-gray-800 rounded-full ml-4">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">
          <!-- Main Message -->
          <section>
            <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Mensagem</h4>
            <div class="bg-[#161b22] p-4 rounded-lg border border-gray-800 group relative">
              <pre class="text-sm text-blue-300 whitespace-pre-wrap font-mono break-all leading-relaxed">{{ log.message }}</pre>
              <button @click="copyText(log.message)" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity p-1.5 bg-gray-800 rounded text-gray-400 hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              </button>
            </div>
          </section>

          <!-- Origin Info -->
          <section v-if="log.class || log.method || log.file">
            <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Origem do Erro</h4>
            <div class="bg-gray-900/50 p-4 rounded-lg border border-gray-800 space-y-3">
              <div v-if="log.class" class="flex gap-2">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">Classe:</span>
                <span class="text-xs font-mono text-gray-300 break-all">{{ log.class }}</span>
              </div>
              <div v-if="log.method" class="flex gap-2">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">Metodo:</span>
                <span class="text-xs font-mono text-yellow-400">{{ log.method }}()</span>
              </div>
              <div v-if="log.file" class="flex gap-2">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">Arquivo:</span>
                <span class="text-xs font-mono text-gray-400 break-all">{{ log.file }}<span v-if="log.line" class="text-cyan-400">:{{ log.line }}</span></span>
              </div>
            </div>
          </section>

          <!-- HTTP Info -->
          <section v-if="log.url || log.http_method">
            <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Requisicao HTTP</h4>
            <div class="bg-gray-900/50 p-4 rounded-lg border border-gray-800 space-y-3">
              <div v-if="log.http_method" class="flex gap-2">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">Metodo:</span>
                <span class="text-xs font-mono font-bold" :class="getHttpMethodColor(log.http_method)">{{ log.http_method }}</span>
              </div>
              <div v-if="log.url" class="flex gap-2">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">URL:</span>
                <span class="text-xs font-mono text-blue-400 break-all">{{ log.url }}</span>
              </div>
              <div v-if="log.remote_addr" class="flex gap-2">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">IP:</span>
                <span class="text-xs font-mono text-gray-400">{{ log.remote_addr }}</span>
              </div>
            </div>
          </section>

          <!-- Stack Trace -->
          <section v-if="log.stack_trace">
            <h4 class="text-[10px] font-semibold text-red-400 uppercase tracking-widest mb-2">Stack Trace</h4>
            <div class="bg-red-950/30 p-4 rounded-lg border border-red-900/50 overflow-x-auto custom-scrollbar">
              <pre class="text-[11px] text-red-300 font-mono whitespace-pre-wrap break-all">{{ log.stack_trace }}</pre>
            </div>
          </section>

          <!-- Request Data -->
          <section v-if="log.request_data && log.request_data !== '{}'">
            <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Request Data</h4>
            <div class="bg-[#0b0e14] p-4 rounded-lg border border-gray-800 overflow-x-auto custom-scrollbar">
              <pre class="text-[11px] text-gray-400 font-mono">{{ formatJson(log.request_data) }}</pre>
            </div>
          </section>

          <!-- Context -->
          <section v-if="log.context && log.context !== '{}'">
            <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Contexto Adicional</h4>
            <div class="bg-[#0b0e14] p-4 rounded-lg border border-gray-800 overflow-x-auto custom-scrollbar">
              <pre class="text-[11px] text-gray-400 font-mono">{{ formatJson(log.context) }}</pre>
            </div>
          </section>

          <!-- User Agent -->
          <section v-if="log.user_agent">
            <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">User Agent</h4>
            <p class="text-[11px] text-gray-500 font-mono break-all bg-gray-900/50 p-3 rounded border border-gray-800">{{ log.user_agent }}</p>
          </section>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-800 bg-gray-900/80 flex justify-between items-center shrink-0">
          <button @click="copyJson" class="text-xs font-medium text-gray-400 hover:text-white flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Copiar JSON
          </button>
          <div class="flex gap-3">
             <button @click="$emit('close')" class="px-4 py-1.5 text-xs font-semibold text-gray-300 hover:bg-gray-800 rounded transition-colors border border-gray-700">
              Fechar
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
    error: 'text-orange-400',
    critical: 'text-red-500',
    alert: 'text-red-600',
    emergency: 'text-red-700'
  }
  return colors[level?.toLowerCase()] || 'text-gray-300'
}

const getHttpMethodColor = (method) => {
  const colors = {
    'GET': 'text-green-400',
    'POST': 'text-blue-400',
    'PUT': 'text-yellow-400',
    'PATCH': 'text-orange-400',
    'DELETE': 'text-red-400',
  }
  return colors[method?.toUpperCase()] || 'text-gray-400'
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

const formatJson = (data) => {
  if (!data) return ''
  try {
    if (typeof data === 'string') {
      return JSON.stringify(JSON.parse(data), null, 2)
    }
    return JSON.stringify(data, null, 2)
  } catch {
    return data
  }
}

const copyText = (text) => {
  navigator.clipboard.writeText(text)
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
