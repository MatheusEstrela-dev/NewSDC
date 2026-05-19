<template>
  <Head title="Primeiro Acesso" />

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
        <h1 class="reset-title">Defina sua nova senha</h1>
        <p class="reset-subtitle">
          Ola{{ userName ? `, ${userName.split(' ')[0]}` : '' }}. Por seguranca,
          substitua a senha provisoria recebida por e-mail antes de continuar.
        </p>
      </div>

      <div v-if="expiresAtFormatted" class="warning-banner">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
          <line x1="12" y1="9" x2="12" y2="13"></line>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        <span>
          Senha provisoria valida ate <strong>{{ expiresAtFormatted }}</strong>.
          Apos esse prazo a conta sera desativada automaticamente.
        </span>
      </div>

      <form @submit.prevent="submit" class="reset-form">
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

        <PasswordStrengthMeter :password="form.password" />

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

        <div v-if="passwordsMismatch" class="error-message">
          A confirmacao nao coincide com a nova senha.
        </div>

        <button
          type="submit"
          class="btn-reset"
          :disabled="form.processing || !canSubmit"
        >
          <span v-if="!form.processing">Salvar e continuar</span>
          <span v-else class="btn-loading">Processando</span>
        </button>
      </form>

      <div class="reset-footer">
        <Link :href="route('logout')" method="post" as="button" class="back-to-login">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
          Sair sem trocar
        </Link>
      </div>

      <div class="card-footer">
        &copy; {{ new Date().getFullYear() }} Governo do Estado de Minas Gerais
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PasswordStrengthMeter from '@/Components/Atoms/PasswordStrengthMeter.vue';
import '../../../css/pages/auth/reset.css';

const props = defineProps({
  userName: { type: String, default: '' },
  expiresAt: { type: String, default: null },
});

const showPassword = ref(false);
const showConfirm = ref(false);

const form = useForm({
  password: '',
  password_confirmation: '',
});

const passwordsMismatch = computed(
  () => form.password_confirmation.length > 0 && form.password !== form.password_confirmation,
);

const meetsPolicy = computed(() => {
  const p = form.password;
  return (
    p.length >= 8 &&
    /[a-z]/.test(p) &&
    /[A-Z]/.test(p) &&
    /\d/.test(p) &&
    /[^A-Za-z0-9]/.test(p)
  );
});

const canSubmit = computed(
  () => meetsPolicy.value && form.password === form.password_confirmation,
);

const expiresAtFormatted = computed(() => {
  if (!props.expiresAt) return null;
  const date = new Date(props.expiresAt);
  if (Number.isNaN(date.getTime())) return null;
  return date.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
});

function submit() {
  form.post(route('password.first-access.update'), {
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

.warning-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  margin: 0 0 1rem;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  background: rgba(243, 156, 18, 0.08);
  border: 1px solid rgba(243, 156, 18, 0.25);
  color: #92400e;
  font-size: 0.8rem;
  line-height: 1.4;
}
</style>
