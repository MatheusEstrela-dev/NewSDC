<template>
  <div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <component
            :is="iconComponent"
            :class="['h-6 w-6', iconColorClass]"
            aria-hidden="true"
          />
        </div>
        <div class="ml-5 w-0 flex-1">
          <dl>
            <dt class="text-sm font-medium text-gray-500 truncate">
              {{ title }}
            </dt>
            <dd class="flex items-baseline">
              <div class="text-2xl font-semibold text-gray-900">
                {{ value }}
              </div>
              <div
                v-if="trend"
                :class="[
                  'ml-2 flex items-baseline text-sm font-semibold',
                  trendColorClass
                ]"
              >
                <component
                  :is="trendIcon"
                  :class="['h-4 w-4 flex-shrink-0', trendColorClass]"
                  aria-hidden="true"
                />
                <span class="ml-1">{{ trend }}</span>
              </div>
            </dd>
          </dl>
        </div>
      </div>
    </div>
    <div v-if="footerText" class="bg-gray-50 px-5 py-3">
      <div class="text-sm">
        <a
          v-if="footerLink"
          :href="footerLink"
          class="font-medium text-blue-700 hover:text-blue-900"
        >
          {{ footerText }}
        </a>
        <span v-else class="text-gray-700">
          {{ footerText }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  CubeIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  icon: {
    type: Object,
    default: () => CubeIcon
  },
  iconColor: {
    type: String,
    default: 'text-gray-400',
    validator: (value) => [
      'text-gray-400',
      'text-blue-500',
      'text-green-500',
      'text-yellow-500',
      'text-red-500',
      'text-purple-500'
    ].includes(value)
  },
  trend: {
    type: String,
    default: null
  },
  trendDirection: {
    type: String,
    default: 'up',
    validator: (value) => ['up', 'down'].includes(value)
  },
  footerText: {
    type: String,
    default: null
  },
  footerLink: {
    type: String,
    default: null
  }
})

const iconComponent = computed(() => props.icon)
const iconColorClass = computed(() => props.iconColor)

const trendIcon = computed(() => {
  return props.trendDirection === 'up' ? ArrowTrendingUpIcon : ArrowTrendingDownIcon
})

const trendColorClass = computed(() => {
  return props.trendDirection === 'up' ? 'text-green-600' : 'text-red-600'
})
</script>
