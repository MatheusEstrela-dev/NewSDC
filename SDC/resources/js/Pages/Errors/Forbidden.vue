<template>
  <AuthenticatedLayout>
    <Head :title="title ?? 'Acesso negado'" />

    <div class="error-wrap">
      <div class="error-card">
        <div class="error-code">403</div>
        <h1 class="error-title">{{ title ?? 'Acesso negado' }}</h1>
        <p class="error-message">
          {{ message ?? 'Você não tem permissão para acessar esta página.' }}
        </p>

        <div class="error-actions">
          <Link :href="route('dashboard')" class="btn-primary">
            Voltar ao Dashboard
          </Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
  title: { type: String, default: null },
  message: { type: String, default: null },
});
</script>

<style scoped>
.error-wrap {
  min-height: calc(100vh - 64px);
  display: grid;
  place-items: center;
  padding: 2rem 1rem;
  background: #0f172a;
}

.error-card {
  width: 100%;
  max-width: 520px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 16px;
  padding: 2rem;
  text-align: center;
  backdrop-filter: blur(12px);
}

.error-code {
  font-size: 3rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: rgba(59, 130, 246, 0.9);
  margin-bottom: 0.5rem;
}

.error-title {
  color: #e2e8f0;
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
}

.error-message {
  color: rgba(226, 232, 240, 0.85);
  margin: 0 0 1.5rem;
  line-height: 1.5;
}

.error-actions {
  display: flex;
  justify-content: center;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 44px;
  padding: 0 18px;
  border-radius: 10px;
  background: #d08a14;
  color: #0b1220;
  font-weight: 700;
  text-decoration: none;
  transition: filter 0.2s, transform 0.2s;
}

.btn-primary:hover {
  filter: brightness(1.05);
  transform: translateY(-1px);
}

@media (max-width: 480px) {
  .error-card {
    padding: 1.5rem;
    border-radius: 14px;
  }
  .error-code {
    font-size: 2.25rem;
  }
  .error-title {
    font-size: 1.25rem;
  }
}
</style>


