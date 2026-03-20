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
              {{ formatFullDate(log.timestamp || log.created_at) }}
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

          <!-- SQL Query Detail -->
          <section v-if="isQueryLog">
            <h4 class="text-[10px] font-semibold text-cyan-500 uppercase tracking-widest mb-2">Query SQL</h4>
            <div class="bg-[#0d1117] rounded-lg border border-cyan-900/40 overflow-hidden">
              <!-- Execution time gauge -->
              <div class="px-4 py-3 border-b border-gray-800 flex items-center gap-4 flex-wrap">
                <div class="flex items-center gap-2">
                  <span class="text-[10px] text-gray-500 uppercase">Tempo:</span>
                  <span class="text-sm font-mono font-bold" :class="timeSeverity(log.time_ms, log.threshold_ms).text">
                    {{ formatMs(log.time_ms) }}
                  </span>
                  <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold"
                    :class="timeSeverity(log.time_ms, log.threshold_ms).bar.replace('500','900/50').replace('600','900/50') + ' ' + timeSeverity(log.time_ms, log.threshold_ms).text">
                    {{ timeSeverity(log.time_ms, log.threshold_ms).label }}
                  </span>
                </div>
                <div class="flex-1 h-1.5 bg-gray-800 rounded-full overflow-hidden min-w-[60px]">
                  <div class="h-full rounded-full transition-all"
                    :class="timeSeverity(log.time_ms, log.threshold_ms).bar"
                    :style="{ width: Math.min(100, (log.time_ms / (log.threshold_ms || 2000)) * 50) + '%' }">
                  </div>
                </div>
                <span v-if="log.threshold_ms" class="text-[10px] text-gray-600 font-mono">
                  threshold: {{ log.threshold_ms }}ms
                </span>
                <span v-if="log.query_type"
                  class="text-[10px] px-2 py-0.5 rounded font-bold uppercase"
                  :class="queryTypeBadge(log.query_type)">
                  {{ log.query_type }}
                </span>
                <span v-if="log.connection" class="text-[10px] text-gray-600 font-mono">
                  {{ log.connection }}
                </span>
              </div>
              <!-- SQL with syntax highlight -->
              <div class="relative group p-4">
                <pre class="text-[11px] font-mono text-gray-300 whitespace-pre-wrap break-all leading-relaxed sql-code"
                  v-html="highlightSql(log.sql)"></pre>
                <button @click="copyText(log.sql)"
                  class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 p-1.5 bg-gray-800 rounded text-gray-400 hover:text-white transition-opacity">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                  </svg>
                </button>
              </div>
              <!-- Resolved SQL -->
              <div v-if="log.bindings?.length" class="border-t border-gray-800 p-4">
                <span class="text-[10px] text-gray-500 uppercase tracking-widest block mb-2">SQL Resolvido</span>
                <pre class="text-[11px] font-mono text-green-300/80 whitespace-pre-wrap break-all">{{ resolvedSql }}</pre>
              </div>
              <!-- Bindings table -->
              <div v-if="log.bindings?.length" class="border-t border-gray-800 p-4">
                <span class="text-[10px] text-gray-500 uppercase tracking-widest block mb-2">Bindings ({{ log.bindings.length }})</span>
                <div class="flex flex-wrap gap-2">
                  <span v-for="(b, i) in log.bindings" :key="i"
                    class="text-[10px] font-mono bg-gray-800 px-2 py-0.5 rounded text-yellow-300">
                    <span class="text-gray-500">?{{ i + 1 }}</span> = {{ b === null ? 'NULL' : b }}
                  </span>
                </div>
              </div>
            </div>
          </section>

          <!-- Origin Info -->
          <section v-if="log.class || log.method || log.file || log.file_path || log.layer">
            <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Origem do Erro</h4>
            <div class="bg-gray-900/50 p-4 rounded-lg border border-gray-800 space-y-3">
              <div v-if="log.layer" class="flex gap-2 items-center">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">Camada:</span>
                <span class="text-xs font-mono px-2 py-0.5 rounded" :class="getLayerBadge(log.layer)">{{ log.layer }}</span>
              </div>
              <div v-if="log.class" class="flex gap-2">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">Classe:</span>
                <span class="text-xs font-mono text-gray-300 break-all">{{ log.class }}</span>
              </div>
              <div v-if="log.method" class="flex gap-2">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">Metodo:</span>
                <span class="text-xs font-mono text-yellow-400">{{ log.method }}()</span>
              </div>
              <div v-if="log.file_path || log.file" class="flex gap-2">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">Arquivo:</span>
                <a
                  :href="getVSCodeLink(log)"
                  class="text-xs font-mono text-cyan-400 hover:text-cyan-300 hover:underline break-all cursor-pointer"
                >
                  {{ log.file_path || log.file }}
                  <span v-if="log.line" class="text-yellow-400">:{{ log.line }}</span>
                </a>
              </div>
              <div v-if="log.request_id" class="flex gap-2">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">Request:</span>
                <span class="text-xs font-mono text-purple-400">{{ log.request_id }}</span>
              </div>
              <div v-if="log.hostname" class="flex gap-2">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">Host:</span>
                <span class="text-xs font-mono text-gray-400">{{ log.hostname }}</span>
              </div>
              <div v-if="log.user_id" class="flex gap-2">
                <span class="text-[10px] text-gray-500 w-16 shrink-0">User ID:</span>
                <span class="text-xs font-mono text-orange-400">{{ log.user_id }}</span>
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

          <!-- Stack Frames (structured) -->
          <section v-if="isCriticalLog && log.stack_frames?.length">
            <h4 class="text-[10px] font-semibold text-red-400 uppercase tracking-widest mb-2">
              Stack Trace ({{ log.stack_frames.length }} frames)
            </h4>
            <div class="bg-red-950/20 rounded-lg border border-red-900/40 overflow-hidden">
              <div v-if="log.exception_class" class="px-4 py-3 bg-red-950/40 border-b border-red-900/40 flex items-center gap-2">
                <span class="text-[10px] font-mono text-red-300 font-bold">{{ shortExceptionClass(log.exception_class) }}</span>
                <span class="text-[10px] text-gray-600 font-mono truncate">{{ log.exception_class }}</span>
              </div>
              <div class="divide-y divide-red-900/20 max-h-64 overflow-y-auto custom-scrollbar">
                <div v-for="(frame, i) in log.stack_frames" :key="i"
                  class="px-4 py-2 flex items-start gap-3 hover:bg-red-950/20 transition-colors">
                  <span class="text-[10px] text-gray-600 w-6 shrink-0 font-mono mt-0.5">#{{ i }}</span>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                      <span v-if="frame.class" class="text-[11px] font-mono text-orange-300 truncate">
                        {{ shortExceptionClass(frame.class) }}
                      </span>
                      <span v-if="frame.function" class="text-[11px] font-mono text-yellow-300">
                        .{{ frame.function }}()
                      </span>
                    </div>
                    <span class="text-[10px] font-mono text-gray-500">
                      {{ frame.file }}<span v-if="frame.line" class="text-cyan-600">:{{ frame.line }}</span>
                    </span>
                  </div>
                </div>
              </div>
              <div v-if="log.previous_exception" class="px-4 py-3 border-t border-red-900/40 bg-red-950/20">
                <span class="text-[10px] text-red-400 uppercase tracking-widest block mb-1">Caused By</span>
                <span class="text-[11px] font-mono text-red-300">{{ log.previous_exception.class }}</span>
                <p class="text-[10px] text-gray-400 mt-0.5">{{ log.previous_exception.message }}</p>
              </div>
            </div>
          </section>

          <!-- System Metrics -->
          <section v-if="log.system_metrics">
            <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">System Metrics</h4>
            <div class="grid grid-cols-2 gap-2">
              <div class="bg-gray-900/50 px-3 py-2 rounded border border-gray-800">
                <span class="text-[9px] text-gray-600 uppercase block">Memoria</span>
                <span class="text-xs font-mono text-gray-300">{{ log.system_metrics.memory_usage_mb }}MB</span>
                <span class="text-[10px] text-gray-600"> / {{ log.system_metrics.memory_limit }}</span>
              </div>
              <div class="bg-gray-900/50 px-3 py-2 rounded border border-gray-800">
                <span class="text-[9px] text-gray-600 uppercase block">Pico</span>
                <span class="text-xs font-mono text-gray-300">{{ log.system_metrics.memory_peak_mb }}MB</span>
              </div>
              <div class="bg-gray-900/50 px-3 py-2 rounded border border-gray-800 col-span-2">
                <span class="text-[9px] text-gray-600 uppercase block">PHP</span>
                <span class="text-xs font-mono text-gray-300">{{ log.system_metrics.php_version }}</span>
              </div>
            </div>
          </section>

          <!-- Stack Trace raw (fallback) -->
          <section v-if="log.stack_trace && !isCriticalLog">
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
import { computed } from 'vue'

