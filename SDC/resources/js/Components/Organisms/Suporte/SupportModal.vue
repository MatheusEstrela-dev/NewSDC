<script setup>
import { ref } from 'vue';
import SupportDocs from './SupportDocs.vue';
import SupportTickets from './SupportTickets.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const viewMode = ref('docs'); // 'docs' or 'tickets'

const close = () => {
    emit('close');
    // Reset view mode after transition (optional, but good UX)
    setTimeout(() => {
        viewMode.value = 'docs';
    }, 300); 
};
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="close"></div>

            <!-- Modal Panel -->
            <div class="relative w-full max-w-6xl h-[85vh] transform transition-all shadow-2xl rounded-2xl overflow-hidden ring-1 ring-white/10">
                <Transition
                    enter-active-class="transition ease-in-out duration-300 transform"
                    enter-from-class="translate-x-full opacity-0"
                    enter-to-class="translate-x-0 opacity-100"
                    leave-active-class="transition ease-in-out duration-300 transform"
                    leave-from-class="translate-x-0 opacity-100"
                    leave-to-class="-translate-x-full opacity-0"
                    mode="out-in"
                >
                    <component 
                        :is="viewMode === 'docs' ? SupportDocs : SupportTickets" 
                        @close="close"
                        @open-tickets="viewMode = 'tickets'"
                        @back="viewMode = 'docs'"
                    />
                </Transition>
            </div>
        </div>
    </Transition>
</template>