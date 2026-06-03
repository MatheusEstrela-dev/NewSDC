import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useActivityFeed() {
    const items = ref([]);
    const isLoading = ref(false);
    const updateMode = ref('polling');
    let pollInterval = null;
    let echoChannel = null;

    const page = usePage();

    async function fetchFeed() {
        isLoading.value = true;
        try {
            const response = await window.axios.get('/api/v1/activity-feed');
            items.value = Array.isArray(response.data.items) ? response.data.items : [];
            updateMode.value = response.data.update_mode ?? 'polling';
        } catch (e) {
            items.value = [];
        } finally {
            isLoading.value = false;
        }
    }

    function startRealtime(userId) {
        if (!window.Echo) return;

        echoChannel = window.Echo.private(`user.${userId}`)
            .listen('UserActivityEvent', (event) => {
                if (event.item) {
                    items.value = [event.item, ...items.value].slice(0, 7);
                }
            });
    }

    function stopRealtime() {
        if (echoChannel) {
            echoChannel.stopListening('UserActivityEvent');
            echoChannel = null;
        }
    }

    onMounted(async () => {
        await fetchFeed();

        const userId = page.props?.auth?.user?.id;
        const mode = page.props?.auth?.user?.notification_update_mode ?? 'polling';
        updateMode.value = mode;

        if (mode === 'realtime' && userId && window.Echo) {
            startRealtime(userId);
        } else {
            pollInterval = setInterval(fetchFeed, 60_000);
        }
    });

    onUnmounted(() => {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
        stopRealtime();
    });

    return { items, isLoading, updateMode, refresh: fetchFeed };
}
