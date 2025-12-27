<template>
  <!-- Modal Overlay -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click.self="$emit('close')"
      >
        <div class="flex min-h-screen items-center justify-center p-4">
          <!-- Modal Background -->
          <div
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            @click="$emit('close')"
          />

          <!-- Modal Content -->
          <div
            class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden"
          >
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  Detalhes do Log
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ formatTimestamp(log?.timestamp) }}
                </p>
              </div>
              <button
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
              >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-4 overflow-y-auto max-h-[calc(90vh-140px)]">
              <!-- Level Badge -->
              <div class="mb-4">
                <span
                  class="px-3 py-1 text-sm font-medium rounded-full"
                  :class="getLevelClass(log?.level)"
                >
                  {{ log?.level?.toUpperCase() }}
                </span>
              </div>

              <!-- Message -->
              <div class="mb-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Mensagem
                </h4>
                <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                  <pre class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap font-mono">{{ log?.message }}</pre>
                </div>
              </div>

              <!-- Metadata Grid -->
              <div class="grid grid-cols-2 gap-4 mb-6">
                <div v-if="log?.file">
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Arquivo
                  </h4>
                  <p class="text-sm text-gray-900 dark:text-gray-100">
                    {{ log.file }}
                    <span v-if="log.line" class="text-gray-500">:{{ log.line }}</span>
                  </p>
                </div>

                <div v-if="log?.format">
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Formato
                  </h4>
                  <p class="text-sm text-gray-900 dark:text-gray-100">
                    {{ log.format }}
                  </p>
                </div>

                <div v-if="log?.context?.environment">
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Ambiente
                  </h4>
                  <p class="text-sm text-gray-900 dark:text-gray-100">
                    {{ log.context.environment }}
                  </p>
                </div>

                <div v-if="log?.context?.user_id">
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Usuário
                  </h4>
                  <p class="text-sm text-gray-900 dark:text-gray-100">
                    ID: {{ log.context.user_id }}
                  </p>
                </div>

                <div v-if="log?.context?.request_id">
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Request ID
                  </h4>
                  <p class="text-sm text-gray-900 dark:text-gray-100 font-mono text-xs">
                    {{ log.context.request_id }}
                  </p>
                </div>

                <div v-if="log?.context?.ip_address">
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    IP
                  </h4>
                  <p class="text-sm text-gray-900 dark:text-gray-100">
                    {{ log.context.ip_address }}
                  </p>
                </div>
              </div>

              <!-- Request Info -->
              <div v-if="log?.context?.url" class="mb-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Requisição
                </h4>
                <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg space-y-2">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                      {{ log.context.http_method || 'GET' }}
                    </span>
                    <span class="text-sm text-gray-900 dark:text-gray-100 font-mono">
                      {{ log.context.url }}
                    </span>
                  </div>
                  <div v-if="log.context.user_agent" class="text-xs text-gray-500 dark:text-gray-400">
                    {{ log.context.user_agent }}
                  </div>
                </div>
              </div>

              <!-- Stack Trace -->
              <div v-if="log?.context?.stack_trace" class="mb-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Stack Trace
                </h4>
                <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto">
                  <div
                    v-for="(trace, index) in log.context.stack_trace"
                    :key="index"
                    class="mb-2 text-sm"
                  >
                    <div class="text-gray-500 dark:text-gray-400">
                      #{{ index }}
                    </div>
                    <div class="text-gray-900 dark:text-gray-100 font-mono text-xs">
                      {{ trace.class || '' }}{{ trace.class ? '::' : '' }}{{ trace.function }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400 text-xs">
                      {{ trace.file }}:{{ trace.line }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Full Trace (if available) -->
              <div v-if="log?.context?.full_trace" class="mb-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Stack Trace Completo
                </h4>
                <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto">
                  <pre class="text-xs text-gray-900 dark:text-gray-100 whitespace-pre-wrap font-mono">{{ log.context.full_trace }}</pre>
                </div>
              </div>

              <!-- Context JSON -->
              <div v-if="log?.context" class="mb-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Contexto Completo (JSON)
                </h4>
                <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto">
                  <pre class="text-xs text-gray-900 dark:text-gray-100 whitespace-pre-wrap font-mono">{{ JSON.stringify(log.context, null, 2) }}</pre>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
              <button
                @click="copyToClipboard"
                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600"
              >
                Copiar JSON
              </button>
              <button
                @click="$emit('close')"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
              >
                Fechar
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  log: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close'])

const formatTimestamp = (timestamp) => {
  if (!timestamp) return '-'
  const date = new Date(timestamp)
  return date.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

const getLevelClass = (level) => {
  const classes = {
    debug: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    info: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    notice: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300',
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    error: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    critical: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    alert: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    emergency: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
  }
  return classes[level] || classes.info
}

const copyToClipboard = async () => {
  if (!props.log) return

  try {
    await navigator.clipboard.writeText(JSON.stringify(props.log, null, 2))
    alert('JSON copiado para a área de transferência!')
  } catch (err) {
    console.error('Erro ao copiar:', err)
    alert('Erro ao copiar JSON')
  }
}
</script>
