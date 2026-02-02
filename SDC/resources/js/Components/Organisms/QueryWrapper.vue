<template>
    <div>
        <slot v-if="isSuccess" :data="data" />

        <component
            v-else-if="isLoading || isFetching"
            :is="skeletonComponent"
            v-bind="skeletonProps"
        />

        <div
            v-else-if="isError"
            class="rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4"
        >
            <div class="flex items-center gap-2 text-red-700 dark:text-red-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium">{{ errorMessage }}</span>
            </div>
            <button
                v-if="showRetry"
                @click="refetch"
                class="mt-3 px-4 py-2 text-sm font-medium text-red-700 dark:text-red-400 bg-red-100 dark:bg-red-900/40 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/60 transition-colors"
            >
                Tentar novamente
            </button>
        </div>

        <div
            v-else-if="isEmpty"
            class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-8 text-center"
        >
            <slot name="empty">
                <p class="text-gray-500 dark:text-gray-400">{{ emptyMessage }}</p>
            </slot>
        </div>
    </div>
</template>

<script setup>
import { computed, markRaw } from 'vue';
import TableSkeleton from '@/Components/Molecules/Skeleton/TableSkeleton.vue';
import CardSkeleton from '@/Components/Molecules/Skeleton/CardSkeleton.vue';
import StatsSkeleton from '@/Components/Molecules/Skeleton/StatsSkeleton.vue';
import FormSkeleton from '@/Components/Molecules/Skeleton/FormSkeleton.vue';

const props = defineProps({
    isLoading: {
        type: Boolean,
        default: false
    },
    isFetching: {
        type: Boolean,
        default: false
    },
    isSuccess: {
        type: Boolean,
        default: false
    },
    isError: {
        type: Boolean,
        default: false
    },
    data: {
        type: [Object, Array],
        default: null
    },
    error: {
        type: [Object, Error],
        default: null
    },
    refetch: {
        type: Function,
        default: () => {}
    },
    skeleton: {
        type: String,
        default: 'card',
        validator: (value) => ['table', 'card', 'stats', 'form'].includes(value)
    },
    skeletonProps: {
        type: Object,
        default: () => ({})
    },
    emptyMessage: {
        type: String,
        default: 'Nenhum dado encontrado.'
    },
    showRetry: {
        type: Boolean,
        default: true
    },
    emptyCheck: {
        type: Function,
        default: (data) => {
            if (Array.isArray(data)) return data.length === 0;
            if (data?.data && Array.isArray(data.data)) return data.data.length === 0;
            return !data;
        }
    }
});

const skeletonComponents = {
    table: markRaw(TableSkeleton),
    card: markRaw(CardSkeleton),
    stats: markRaw(StatsSkeleton),
    form: markRaw(FormSkeleton),
};

const skeletonComponent = computed(() => skeletonComponents[props.skeleton]);

const errorMessage = computed(() => {
    if (props.error?.response?.data?.message) {
        return props.error.response.data.message;
    }
    if (props.error?.message) {
        return props.error.message;
    }
    return 'Ocorreu um erro ao carregar os dados.';
});

const isEmpty = computed(() => {
    if (!props.isSuccess || !props.data) return false;
    return props.emptyCheck(props.data);
});
</script>
