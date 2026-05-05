<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const toasts = ref([]);
let idCounter = 0;

function addToast({ message, type = 'info', duration = 4000 }) {
    const id = ++idCounter;
    toasts.value.push({ id, message, type, duration });

    if (duration > 0) {
        setTimeout(() => removeToast(id), duration);
    }
}

function removeToast(id) {
    const idx = toasts.value.findIndex((t) => t.id === id);
    if (idx !== -1) toasts.value.splice(idx, 1);
}

// Global event bus: dispatch('toast', { message, type, duration })
function onToastEvent(e) {
    addToast(e.detail ?? {});
}

onMounted(() => window.addEventListener('toast', onToastEvent));
onUnmounted(() => window.removeEventListener('toast', onToastEvent));

// Expose helper so child components can use window.dispatchEvent(new CustomEvent('toast', {...}))
</script>

<template>
    <Teleport to="body">
        <div
            aria-live="polite"
            aria-atomic="false"
            class="toast-container"
        >
            <TransitionGroup name="toast" tag="div" class="toast-list">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="toast"
                    :class="`toast--${toast.type}`"
                    role="alert"
                >
                    <span class="toast__icon">
                        <svg v-if="toast.type === 'success'" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                        <svg v-else-if="toast.type === 'error'" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                        <svg v-else-if="toast.type === 'warning'" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                        <svg v-else viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/></svg>
                    </span>
                    <span class="toast__message">{{ toast.message }}</span>
                    <button class="toast__close" @click="removeToast(toast.id)" aria-label="Fechar">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-container {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    pointer-events: none;
    max-width: 380px;
    width: calc(100vw - 2rem);
}

.toast-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.toast {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.75rem 1rem;
    border-radius: 0.625rem;
    font-size: 0.875rem;
    font-weight: 500;
    box-shadow: 0 4px 16px rgba(0,0,0,.12);
    pointer-events: all;
    backdrop-filter: blur(8px);
    border: 1px solid transparent;
}

.toast--info    { background: rgba(59,130,246,.92);  color: #fff; border-color: rgba(255,255,255,.15); }
.toast--success { background: rgba(22,163,74,.92);   color: #fff; border-color: rgba(255,255,255,.15); }
.toast--warning { background: rgba(234,179,8,.92);   color: #fff; border-color: rgba(255,255,255,.15); }
.toast--error   { background: rgba(220,38,38,.92);   color: #fff; border-color: rgba(255,255,255,.15); }

.toast__icon { flex-shrink: 0; opacity: .9; }
.toast__message { flex: 1; line-height: 1.4; }
.toast__close {
    flex-shrink: 0;
    background: none;
    border: none;
    color: inherit;
    opacity: .7;
    cursor: pointer;
    padding: 2px;
    border-radius: 4px;
    transition: opacity .15s;
}
.toast__close:hover { opacity: 1; }

/* Transition */
.toast-enter-active { transition: all .25s ease; }
.toast-leave-active { transition: all .2s ease; }
.toast-enter-from   { opacity: 0; transform: translateX(1rem) scale(.97); }
.toast-leave-to     { opacity: 0; transform: translateX(1rem) scale(.97); }
</style>
