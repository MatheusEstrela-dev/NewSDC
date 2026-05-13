import { ref } from 'vue';

const FLICKER_DELAY_MS = 300;

const isPageLoading = ref(false);
let pendingTimer = null;

export function startLoading() {
    if (pendingTimer !== null) return;
    pendingTimer = setTimeout(() => {
        isPageLoading.value = true;
        pendingTimer = null;
    }, FLICKER_DELAY_MS);
}

export function stopLoading() {
    if (pendingTimer !== null) {
        clearTimeout(pendingTimer);
        pendingTimer = null;
    }
    isPageLoading.value = false;
}

export function usePageLoading() {
    return { isPageLoading, startLoading, stopLoading };
}
