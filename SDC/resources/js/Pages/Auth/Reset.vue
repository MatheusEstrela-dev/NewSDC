<template>
  <Head title="Redefinir Senha" />

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
        <p class="reset-subtitle">Escolha um método para identificar sua conta</p>
      </div>

      <div v-if="successMessage" class="success-message">
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
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        {{ successMessage }}
      </div>

      <div class="method-tabs">
        <button
          type="button"
          @click="setRecoveryMethod('cpf')"
          class="method-tab"
          :class="{ active: recoveryMethod === 'cpf' }"
        >
          Por CPF
        </button>
        <button
          type="button"
          @click="setRecoveryMethod('municipio')"
          class="method-tab"
          :class="{ active: recoveryMethod === 'municipio' }"
        >
          Por Município
        </button>
      </div>

      <form @submit.prevent="submitReset" class="reset-form">
        <div v-if="recoveryMethod === 'cpf'" class="input-group">
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

        <div v-else class="input-group">
          <select
            id="municipioId"
            v-model="municipioId"
            class="input-field"
            required
            :class="{ 'border-red-500': errors.municipio_id }"
          >
            <option value="" disabled>Selecione seu município</option>
            <option
              v-for="municipio in municipios"
              :key="municipio.id"
              :value="municipio.id"
            >
              {{ municipio.nome }}
            </option>
          </select>
          <label for="municipioId" class="input-label">Município</label>
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
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
              <circle cx="12" cy="10" r="3"></circle>
            </svg>
          </span>
        </div>

        <div v-if="errors[recoveryMethod === 'cpf' ? 'cpf' : 'municipio_id']" class="error-message">
          {{ errors[recoveryMethod === 'cpf' ? 'cpf' : 'municipio_id'] }}
        </div>

        <button
          type="submit"
          class="btn-reset"
          :disabled="loading || !isValid"
        >
          <span v-if="!loading">Redefinir Acesso</span>
          <span v-else class="btn-loading">Processando</span>
        </button>
      </form>

      <div class="reset-footer">
        <Link
          :href="route('login')"
          class="back-to-login"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          Voltar para o login
        </Link>
      </div>

      <div class="card-footer">
        &copy; 2025 Governo do Estado de Minas Gerais
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useReset } from '../../composables/useReset';
import '../../../css/pages/auth/reset.css';

defineProps({
  municipios: {
    type: Array,
    default: () => []
  },
  status: String
});

const {
  recoveryMethod,
  cpf,
  municipioId,
  loading,
  errors,
  successMessage,
  cpfFormatted,
  isValid,
  updateCpf,
  setRecoveryMethod,
  submitReset,
} = useReset();
</script>
