import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

// Estado global de loading compartilhado
const isLoading = ref(false)
const loadingMessage = ref('')
const loadingProgress = ref(0)
const minLoadingTime = 500 // ms - tempo mínimo para mostrar skeleton
const loadingStartTime = ref(null)

export function usePageLoading() {
    /**
     * Inicia o estado de loading
     */
    const startLoading = (message = 'Carregando...') => {
        loadingStartTime.value = Date.now()
        isLoading.value = true
        loadingMessage.value = message
        loadingProgress.value = 0
    }

    /**
     * Finaliza o estado de loading respeitando tempo mínimo
     */
    const stopLoading = async () => {
        if (!loadingStartTime.value) {
            isLoading.value = false
            return
        }

        const elapsed = Date.now() - loadingStartTime.value
        const remaining = minLoadingTime - elapsed

        if (remaining > 0) {
            // Aguarda tempo mínimo para evitar flash
            await new Promise(resolve => setTimeout(resolve, remaining))
        }

        isLoading.value = false
        loadingMessage.value = ''
        loadingProgress.value = 0
        loadingStartTime.value = null
    }

    /**
     * Atualiza progresso do loading (0-100)
     */
    const updateProgress = (progress) => {
        loadingProgress.value = Math.min(100, Math.max(0, progress))
    }

    /**
     * Atualiza mensagem de loading
     */
    const updateMessage = (message) => {
        loadingMessage.value = message
    }

    /**
     * Verifica se está carregando
     */
    const loading = computed(() => isLoading.value)

    /**
     * Obtém mensagem atual
     */
    const message = computed(() => loadingMessage.value)

    /**
     * Obtém progresso atual
     */
    const progress = computed(() => loadingProgress.value)

    return {
        isLoading: loading,
        loadingMessage: message,
        loadingProgress: progress,
        startLoading,
        stopLoading,
        updateProgress,
        updateMessage
    }
}

/**
 * Hook para configurar interceptadores globais do Inertia
 * Chame isso no app.js ou em um componente raiz
 */
export function setupInertiaLoadingInterceptors() {
    const { startLoading, stopLoading, updateProgress } = usePageLoading()

    // Evento de início de navegação
    router.on('start', (event) => {
        startLoading('Carregando página...')
    })

    // Evento de progresso
    router.on('progress', (event) => {
        if (event.detail.progress) {
            updateProgress(event.detail.progress.percentage)
        }
    })

    // Evento de finalização (sucesso ou erro)
    router.on('finish', (event) => {
        stopLoading()
    })

    // Evento de erro
    router.on('error', (errors) => {
        stopLoading()
        console.error('Erro ao carregar página:', errors)
    })

    // Evento de exceção
    router.on('exception', (error) => {
        stopLoading()
        console.error('Exceção ao navegar:', error)
    })
}

/**
 * Hook para simular loading em operações assíncronas
 * Útil para chamadas API ou processamento de dados
 */
export function useAsyncLoading() {
    const { startLoading, stopLoading, updateMessage } = usePageLoading()

    /**
     * Executa função com loading
     */
    const withLoading = async (asyncFn, message = 'Processando...') => {
        try {
            startLoading(message)
            const result = await asyncFn()
            return result
        } catch (error) {
            console.error('Erro durante operação assíncrona:', error)
            throw error
        } finally {
            await stopLoading()
        }
    }

    return {
        withLoading,
        startLoading,
        stopLoading,
        updateMessage
    }
}
