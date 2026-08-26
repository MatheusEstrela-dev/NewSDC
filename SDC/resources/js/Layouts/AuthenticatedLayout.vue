<script setup>
import FlashNotification from '@/Components/Molecules/FlashNotification.vue';
import OfflineIndicator from '@/Components/Molecules/OfflineIndicator.vue';
import PullToRefresh from '@/Components/Molecules/PullToRefresh.vue';
import ToastContainer from '@/Components/Atoms/Toast/ToastContainer.vue';
import ContentAreaSkeleton from '@/Components/Molecules/Skeleton/ContentAreaSkeleton.vue';
import NavigationHeader from '@/Components/Organisms/Navigation/NavigationHeader.vue';
import EmailChangeVerifyModal from '@/Components/Organisms/EmailChangeVerifyModal.vue';
import Sidebar from '@/Components/Sidebar.vue';
import TopBar from '@/Components/TopBar.vue';
import { useMobile, useSidebarMobile } from '@/Composables/useMobile';
import { usePageLoading } from '@/Composables/usePageLoading';
import { defineAsyncComponent, onMounted, onUnmounted, provide, ref } from 'vue';

const { isPageLoading } = usePageLoading();

// Modais carregados sob demanda (raramente usados, reduz bundle de ~50KB+ por página)
const SupportModal = defineAsyncComponent(() => import('@/Components/Organisms/Suporte/SupportModal.vue'));
const TermosUsoModal = defineAsyncComponent(() => import('@/Components/Organisms/TermosUsoModal.vue'));
const PrivacidadeModal = defineAsyncComponent(() => import('@/Components/Organisms/PrivacidadeModal.vue'));
const GuiaSistemaModal = defineAsyncComponent(() => import('@/Components/Organisms/GuiaSistemaModal.vue'));

// Estado compartilhado da sidebar desktop
const sidebarCollapsed = ref(false);
const showSupportModal = ref(false);
const showTermosModal = ref(false);
const showPrivacidadeModal = ref(false);
const showGuiaModal = ref(false);

// Listeners para eventos disparados pelo passo final do tour de boas-vindas.
// Quando o tour abre Termos via "open-termos", encadeamos Privacidade ao fechar
// para completar o ciclo de aceite institucional (LGPD).
const chainPrivacidadeAfterTermos = ref(false);

const handleOpenGuia = () => { showGuiaModal.value = true; };
const handleOpenTermos = () => {
  chainPrivacidadeAfterTermos.value = true;
  showTermosModal.value = true;
};
const handleOpenPrivacidade = () => { showPrivacidadeModal.value = true; };

const closeTermos = () => {
  showTermosModal.value = false;
  if (chainPrivacidadeAfterTermos.value) {
    chainPrivacidadeAfterTermos.value = false;
    setTimeout(() => { showPrivacidadeModal.value = true; }, 250);
  }
};

onMounted(() => {
  window.addEventListener('sdc-tour:open-guia', handleOpenGuia);
  window.addEventListener('sdc-tour:open-termos', handleOpenTermos);
  window.addEventListener('sdc-tour:open-privacidade', handleOpenPrivacidade);
});

onUnmounted(() => {
  window.removeEventListener('sdc-tour:open-guia', handleOpenGuia);
  window.removeEventListener('sdc-tour:open-termos', handleOpenTermos);
  window.removeEventListener('sdc-tour:open-privacidade', handleOpenPrivacidade);
});

// Estado e funções para sidebar mobile
const { isMobile, isTablet, isDesktop } = useMobile();
const { isSidebarOpen, openSidebar, closeSidebar, toggleSidebar } = useSidebarMobile();

