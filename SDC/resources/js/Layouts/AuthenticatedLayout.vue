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
  <div class="layout-container">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Top Bar -->
    <TopBar />

    <!-- Main Content Area -->
    <div class="main-content" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
      <!-- Page Content -->
      <main class="content-wrapper">
        <slot />
      </main>

      <!-- Footer -->
      <footer class="page-footer">
        <div class="footer-left">
          <img
            src="https://www.mg.gov.br/sites/default/files/styles/large/public/media/image/2025/02/logo-defesa-civil-2.png?itok=NhfQmxcj"
            alt="MG Logo"
            class="footer-logo"
            style="height: 24px; margin-right: 12px;"
          />
          <span class="footer-text">
            CEDEC - Defesa Civil de Minas Gerais
          </span>
          <span class="footer-copyright">
            © 2025 Todos os direitos reservados.
          </span>
        </div>
        <div class="footer-right">
          <a href="#" class="footer-link">Termos</a>
          <a href="#" class="footer-link">Privacidade</a>
          <a href="#" class="footer-link">Suporte</a>
        </div>
      </footer>
    </div>
  </div>
</template>

<style scoped>
.layout-container {
  display: flex;
  min-height: 100vh;
  background: #f8fafc;
}

.main-content {
  flex: 1;
  margin-left: 280px;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  transition: margin-left 0.3s ease;
}

.main-content.sidebar-collapsed {
  margin-left: 80px;
}

.content-wrapper {
  flex: 1;
  padding: 0;
  background: #0f172a;
  overflow-x: hidden;
  margin-top: 64px; /* Espaço para a top bar fixa */
}

.page-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  background: white;
  border-top: 1px solid #e2e8f0;
  margin-top: auto;
}

.footer-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.footer-logo {
  height: 24px;
}

.footer-text {
  color: #64748b;
  font-size: 0.875rem;
}

.footer-copyright {
  color: #94a3b8;
  font-size: 0.875rem;
}

.footer-right {
  display: flex;
  gap: 1.5rem;
}

.footer-link {
  color: #64748b;
  font-size: 0.875rem;
  text-decoration: none;
  transition: color 0.2s;
}

.footer-link:hover {
  color: #3b82f6;
}

/* Responsive */
@media (max-width: 1024px) {
  .main-content {
    margin-left: 0;
  }

  .content-wrapper {
    padding: 1rem;
  }

  .page-footer {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
}
</style>
