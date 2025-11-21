<template>
  <Head title="Login" />

  <div class="login-container">
    <div class="login-card">
      <header class="card-header">
        <img
          src="https://www.mg.gov.br/sites/default/files/styles/large/public/media/image/2025/02/logo-defesa-civil-2.png?itok=NhfQmxcj"
          alt="Logo Defesa Civil"
          class="main-logo"
        />
        <div class="system-title">Sistema Integrado de Defesa Civil</div>
      </header>

      <form @submit.prevent="submitLogin" class="login-form">
        <!-- Input CPF -->
        <div class="input-group">
          <input
            type="text"
            id="cpf"
            v-model="cpfFormatted"
            @input="updateCpf($event.target.value)"
            class="input-field"
            placeholder=" "
            maxlength="14"
            required
            :class="{ 'border-red-500': errors.cpf }"
          />
          <label for="cpf" class="input-label">CPF</label>
          <span class="input-icon">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </span>
        </div>
        <div v-if="errors.cpf" class="error-message">{{ errors.cpf }}</div>

        <!-- Input Senha -->
        <div class="input-group">
          <input
            :type="showPassword ? 'text' : 'password'"
            id="password"
            v-model="password"
            class="input-field"
            placeholder=" "
            required
            :class="{ 'border-red-500': errors.password }"
          />
          <label for="password" class="input-label">Senha</label>
          <span
            class="input-icon toggle-password"
            @click="togglePasswordVisibility"
            :title="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
          >
            <svg
              v-if="showPassword"
              xmlns="http://www.w3.org/2000/svg"
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
              <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
              <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
              <line x1="2" y1="2" x2="22" y2="22"/>
            </svg>
          </span>
        </div>
        <div v-if="errors.password" class="error-message">{{ errors.password }}</div>

        <!-- Form Actions -->
        <div class="form-actions">
          <label class="remember-me">
            <input
              type="checkbox"
              v-model="remember"
            />
            <span>Lembrar-me</span>
          </label>
          <Link
            :href="route('password.request')"
            class="forgot-password"
          >
            Esqueceu a senha?
          </Link>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          class="btn-login"
          :disabled="loading || !isValid"
        >
          <span v-if="!loading">Acessar Sistema</span>
          <span v-else class="btn-loading">Autenticando...</span>
        </button>
      </form>

      <div class="card-footer">
        &copy; 2025 Governo do Estado de Minas Gerais
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useLogin } from '../../composables/useLogin';

const {
  cpf,
  password,
  remember,
  showPassword,
  loading,
  errors,
  cpfFormatted,
  isValid,
  updateCpf,
  togglePasswordVisibility,
  submitLogin,
} = useLogin();
</script>

<style scoped>
/* Garantir que o container ocupe toda a tela */
.login-container {
  min-height: 100vh;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  background: linear-gradient(135deg, #06315c, #001224);
  position: relative;
  overflow: hidden;
}
</style>

