import { ref } from 'vue';
import axios from 'axios';

/**
 * Composable para gerenciar integracoes pessoais do usuario (Telegram).
 * Espelha os endpoints da Fase 1 backend (TelegramController).
 */
export function useIntegrations() {
    const loading = ref(false);
    const error = ref(null);

    /**
     * Inicia pairing Telegram. Retorna { code, link, expires_in_seconds }.
     */
    async function connectTelegram() {
        loading.value = true;
        error.value = null;
        try {
            const res = await axios.post('/api/v1/integrations/telegram/connect');
            return res.data;
        } catch (e) {
            error.value = e?.response?.data?.message || e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Consulta status atual. Retorna { status: 'not_connected'|'awaiting_pairing'|'connected', integration? }.
     */
    async function getTelegramStatus() {
        try {
            const res = await axios.get('/api/v1/integrations/telegram/status');
            return res.data;
        } catch (e) {
            error.value = e?.response?.data?.message || e.message;
            throw e;
        }
    }

    /**
     * Soft disconnect: marca is_active=false.
     */
    async function disconnectTelegram(integrationId) {
        loading.value = true;
        error.value = null;
        try {
            await axios.delete(`/api/v1/integrations/telegram/${integrationId}`);
        } catch (e) {
            error.value = e?.response?.data?.message || e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return {
        loading,
        error,
        connectTelegram,
        getTelegramStatus,
        disconnectTelegram,
    };
}
