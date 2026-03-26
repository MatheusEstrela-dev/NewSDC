<template>
  <div ref="chartContainer" class="w-full h-full min-h-[200px]">
    <template v-if="isVisible">
      <component
        :is="chartComponent"
        v-if="chartComponent"
        :type="type"
        :height="height"
        :width="width"
        :options="options"
        :series="series"
      />
    </template>
    <template v-else>
      <div class="flex items-center justify-center h-full">
        <div class="animate-pulse bg-slate-200 dark:bg-slate-700 rounded-lg w-full h-full min-h-[200px]"></div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, shallowRef, defineAsyncComponent } from 'vue';

const props = defineProps({
  type: { type: String, required: true },
  height: { type: [String, Number], default: '100%' },
  width: { type: [String, Number], default: '100%' },
  options: { type: Object, required: true },
  series: { type: Array, required: true },
});

const chartContainer = ref(null);
const isVisible = ref(false);
const chartComponent = shallowRef(null);

let observer = null;

onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting && !isVisible.value) {
        isVisible.value = true;
        import('vue3-apexcharts').then((module) => {
          chartComponent.value = module.default;
        });
        observer?.disconnect();
      }
    },
    { rootMargin: '100px', threshold: 0.1 }
  );

  if (chartContainer.value) {
    observer.observe(chartContainer.value);
  }
});

onUnmounted(() => {
  observer?.disconnect();
});
</script>