// Fornecer o estado para componentes filhos
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
    <!-- Toast Notifications -->
    <ToastContainer />
    <FlashNotification />

    <!-- Offline/Slow Connection Indicator -->
    <OfflineIndicator />

    <!-- Pull to Refresh (Mobile Only) -->
    <PullToRefresh />

    <!-- Mobile Overlay/Backdrop -->
    <Transition name="fade">
      <div
        v-if="isSidebarOpen && (isMobile || isTablet)"
        class="mobile-overlay"
        @click="closeSidebar"
      ></div>
    </Transition>

    <!-- Sidebar -->
    <Sidebar />

    <!-- Top Bar -->
    <TopBar />

    <!--
      Main Content Area

      min-w-0 e obrigatorio aqui. Item flex nasce com min-width:auto, ou seja, se
      recusa a encolher abaixo da largura intrinseca do proprio conteudo: uma tabela
      larga inflava este wrapper para alem da viewport e TUDO dentro dele passava a
      ser dimensionado contra essa largura inflada -- por isso os stat cards perdiam
      a proporcao em telas medias, mesmo com o grid correto.
      Medido antes/depois em 768, 1024 e 1280px sobre PAE, Decretacoes, RAT e
      Permissionamento: o estouro ia de ate 672px para 0 em todos os casos.
      O [overflow-x:clip] do <main> abaixo nao resolvia porque o pai ja havia crescido.

      O offset lateral vira em `lg`, NAO em `md`: abaixo de 1024px a Sidebar e um
      drawer off-canvas (`transform: translateX(-100%)` em Sidebar.styles.css, tanto
      na media query de mobile quanto na de tablet), logo nao ocupa nada no layout.
      Reservar `md:ml-20` ali criava 80px de faixa branca morta entre a borda da tela
      e o conteudo em toda a faixa 768-1023px -- medido em 900px: wrapper e TopBar
      comecavam em x=80 com a sidebar em translateX(-280px). O `lg:!ml-20` do
      collapsed continua valendo, porque em lg+ a sidebar existe mesmo (80px).
    -->
    <div
      class="flex-1 min-w-0 flex flex-col min-h-screen ml-0 lg:ml-[280px]"
      :class="{
        'lg:!ml-20': sidebarCollapsed
      }"
      :data-collapsed="sidebarCollapsed"
    >
      <!-- Navigation Header (Breadcrumb) -->
      <NavigationHeader class="mt-12 md:mt-16 flex-shrink-0" :style="{ marginTop: `calc(max(env(safe-area-inset-top, 0px), var(--inset-top, 12px)) + ${isMobile ? '3rem' : '4rem'})` }" />

      <!-- Page Content -->
      <main class="flex-1 pt-4 bg-slate-50 dark:bg-slate-950 [overflow-x:clip] px-4 sm:px-6 lg:px-8">
        <ContentAreaSkeleton v-if="isPageLoading" />
        <slot v-else />
      </main>

      <!-- Footer -->
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
            © {{ new Date().getFullYear() }} Todos os direitos reservados.
          </span>
        </div>
        <div class="flex gap-4 sm:gap-6">
          <a href="#" @click.prevent="showTermosModal = true" class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors">Termos</a>
          <a href="#" @click.prevent="showPrivacidadeModal = true" class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors">Privacidade</a>
          <a href="#" @click.prevent="showSupportModal = true" class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors">Suporte</a>
        </div>
      </footer>
    </div>
    <!-- Support Modal -->
    <SupportModal :show="showSupportModal" @close="showSupportModal = false" />
    <!-- Termos Uso Modal -->
    <TermosUsoModal :show="showTermosModal" @close="closeTermos" />
    <!-- Privacidade Modal -->
    <PrivacidadeModal :show="showPrivacidadeModal" @close="showPrivacidadeModal = false" />
    <!-- Guia do Sistema (disparado pelo passo final do tour) -->
    <GuiaSistemaModal :show="showGuiaModal" @close="showGuiaModal = false" />

    <!-- Popup global de verificacao de troca de e-mail (nao-dismissable) -->
    <EmailChangeVerifyModal
        v-if="$page.props.auth?.user?.pending_email_change"
        :pending-change="$page.props.auth.user.pending_email_change"
    />
  </div>
</template>

<style scoped>
/* Mobile Overlay */
.mobile-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
  z-index: 40;
  cursor: pointer;
}

/* Transições do overlay */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Responsive - Apenas mobile */
@media (max-width: 767px) {
  .flex-1.flex.flex-col {
    margin-left: 0 !important;
  }
}

/* Ajustes para dispositivos muito pequenos */
@media (max-width: 640px) {
  main {
    padding-left: 1rem !important;
    padding-right: 1rem !important;
  }
}
</style>
