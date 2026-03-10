<script setup>
import OfflineIndicator from '@/Components/Molecules/OfflineIndicator.vue';
import Sidebar from '@/Components/Sidebar.vue';
import { useMobile, useSidebarMobile } from '@/Composables/useMobile';
import { provide, ref } from 'vue';

const sidebarCollapsed = ref(false);

const { isMobile, isTablet, isDesktop } = useMobile();
const { isSidebarOpen, openSidebar, closeSidebar, toggleSidebar } = useSidebarMobile();

provide('sidebarCollapsed', sidebarCollapsed);
provide('isMobile', isMobile);
provide('isTablet', isTablet);
provide('isDesktop', isDesktop);
provide('isSidebarOpen', isSidebarOpen);
provide('toggleSidebar', toggleSidebar);
provide('closeSidebar', closeSidebar);
provide('openSidebar', openSidebar);
</script>

<template>
  <div class="flex h-screen bg-slate-50 dark:bg-slate-950 overflow-hidden">
    <OfflineIndicator />

    <Transition name="fade">
      <div
        v-if="isSidebarOpen && (isMobile || isTablet)"
        class="mobile-overlay"
        @click="closeSidebar"
      ></div>
    </Transition>

    <Sidebar />

    <div
      class="flex-1 flex flex-col h-screen overflow-hidden transition-all duration-300 ml-0 md:ml-20"
      :class="{
        'lg:ml-[280px]': !sidebarCollapsed,
        'lg:ml-20': sidebarCollapsed
      }"
    >
      <slot />
    </div>
  </div>
</template>

<style scoped>
.mobile-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
  z-index: 40;
  cursor: pointer;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@media (max-width: 767px) {
  .flex-1.flex.flex-col {
    margin-left: 0 !important;
  }
}
</style>
