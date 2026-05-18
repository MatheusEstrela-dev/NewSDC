<script setup>
import { computed, defineAsyncComponent, provide, ref } from 'vue';
import OfflineIndicator from '@/Components/Molecules/OfflineIndicator.vue';
import PullToRefresh from '@/Components/Molecules/PullToRefresh.vue';
import ToastContainer from '@/Components/Atoms/Toast/ToastContainer.vue';
import ContentAreaSkeleton from '@/Components/Molecules/Skeleton/ContentAreaSkeleton.vue';
import NavigationHeader from '@/Components/Organisms/Navigation/NavigationHeader.vue';
import ResponsiveDebugOverlay from '@/Components/Atoms/ResponsiveDebugOverlay.vue';
import TdapSidebar from '@/Components/Organisms/Tdap/Navigation/TdapSidebar.vue';
import TopBar from '@/Components/TopBar.vue';
import { useMobile, useSidebarMobile } from '@/Composables/useMobile';
import { usePageLoading } from '@/Composables/usePageLoading';

const responsiveDebugEnabled = computed(() => {
  if (typeof window === 'undefined') return false;
  if (import.meta.env.PROD) return false;
  return new URLSearchParams(window.location.search).get('debug') === 'responsive';
});

const { isPageLoading } = usePageLoading();

const SupportModal = defineAsyncComponent(() => import('@/Components/Organisms/Suporte/SupportModal.vue'));
const TermosUsoModal = defineAsyncComponent(() => import('@/Components/Organisms/TermosUsoModal.vue'));
const PrivacidadeModal = defineAsyncComponent(() => import('@/Components/Organisms/PrivacidadeModal.vue'));

const sidebarCollapsed = ref(false);
const showSupportModal = ref(false);
const showTermosModal = ref(false);
const showPrivacidadeModal = ref(false);

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
  <div class="flex min-h-screen bg-slate-50 dark:bg-slate-950">
    <ToastContainer />
    <OfflineIndicator />
    <PullToRefresh />

    <Transition name="fade">
      <div
        v-if="isSidebarOpen && (isMobile || isTablet)"
        class="mobile-overlay"
        @click="closeSidebar"
      ></div>
    </Transition>

    <TdapSidebar />
    <TopBar />

    <div class="flex-1 flex flex-col min-h-screen ml-0 lg:ml-[280px]">
      <NavigationHeader
        class="mt-12 md:mt-16 flex-shrink-0"
        :style="{ marginTop: `calc(max(env(safe-area-inset-top, 0px), var(--inset-top, 12px)) + ${isMobile ? '3rem' : '4rem'})` }"
      />

      <main class="flex-1 pt-4 bg-slate-50 dark:bg-slate-950 [overflow-x:clip] px-4 sm:px-6 lg:px-8">
        <ContentAreaSkeleton v-if="isPageLoading" />
        <slot v-else />
      </main>

      <footer class="flex flex-col sm:flex-row justify-between items-center gap-4 px-4 sm:px-6 lg:px-8 py-6 mt-auto bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3 flex-wrap justify-center sm:justify-start">
          <picture>
            <source srcset="/imgs/logo-defesa-civil.webp" type="image/webp" />
            <img
              src="/imgs/logo-defesa-civil.png"
              alt="MG Logo"
              width="120"
              height="24"
              class="h-5 sm:h-6 w-auto"
              loading="lazy"
              decoding="async"
              style="aspect-ratio: 5/1;"
            />
          </picture>
          <span class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 text-center sm:text-left">
            CEDEC - Defesa Civil de Minas Gerais
          </span>
          <span class="text-xs sm:text-sm text-slate-500 dark:text-slate-500 hidden sm:inline">
            © 2025 Todos os direitos reservados.
          </span>
        </div>
        <div class="flex gap-4 sm:gap-6">
          <a href="#" @click.prevent="showTermosModal = true" class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors">Termos</a>
          <a href="#" @click.prevent="showPrivacidadeModal = true" class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors">Privacidade</a>
          <a href="#" @click.prevent="showSupportModal = true" class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors">Suporte</a>
        </div>
      </footer>
    </div>

    <ResponsiveDebugOverlay :enabled="responsiveDebugEnabled" />

    <SupportModal :show="showSupportModal" @close="showSupportModal = false" />
    <TermosUsoModal :show="showTermosModal" @close="showTermosModal = false" />
    <PrivacidadeModal :show="showPrivacidadeModal" @close="showPrivacidadeModal = false" />
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

@media (max-width: 640px) {
  main {
    padding-left: 1rem !important;
    padding-right: 1rem !important;
  }
}
</style>
