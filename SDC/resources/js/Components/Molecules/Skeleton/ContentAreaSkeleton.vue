<template>
    <div class="space-y-6 pb-6" aria-busy="true" aria-live="polite">
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4 flex-1 min-w-0">
                <SkeletonBase width="3.5rem" height="3.5rem" rounded="lg" />
                <div class="flex-1 min-w-0 space-y-2">
                    <SkeletonBase width="40%" height="1.5rem" />
                    <SkeletonBase width="65%" height="0.875rem" />
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <SkeletonBase width="5rem" height="2.25rem" rounded="lg" />
                <SkeletonBase width="6.5rem" height="2.25rem" rounded="lg" />
                <SkeletonBase width="8.5rem" height="2.25rem" rounded="lg" />
            </div>
        </div>

        <div class="grid gap-4 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
            <div
                v-for="i in 5"
                :key="`kpi-${i}`"
                class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-4"
            >
                <div class="flex items-start justify-between mb-3">
                    <SkeletonBase width="55%" height="0.875rem" />
                    <SkeletonBase width="2rem" height="2rem" rounded="lg" />
                </div>
                <SkeletonBase width="35%" height="2rem" custom-class="mb-3" />
                <div class="flex gap-3">
                    <div class="flex-1 space-y-1">
                        <SkeletonBase width="40%" height="0.625rem" />
                        <SkeletonBase width="55%" height="0.875rem" />
                    </div>
                    <div class="flex-1 space-y-1">
                        <SkeletonBase width="40%" height="0.625rem" />
                        <SkeletonBase width="55%" height="0.875rem" />
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl px-4 py-3 flex items-center justify-between">
            <SkeletonBase width="12rem" height="1.25rem" />
            <SkeletonBase width="1.5rem" height="1.5rem" rounded="full" />
        </div>

        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden">
            <div class="px-4 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between gap-4">
                <div class="space-y-2 flex-1 min-w-0">
                    <SkeletonBase width="14rem" height="1.125rem" />
                    <SkeletonBase width="10rem" height="0.75rem" />
                </div>
                <SkeletonBase width="3rem" height="1.5rem" rounded="full" />
            </div>
            <div class="bg-gray-50 dark:bg-slate-800/50 px-4 py-3 border-b border-gray-200 dark:border-slate-800 hidden sm:block">
                <div class="flex gap-4">
                    <SkeletonBase
                        v-for="(w, i) in columnWidths"
                        :key="`th-${i}`"
                        :width="w"
                        height="0.75rem"
                    />
                </div>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-slate-800">
                <div
                    v-for="row in 8"
                    :key="`tr-${row}`"
                    class="px-4 py-4"
                >
                    <div class="hidden sm:flex gap-4 items-center">
                        <SkeletonBase
                            v-for="(w, i) in columnWidths"
                            :key="`td-${row}-${i}`"
                            :width="w"
                            height="0.875rem"
                        />
                    </div>
                    <div class="sm:hidden space-y-2">
                        <SkeletonBase width="60%" height="0.875rem" />
                        <SkeletonBase width="40%" height="0.75rem" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import SkeletonBase from '@/Components/Atoms/Skeleton/SkeletonBase.vue';

const columnWidths = ['10%', '12%', '20%', '12%', '16%', '20%', '10%'];
</script>
