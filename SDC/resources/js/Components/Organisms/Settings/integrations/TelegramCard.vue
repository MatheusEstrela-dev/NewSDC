<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useIntegrations } from '@/Composables/useIntegrations';

const { loading, error, connectTelegram, getTelegramStatus, disconnectTelegram } = useIntegrations();

const status = ref('not_connected'); // not_connected | awaiting_pairing | connected
const integration = ref(null);

// Estado do modal de pairing
const showPairingModal = ref(false);
const pairingCode = ref('');
const pairingLink = ref('');
const pollingId = ref(null);

async function refresh() {
    try {
        const data = await getTelegramStatus();
        status.value = data.status;
        integration.value = data.integration || null;
    } catch (e) {
        // mantem estado anterior
    }
}

async function startConnect() {
    try {
        const data = await connectTelegram();
        pairingCode.value = data.code;
        pairingLink.value = data.link;
        showPairingModal.value = true;
        startPolling();
    } catch (e) {
        // error ja capturado em useIntegrations
    }
}

function startPolling() {
    stopPolling();
    pollingId.value = setInterval(async () => {
        await refresh();
        if (status.value === 'connected') {
            stopPolling();
            showPairingModal.value = false;
        }
    }, 3000);
}

function stopPolling() {
    if (pollingId.value) {
        clearInterval(pollingId.value);
        pollingId.value = null;
    }
}

function closeModal() {
    showPairingModal.value = false;
    stopPolling();
}

async function disconnect() {
    if (!integration.value?.id) {
        return;
    }
    if (!confirm('Desconectar conta Telegram? Voce parara de receber notificacoes neste canal.')) {
        return;
    }
    try {
        await disconnectTelegram(integration.value.id);
        status.value = 'not_connected';
        integration.value = null;
    } catch (e) {
        // mantem estado
    }
}

onMounted(refresh);
onUnmounted(stopPolling);
</script>

<template>
    <div class="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-xl">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-sky-500 rounded-lg flex items-center justify-center text-white font-bold text-xl">T</div>
            <div>
                <h4 class="font-bold text-slate-900 dark:text-white">Telegram</h4>
                <p v-if="status === 'connected'" class="text-sm text-emerald-600 dark:text-emerald-400">
                    Conectado{{ integration?.display_name ? ' ('+integration.display_name+')' : '' }}
                </p>
                <p v-else-if="status === 'awaiting_pairing'" class="text-sm text-amber-600 dark:text-amber-400">
                    Aguardando confirmacao no Telegram...
                </p>
                <p v-else class="text-sm text-slate-500">
                    Receber alertas de RAT, decretos e exports diretamente.
                </p>
            </div>
        </div>

        <button
            v-if="status === 'connected'"
            type="button"
            class="px-4 py-2 text-sm font-medium border border-rose-300 dark:border-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20 text-rose-700 dark:text-rose-300"
            :disabled="loading"
            @click="disconnect"
        >
            Desconectar
        </button>

        <button
            v-else
            type="button"
            class="px-4 py-2 text-sm font-medium border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"
            :disabled="loading"
            @click="startConnect"
        >
            Conectar
        </button>
    </div>

    <!-- Modal de pairing -->
    <div
        v-if="showPairingModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
        @click.self="closeModal"
    >
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-xl max-w-md w-full mx-4 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Conectar Telegram</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" @click="closeModal">&times;</button>
            </div>

            <ol class="space-y-3 text-sm text-slate-700 dark:text-slate-300">
                <li><strong>1.</strong> Clique no link abaixo para abrir o bot no Telegram:</li>
            </ol>

            <a
                :href="pairingLink"
                target="_blank"
                rel="noopener"
                class="block w-full text-center px-4 py-3 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg"
            >
                Abrir bot no Telegram
            </a>

            <p class="text-xs text-slate-500">
                Ou envie ao bot manualmente:
                <code class="bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded font-mono">/start {{ pairingCode }}</code>
            </p>

            <p class="text-xs text-slate-500">
                Aguardando confirmacao... (codigo expira em 10 minutos)
            </p>

            <button
                type="button"
                class="w-full px-4 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800"
                @click="closeModal"
            >
                Cancelar
            </button>
        </div>
    </div>
</template>