const props = defineProps({
  show: Boolean,
  log: Object,
})

const emit = defineEmits(['close'])

const isQueryLog = computed(() => props.log?.log_type === 'query' || !!props.log?.sql)

const isCriticalLog = computed(() => props.log?.log_type === 'critical' || !!props.log?.stack_frames)

const formatMs = (ms) => {
  if (ms === null || ms === undefined) return '?'
  if (ms >= 1000) return (ms / 1000).toFixed(2) + 's'
  return Math.round(ms) + 'ms'
}

const timeSeverity = (ms, threshold) => {
  if (!ms) return { bar: 'bg-gray-600', text: 'text-gray-400', label: '-' }
  const t = threshold || 2000
  if (ms < t / 2)   return { bar: 'bg-green-500',  text: 'text-green-400',  label: 'OK' }
  if (ms < t)       return { bar: 'bg-yellow-500', text: 'text-yellow-400', label: 'LENTO' }
  if (ms < t * 2)   return { bar: 'bg-orange-500', text: 'text-orange-400', label: 'CRITICO' }
  return                   { bar: 'bg-red-600',    text: 'text-red-400',    label: 'TIMEOUT' }
}

const highlightSql = (sql) => {
  if (!sql) return ''
  const keywords = ['SELECT','FROM','WHERE','JOIN','LEFT JOIN','RIGHT JOIN','INNER JOIN','ON','AS',
    'GROUP BY','ORDER BY','HAVING','LIMIT','OFFSET','INSERT INTO','VALUES','UPDATE','SET',
    'DELETE FROM','DROP','CREATE','ALTER','TRUNCATE','AND','OR','NOT','IN','LIKE',
    'IS NULL','IS NOT NULL','EXISTS','DISTINCT','COUNT','SUM','AVG','MAX','MIN']
  let result = sql.replace(/</g, '&lt;').replace(/>/g, '&gt;')
  keywords.forEach(kw => {
    const re = new RegExp(`\\b(${kw})\\b`, 'gi')
    result = result.replace(re, `<span class="sql-keyword">$1</span>`)
  })
  return result
}

