<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Icons/Icon.vue'; // You might need to check how icons are handled in this project, using provided SVG handling for now or generic icon wrapper if it matches.
// Since I don't see a generic Icon component in the file list earlier, I'll inline SVGs or use a simple mapping if needed. 
// However, the service returns an 'icon' string. I'll map that to SVGs here.

const props = defineProps({
    results: {
        type: Object,
        default: () => ({}),
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const hasResults = computed(() => {
    return Object.keys(props.results).length > 0;
});

const categoryLabels = {
    rats: 'RATs',
    demandas: 'Demandas',
    orgaos: 'Órgãos',
    processos: 'Processos',
    treinamentos: 'Treinamentos',
};

const getIconPath = (iconName) => {
    // Simple mapping for demo
    const icons = {
        document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        checkbadge: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        building: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        default: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'
    };
    return icons[iconName] || icons.default;
};

</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
    >
        <div
            v-if="show"
            class="absolute top-full left-0 w-full mt-2 bg-white dark:bg-slate-900 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden z-50 max-h-[80vh] overflow-y-auto"
        >
            <!-- Loading State -->
            <div v-if="isLoading" class="p-4 text-center text-slate-500 dark:text-slate-400">
                <svg class="animate-spin h-5 w-5 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Buscando...
            </div>

            <!-- Empty State -->
            <div v-else-if="!hasResults" class="p-8 text-center text-slate-500 dark:text-slate-400">
                <p>Nenhum resultado encontrado.</p>
            </div>

            <!-- Results List -->
            <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                <div v-for="(items, category) in results" :key="category">
                    <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        {{ categoryLabels[category] || category }}
                    </div>
                    <ul>
                        <li v-for="item in items" :key="item.id + item.type">
                            <Link
                                :href="item.url"
                                class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors duration-150"
                                @click="$emit('close')"
                            >
                                <div class="mt-0.5 p-1.5 rounded-md bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconPath(item.icon)" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">
                                        {{ item.title }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                        {{ item.subtitle }}
                                    </div>
                                </div>
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Footer hint -->
            <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 text-xs text-center text-slate-500 dark:text-slate-400">
                Pressione ESC para fechar
            </div>
        </div>
    </transition>
</template>
