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
    // Renderiza o menu via Teleport para o body com posicao fixa ancorada no
    // gatilho. Necessario quando o Dropdown vive dentro de um container com
    // overflow (ex.: tabelas), que recortaria o menu absoluto.
    teleport: {
        type: Boolean,
        default: false,
    },
});

const open = ref(false);

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    removeReposition();
});

const widthClass = computed(() => {
    return {
        48: 'w-48',
        80: 'w-80',
        96: 'w-96',
    }[props.width.toString()];
});

// ===== Teleport: posicao fixa ancorada no gatilho =====
const triggerWrap = ref(null);
const floatStyle = ref({});

const widthPx = computed(() => ({ 48: 192, 80: 320, 96: 384 }[props.width.toString()] ?? 192));

function computePosition() {
    const el = triggerWrap.value;
    if (!el) return;
    const rect = el.getBoundingClientRect();
    const left = props.align === 'left'
        ? rect.left
        : rect.right - widthPx.value;
    floatStyle.value = {
        position: 'fixed',
        top: `${rect.bottom + 8}px`,
        left: `${Math.max(8, left)}px`,
        zIndex: 9999,
    };
}

function addReposition() {
    window.addEventListener('scroll', computePosition, true);
    window.addEventListener('resize', computePosition);
}

function removeReposition() {
    window.removeEventListener('scroll', computePosition, true);
    window.removeEventListener('resize', computePosition);
}

watch(open, async (isOpen) => {
    if (!props.teleport) return;
    if (isOpen) {
        await nextTick();
        computePosition();
        addReposition();
    } else {
        removeReposition();
    }
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
</script>

<template>
    <div class="relative" ref="triggerWrap">
        <div @click="open = !open">
            <slot name="trigger" />
        </div>

        <!-- ===== Modo teleport: escapa de containers com overflow ===== -->
        <Teleport v-if="teleport" to="body">
            <div v-show="open" class="fixed inset-0 z-[9998]" @click="open = false"></div>

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
                    :style="floatStyle"
                    class="rounded-md shadow-lg"
                    :class="[widthClass, 'max-w-[calc(100vw-1rem)]']"
                    @click="open = false"
                >
                    <div class="rounded-md ring-1 ring-black ring-opacity-5" :class="contentClasses">
                        <slot name="content" />
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ===== Modo padrao (absolute) ===== -->
        <template v-else>
            <!-- Full Screen Dropdown Overlay -->
            <div v-show="open" class="fixed inset-0 z-40" @click="open = false"></div>

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
                    class="z-50 mt-2 rounded-md shadow-lg"
                    :class="[
                        mobileFullWidth ? 'dropdown-mobile-full' : 'absolute max-w-[calc(100vw-1rem)]',
                        mobileFullWidth ? '' : widthClass,
                        mobileFullWidth ? '' : alignmentClasses
                    ]"
                    style="display: none"
                    @click="open = false"
                >
                    <div class="rounded-md ring-1 ring-black ring-opacity-5" :class="contentClasses">
                        <slot name="content" />
                    </div>
                </div>
            </Transition>
        </template>
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
