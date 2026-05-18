<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '48',
    },
    contentClasses: {
        type: String,
        default: 'py-1 bg-white dark:bg-gray-700',
    },
    mobileFullWidth: {
        type: Boolean,
        default: false,
    },
    teleport: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['open-change']);

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        close();
    }
};

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
    window.addEventListener('resize', updateFloatingPosition);
    window.addEventListener('scroll', updateFloatingPosition, true);
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    window.removeEventListener('resize', updateFloatingPosition);
    window.removeEventListener('scroll', updateFloatingPosition, true);
});

const widthClass = computed(() => {
    return {
        48: 'w-48',
        80: 'w-80',
        96: 'w-96',
    }[props.width.toString()];
});

const alignmentClasses = computed(() => {
    if (props.align === 'left') {
        return 'ltr:origin-top-left rtl:origin-top-right start-0';
    } else if (props.align === 'right') {
        return 'ltr:origin-top-right rtl:origin-top-left end-0';
    } else {
        return 'origin-top';
    }
});

const open = ref(false);
const triggerRef = ref(null);
const floatingStyle = ref({});

const widthPx = computed(() => {
    return {
        48: 192,
        80: 320,
        96: 384,
    }[props.width.toString()] ?? 192;
});

watch(open, (value) => {
    emit('open-change', value);
    if (value) {
        nextTick(updateFloatingPosition);
    }
});

function toggle() {
    if (open.value) {
        close();
        return;
    }

    open.value = true;
}

function close() {
    open.value = false;
}

function updateFloatingPosition() {
    if (!open.value || !props.teleport || !triggerRef.value) return;

    const rect = triggerRef.value.getBoundingClientRect();
    const viewportPadding = 8;
    const gap = 8;
    const width = widthPx.value;

    let left = rect.right - width;
    if (props.align === 'left') {
        left = rect.left;
    } else if (props.align === 'center') {
        left = rect.left + rect.width / 2 - width / 2;
    }

    left = Math.max(viewportPadding, Math.min(left, window.innerWidth - width - viewportPadding));

    const belowSpace = window.innerHeight - rect.bottom - gap - viewportPadding;
    const aboveSpace = rect.top - gap - viewportPadding;
    const shouldOpenUp = belowSpace < 120 && aboveSpace > belowSpace;

    const nextStyle = {
        position: 'fixed',
        left: `${left}px`,
        width: `${width}px`,
        overflowY: 'auto',
        zIndex: 1000,
    };

    if (shouldOpenUp) {
        nextStyle.bottom = `${window.innerHeight - rect.top + gap}px`;
        nextStyle.maxHeight = `${Math.max(120, aboveSpace)}px`;
    } else {
        const top = rect.bottom + gap;
        nextStyle.top = `${top}px`;
        nextStyle.maxHeight = `${Math.max(120, belowSpace)}px`;
    }

    floatingStyle.value = nextStyle;
}
</script>

<template>
    <div class="relative">
        <div ref="triggerRef" @click="toggle">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div v-if="!teleport" v-show="open" class="fixed inset-0 z-40" @click="close"></div>

        <Transition
            v-if="!teleport"
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                class="z-50 mt-2 rounded-md shadow-lg"
                :class="[
                    mobileFullWidth ? 'dropdown-mobile-full' : 'absolute max-w-[calc(100vw-1rem)]',
                    mobileFullWidth ? '' : widthClass,
                    mobileFullWidth ? '' : alignmentClasses
                ]"
                style="display: none"
                @click="close"
            >
                <div class="rounded-md ring-1 ring-black ring-opacity-5" :class="contentClasses">
                    <slot name="content" />
                </div>
            </div>
        </Transition>

        <Teleport v-if="teleport" to="body">
            <div v-show="open" class="fixed inset-0" style="z-index: 999" @click="close"></div>

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-show="open"
                    class="rounded-md shadow-lg"
                    :style="floatingStyle"
                    @click="close"
                >
                    <div class="rounded-md ring-1 ring-black ring-opacity-5" :class="contentClasses">
                        <slot name="content" />
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.dropdown-mobile-full {
    position: fixed;
    left: 0.5rem;
    right: 0.5rem;
}

@media (min-width: 768px) {
    .dropdown-mobile-full {
        position: absolute;
        left: auto;
        right: 0;
        width: 24rem; /* w-96 */
        max-width: calc(100vw - 1rem);
    }
}
</style>
