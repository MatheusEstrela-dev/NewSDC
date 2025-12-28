<script setup>
import { ref, provide } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';
import TopBar from '@/Components/TopBar.vue';

// Estado compartilhado da sidebar
const sidebarCollapsed = ref(false);

// Fornecer o estado para componentes filhos
provide('sidebarCollapsed', sidebarCollapsed);
</script>

<template>
  <div class="flex min-h-screen bg-slate-50 dark:bg-slate-950">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Top Bar -->
    <TopBar />

    <!-- Main Content Area -->
    <div
      class="flex-1 flex flex-col min-h-screen transition-all duration-300"
      :class="sidebarCollapsed ? 'ml-20' : 'ml-[280px]'"
    >
      <!-- Page Content -->
      <main class="flex-1 mt-16 bg-slate-50 dark:bg-slate-950 overflow-x-hidden">
        <slot />
      </main>

      <!-- Footer -->
      <footer class="flex justify-between items-center px-8 py-6 mt-auto bg-white border-t border-slate-200">
        <div class="flex items-center gap-3 flex-wrap">
          <img
            src="https://www.mg.gov.br/sites/default/files/styles/large/public/media/image/2025/02/logo-defesa-civil-2.png?itok=NhfQmxcj"
            alt="MG Logo"
            class="h-6"
            loading="lazy"
            decoding="async"
          />
          <span class="text-sm text-slate-600">
            CEDEC - Defesa Civil de Minas Gerais
          </span>
          <span class="text-sm text-slate-500">
            © 2025 Todos os direitos reservados.
          </span>
        </div>
        <div class="flex gap-6">
          <a href="#" class="text-sm text-slate-600 hover:text-blue-500 transition-colors">Termos</a>
          <a href="#" class="text-sm text-slate-600 hover:text-blue-500 transition-colors">Privacidade</a>
          <a href="#" class="text-sm text-slate-600 hover:text-blue-500 transition-colors">Suporte</a>
        </div>
      </footer>
    </div>
  </div>
</template>

<style scoped>
/* Responsive */
@media (max-width: 1024px) {
  .flex-1.flex.flex-col {
    margin-left: 0 !important;
  }

  footer {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
}
</style>
