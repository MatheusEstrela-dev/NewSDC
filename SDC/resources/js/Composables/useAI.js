import { ref } from 'vue'
import axios from 'axios'

export function useAI() {
    const plugins = ref([])
    const loading = ref(false)
    const error = ref(null)

    const fetchPlugins = async () => {
        loading.value = true
        try {
            const response = await axios.get(route('ia.plugins.index'))
            plugins.value = response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Erro ao carregar plugins de IA'
        } finally {
            loading.value = false
        }
    }

    const executePlugin = async (pluginName, payload) => {
        loading.value = true
        error.value = null
        try {
            const response = await axios.post(route('ia.execute-plugin'), {
                plugin: pluginName,
                payload: payload
            })
            return response.data
        } catch (err) {
            error.value = err.response?.data?.error || 'Erro ao executar plugin de IA'
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        plugins,
        loading,
        error,
        fetchPlugins,
        executePlugin
    }
}
