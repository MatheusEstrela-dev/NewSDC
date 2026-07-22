<template>
  <Head title="Redefinir Senha" />

  <div class="reset-container">
    <div class="reset-card">
      <header class="card-header">
        <picture>
          <source srcset="/imgs/logo-defesa-civil-login.webp" type="image/webp" />
          <img
            src="/imgs/logo-defesa-civil-login.png"
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

        <div v-else class="input-group municipio-autocomplete">
          <input
            type="text"
            id="municipioBusca"
            :value="municipioBusca"
            @input="buscarMunicipios($event.target.value)"
            class="input-field"
            placeholder=" "
            autocomplete="off"
            :class="{ 'border-red-500': errors.municipio_id }"
          />
          <label for="municipioBusca" class="input-label">Município</label>
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

          <ul v-if="municipioResultados.length" class="municipio-results">
            <li
              v-for="municipio in municipioResultados"
              :key="municipio.id"
              class="municipio-result"
              @mousedown.prevent="selecionarMunicipio(municipio)"
            >
              {{ municipio.nome }} <span class="municipio-uf">/ {{ municipio.uf }}</span>
            </li>
          </ul>
          <p v-else-if="buscandoMunicipios" class="municipio-hint">Buscando...</p>
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
        &copy; {{ new Date().getFullYear() }} Governo do Estado de Minas Gerais
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useReset } from '../../Composables/useReset';
import '../../../css/pages/auth/reset.css';

defineProps({
  status: String
});

const {
  recoveryMethod,
  cpf,
  municipioBusca,
  municipioResultados,
  buscandoMunicipios,
  loading,
  errors,
  successMessage,
  cpfFormatted,
  isValid,
  updateCpf,
  buscarMunicipios,
  selecionarMunicipio,
  setRecoveryMethod,
  submitReset,
} = useReset();
</script>

<style scoped>
.municipio-autocomplete {
  position: relative;
}

.municipio-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 20;
  margin: 0.25rem 0 0;
  padding: 0.25rem;
  list-style: none;
  max-height: 15rem;
  overflow-y: auto;
  background: #0f172a;
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 0.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
}

.municipio-result {
  padding: 0.55rem 0.75rem;
  border-radius: 0.375rem;
  color: #e2e8f0;
  font-size: 0.9rem;
  cursor: pointer;
}

.municipio-result:hover {
  background: rgba(234, 140, 10, 0.15);
}

.municipio-uf {
  color: #94a3b8;
  font-size: 0.8rem;
}

.municipio-hint {
  position: absolute;
  top: 100%;
  left: 0.25rem;
  margin-top: 0.35rem;
  font-size: 0.8rem;
  color: #94a3b8;
}
</style>
