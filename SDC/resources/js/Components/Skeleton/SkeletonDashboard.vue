<template>
    <div class="skeleton-dashboard space-y-6">
        <!-- Metrics Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
                v-for="i in 4"
                :key="`metric-${i}`"
                class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md"
            >
                <!-- Icon -->
                <div class="flex items-center justify-between mb-4">
                    <SkeletonBase
                        shape="circle"
                        width="48px"
                        height="48px"
                        :animation="animation"
                    />
                    <SkeletonBase
                        shape="rectangle"
                        width="60px"
                        height="24px"
                        rounded="full"
                        :animation="animation"
                    />
                </div>

                <!-- Value -->
                <SkeletonBase
                    shape="line"
                    width="60%"
                    height="32px"
                    rounded="md"
                    :animation="animation"
                    custom-class="mb-2"
                />

                <!-- Label -->
                <SkeletonBase
                    shape="line"
                    width="80%"
                    height="16px"
                    rounded="md"
                    :animation="animation"
                />
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Table (2/3 width) -->
            <div class="lg:col-span-2">
                <SkeletonTable
                    :columns="5"
                    :rows="6"
                    :show-pagination="true"
                    :animation="animation"
                />
            </div>

            <!-- Sidebar Timeline (1/3 width) -->
            <div class="space-y-4">
                <!-- Timeline Header -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-md">
                    <SkeletonBase
                        shape="line"
                        width="70%"
                        height="24px"
                        rounded="md"
                        :animation="animation"
                        custom-class="mb-4"
                    />

                    <!-- Timeline Items -->
                    <div class="space-y-4">
                        <div
                            v-for="i in 4"
                            :key="`timeline-${i}`"
                            class="flex gap-3"
                        >
                            <SkeletonBase
                                shape="circle"
                                width="32px"
                                height="32px"
                                :animation="animation"
                            />
                            <div class="flex-1 space-y-2">
                                <SkeletonBase
                                    shape="line"
                                    width="90%"
                                    height="16px"
                                    rounded="md"
                                    :animation="animation"
                                />
                                <SkeletonBase
                                    shape="line"
                                    width="60%"
                                    height="12px"
                                    rounded="md"
                                    :animation="animation"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import SkeletonBase from './SkeletonBase.vue'
import SkeletonTable from './SkeletonTable.vue'

defineProps({
    animation: {
        type: String,
        default: 'pulse'
    }
})
</script>
