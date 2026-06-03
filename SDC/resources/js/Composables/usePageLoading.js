import { ref } from 'vue';

const FLICKER_DELAY_MS = 300;
const MAX_LOADING_MS = 15000;

const isPageLoading = ref(false);
let pendingTimer = null;
let safetyTimer = null;

function clearSafetyTimer() {
    if (safetyTimer !== null) {
        clearTimeout(safetyTimer);
        safetyTimer = null;
    }
}

export function startLoading() {
    if (pendingTimer !== null) return;
    pendingTimer = setTimeout(() => {
        isPageLoading.value = true;
        pendingTimer = null;
        clearSafetyTimer();
        safetyTimer = setTimeout(() => {
            isPageLoading.value = false;
            safetyTimer = null;
        }, MAX_LOADING_MS);
    }, FLICKER_DELAY_MS);
}

export function stopLoading() {
    if (pendingTimer !== null) {
        clearTimeout(pendingTimer);
        pendingTimer = null;
    }
    clearSafetyTimer();
    isPageLoading.value = false;
}

export function usePageLoading() {
    return { isPageLoading, startLoading, stopLoading };
}
