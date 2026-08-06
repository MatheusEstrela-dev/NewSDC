<template>
  <Head title="Portal de Treinamentos - Cadastro" />

  <div class="login-container">
    <div class="login-card" style="max-width: 32rem;">
      <header class="card-header">
        <picture>
          <source srcset="/imgs/logo-defesa-civil-login.webp" type="image/webp" />
          <img
            src="/imgs/logo-defesa-civil-login.png"
            alt="Logo Defesa Civil"
            class="main-logo"
            width="240"
            height="72"
            style="aspect-ratio: 10/3;"
          />
        </picture>
        <div class="system-title">Cadastro no Portal de Treinamentos</div>
      </header>

      <form @submit.prevent="submit" class="login-form">
        <div class="input-group">
          <input type="text" id="name" v-model="form.name" class="input-field" placeholder=" " required :class="{ 'border-red-500': form.errors.name }" />
          <label for="name" class="input-label">Nome completo</label>
        </div>

        <div class="input-group">
          <input type="email" id="email" v-model="form.email" class="input-field" placeholder=" " required :class="{ 'border-red-500': form.errors.email }" />
          <label for="email" class="input-label">E-mail</label>
        </div>

        <div class="input-group">
          <input
            type="text"
            inputmode="numeric"
            id="cpf"
            :value="cpfFormatted"
            @input="handleCpfInput"
            maxlength="14"
            class="input-field"
            placeholder=" "
            required
            :class="{ 'border-red-500': form.errors.cpf }"
          />
          <label for="cpf" class="input-label">CPF</label>
        </div>

        <div class="input-group">
          <input
            type="text"
            inputmode="numeric"
            id="telefone"
            :value="telefoneFormatted"
            @input="handleTelefoneInput"
            maxlength="15"
            class="input-field"
            placeholder=" "
            :class="{ 'border-red-500': form.errors.telefone }"
          />
          <label for="telefone" class="input-label">Telefone com DDD (opcional)</label>
        </div>

        <div class="input-group">
          <input type="password" id="password" v-model="form.password" class="input-field" placeholder=" " required :class="{ 'border-red-500': form.errors.password }" />
          <label for="password" class="input-label">Senha</label>
        </div>

        <div class="input-group">
          <input type="password" id="password_confirmation" v-model="form.password_confirmation" class="input-field" placeholder=" " required />
          <label for="password_confirmation" class="input-label">Confirme a senha</label>
        </div>

        <label class="remember-me mb-4">
          <input type="checkbox" v-model="form.aceite_lgpd" required />
          <span>Li e aceito os termos de uso e a política de privacidade (LGPD)</span>
        </label>

        <div v-if="hasErrors" class="mb-4 mt-2 p-2 rounded-lg bg-red-500/10 border border-red-500/20 text-center shadow-sm backdrop-blur-sm">
          <p v-for="(msg, key) in form.errors" :key="key" class="text-sm text-red-400 font-medium">{{ msg }}</p>
        </div>

        <button type="submit" class="btn-login" :disabled="form.processing">
          <span v-if="!form.processing">Criar minha conta</span>
          <span v-else class="btn-loading">Enviando...</span>
        </button>
      </form>

      <div class="card-footer">
        Já tem conta? <Link :href="route('login')" class="text-amber-400 hover:underline">Entrar</Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { applyCpfMask } from '@/utils/cpfMask';
import { applyPhoneMask } from '@/utils/phoneMask';
import '../../../../css/pages/auth/login.css';

const form = useForm({
  name: '',
  email: '',
  cpf: '',
  telefone: '',
  password: '',
  password_confirmation: '',
  aceite_lgpd: false,
});

const cpfFormatted = ref('');
const telefoneFormatted = ref('');
const hasErrors = computed(() => Object.keys(form.errors).length > 0);

const handleCpfInput = (evt) => {
  const numbersOnly = evt.target.value.replace(/\D/g, '').slice(0, 11);
  form.cpf = numbersOnly;
  cpfFormatted.value = applyCpfMask(numbersOnly);
  evt.target.value = cpfFormatted.value;
};

const handleTelefoneInput = (evt) => {
  const numbersOnly = evt.target.value.replace(/\D/g, '').slice(0, 11);
  form.telefone = numbersOnly;
  telefoneFormatted.value = applyPhoneMask(numbersOnly);
  evt.target.value = telefoneFormatted.value;
};

const submit = () => {
  form.post(route('portal.treinamento.registrar'));
};
</script>
