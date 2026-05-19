<template>
  <Head title="Nova Senha" />

  <div class="reset-container">
    <div class="reset-card">
      <header class="card-header">
        <picture>
          <source srcset="/imgs/logo-defesa-civil.webp" type="image/webp" />
          <img
            src="/imgs/logo-defesa-civil.png"
            alt="Logo Defesa Civil"
            class="main-logo"
          />
        </picture>
        <div class="system-title">Sistema Integrado de Defesa Civil</div>
      </header>

      <div class="reset-header">
        <h1 class="reset-title">Definir nova senha</h1>
        <p class="reset-subtitle">Escolha uma senha segura para sua conta</p>
      </div>

      <div v-if="!hasResetContext" class="error-message" style="padding:1rem; border-radius:0.75rem; background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.2);">
        Link de redefinicao invalido ou expirado.
        <Link :href="route('password.request')" style="color: var(--reset-primary); text-decoration: underline; margin-left: 0.25rem;">
          Solicitar novo link
        </Link>
      </div>

      <div v-if="form.recentlySuccessful" class="success-message">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        Senha atualizada com sucesso.
      </div>

      <form v-if="hasResetContext" @submit.prevent="submit" class="reset-form">
        <div class="input-group">
          <input
            :type="showPassword ? 'text' : 'password'"
            id="password"
            v-model="form.password"
            class="input-field"
            placeholder=" "
            autocomplete="new-password"
            required
            :class="{ 'border-red-500': form.errors.password }"
          />
          <label for="password" class="input-label">Nova senha</label>
          <button
            type="button"
            class="input-icon toggle-icon"
            @click="showPassword = !showPassword"
            :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
          >
            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path>
              <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          </button>
        </div>

        <div v-if="form.errors.password" class="error-message">
          {{ form.errors.password }}
        </div>

        <div class="input-group">
          <input
            :type="showConfirm ? 'text' : 'password'"
            id="password_confirmation"
            v-model="form.password_confirmation"
            class="input-field"
            placeholder=" "
            autocomplete="new-password"
            required
          />
          <label for="password_confirmation" class="input-label">Confirmar nova senha</label>
          <button
            type="button"
            class="input-icon toggle-icon"
            @click="showConfirm = !showConfirm"
            :aria-label="showConfirm ? 'Ocultar senha' : 'Mostrar senha'"
          >
            <svg v-if="!showConfirm" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path>
              <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          </button>
        </div>

        <div v-if="form.errors.email" class="error-message">
          {{ form.errors.email }}
        </div>

        <button
          type="submit"
          class="btn-reset"
          :disabled="form.processing || !canSubmit"
        >
          <span v-if="!form.processing">Salvar nova senha</span>
          <span v-else class="btn-loading">Processando</span>
        </button>
      </form>

      <div class="reset-footer">
        <Link :href="route('login')" class="back-to-login">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          Voltar para o login
        </Link>
      </div>

      <div class="card-footer">
        &copy; {{ new Date().getFullYear() }} Governo do Estado de Minas Gerais
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import '../../../css/pages/auth/reset.css';

// hasResetContext: backend ja decriptou o link e guardou token+email na session.
// Token e email NUNCA chegam ao frontend — evitam exposicao via view-source/devtools.
defineProps({
  hasResetContext: { type: Boolean, default: false },
});

const showPassword = ref(false);
const showConfirm = ref(false);

const form = useForm({
  password: '',
  password_confirmation: '',
});

const canSubmit = computed(() =>
  form.password.length >= 8 && form.password === form.password_confirmation
);

function submit() {
  form.post(route('password.store'), {
    onFinish: () => {
      form.reset('password', 'password_confirmation');
    },
  });
}
</script>

<style scoped>
.toggle-icon {
  pointer-events: auto;
  cursor: pointer;
  background: transparent;
  border: 0;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
