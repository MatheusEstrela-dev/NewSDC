<template>
    <div class="skeleton-pae space-y-6">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2">
            <SkeletonBase
                v-for="i in 3"
                :key="`breadcrumb-${i}`"
                shape="line"
                width="80px"
                height="16px"
                rounded="md"
                :animation="animation"
            />
        </div>

        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
            <div class="flex items-start justify-between">
                <div class="flex-1 space-y-3">
                    <!-- Title -->
                    <SkeletonBase
                        shape="line"
                        width="40%"
                        height="32px"
                        rounded="md"
                        :animation="animation"
                    />

                    <!-- Subtitle -->
                    <SkeletonBase
                        shape="line"
                        width="60%"
                        height="20px"
                        rounded="md"
                        :animation="animation"
                    />

                    <!-- Status badges -->
                    <div class="flex gap-2">
                        <SkeletonBase
                            v-for="i in 2"
                            :key="`badge-${i}`"
                            shape="rectangle"
                            width="80px"
                            height="24px"
                            rounded="full"
                            :animation="animation"
                        />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <SkeletonBase
                        v-for="i in 3"
                        :key="`action-${i}`"
                        shape="rectangle"
                        width="40px"
                        height="40px"
                        rounded="md"
                        :animation="animation"
                    />
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="flex border-b border-gray-200 dark:border-gray-700">
                <SkeletonBase
                    v-for="i in 4"
                    :key="`tab-${i}`"
                    shape="rectangle"
                    width="120px"
                    height="48px"
                    rounded="none"
                    :animation="animation"
                    custom-class="border-r border-gray-200 dark:border-gray-700"
                />
            </div>

            <!-- Tab Content -->
            <div class="p-6 space-y-6">
                <!-- Form Fields Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div
                        v-for="i in 6"
                        :key="`field-${i}`"
                        class="space-y-2"
                    >
                        <!-- Label -->
                        <SkeletonBase
                            shape="line"
                            width="40%"
                            height="16px"
                            rounded="md"
                            :animation="animation"
                        />

                        <!-- Input -->
                        <SkeletonBase
                            shape="rectangle"
                            width="100%"
                            height="40px"
                            rounded="md"
                            :animation="animation"
                        />
                    </div>
                </div>

                <!-- Action Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <SkeletonCard
                        v-for="i in 3"
                        :key="`card-${i}`"
                        :lines="2"
                        :show-header="true"
                        :show-footer="true"
                        :footer-items="2"
                        :animation="animation"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import SkeletonBase from './SkeletonBase.vue'
import SkeletonCard from './SkeletonCard.vue'

defineProps({
    animation: {
        type: String,
        default: 'pulse'
    }
})
</script>