const resolvedSql = computed(() => {
  const sql = props.log?.sql
  const bindings = props.log?.bindings
  if (!sql || !bindings?.length) return sql
  let i = 0
  return sql.replace(/\?/g, () => {
    const val = bindings[i++]
    if (val === null) return 'NULL'
    if (typeof val === 'number') return String(val)
    return `'${String(val).replace(/'/g, "''")}'`
  })
})

const queryTypeBadge = (type) => {
  const map = {
    'SELECT':   'bg-blue-900/50 text-blue-300',
    'INSERT':   'bg-green-900/50 text-green-300',
    'UPDATE':   'bg-yellow-900/50 text-yellow-300',
    'DELETE':   'bg-red-900/50 text-red-300',
    'DROP':     'bg-red-900/70 text-red-200 font-bold',
    'TRUNCATE': 'bg-red-900/70 text-red-200',
  }
  return map[type] || 'bg-gray-700 text-gray-400'
}

const shortExceptionClass = (cls) => cls ? cls.split('\\').pop() : null

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
  return colors[String(level ?? '').toLowerCase()] || 'text-gray-300'
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

const getLayerBadge = (layer) => {
  const badges = {
    'Controller': 'bg-blue-900/50 text-blue-300',
    'Service': 'bg-purple-900/50 text-purple-300',
    'Repository': 'bg-green-900/50 text-green-300',
    'Model': 'bg-yellow-900/50 text-yellow-300',
    'Middleware': 'bg-orange-900/50 text-orange-300',
    'Request': 'bg-cyan-900/50 text-cyan-300',
    'Job': 'bg-pink-900/50 text-pink-300',
    'Command': 'bg-indigo-900/50 text-indigo-300',
    'Event': 'bg-teal-900/50 text-teal-300',
    'Listener': 'bg-lime-900/50 text-lime-300',
    'System': 'bg-gray-700 text-gray-400',
  }
  return badges[layer] || 'bg-gray-700 text-gray-400'
}

const getVSCodeLink = (log) => {
  const filePath = log.file_path || log.file
  const line = log.line || 1
  if (!filePath) return '#'
  return `vscode://file/${filePath}:${line}`
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
.sql-code :deep(.sql-keyword) {
  color: #67e8f9;
  font-weight: 600;
}
</style>
