import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { applyCpfMask, removeCpfMask, isValidCpfFormat } from '../utils/cpfMask';

/**
 * Composable para gerenciar o estado e lógica do formulário de login
 */
export function useLogin() {
  const cpf = ref('');
  const password = ref('');
  const remember = ref(false);
  const showPassword = ref(false);
  const loading = ref(false);
  const errors = ref({});

  /**
   * CPF formatado com máscara
   */
  const cpfFormatted = computed(() => {
    return applyCpfMask(cpf.value);
  });

  /**
   * Valida se o formulário está válido
   */
  const isValid = computed(() => {
    return isValidCpfFormat(cpf.value) && password.value.length >= 6;
  });

  /**
   * Atualiza o CPF aplicando a máscara automaticamente
   */
  function updateCpf(value) {
    const numbers = value.replace(/\D/g, '');
    if (numbers.length <= 11) {
      cpf.value = numbers;
    }
  }

  /**
   * Alterna a visibilidade da senha
   */
  function togglePasswordVisibility() {
    showPassword.value = !showPassword.value;
  }

  /**
   * Submete o formulário de login
   * Modo frontend: apenas redireciona para o dashboard
   */
  function submitLogin() {
    if (!isValid.value) {
      return;
    }

    loading.value = true;
    errors.value = {};

    // Autenticação real via backend
    router.post('/login', {
      cpf: removeCpfMask(cpf.value),
      password: password.value,
      remember: remember.value,
    }, {
      onFinish: () => {
        loading.value = false;
      },
      onError: (pageErrors) => {
        errors.value = pageErrors;
        loading.value = false;
      },
    });
  }

  /**
   * Reseta o formulário
   */
  function resetForm() {
    cpf.value = '';
    password.value = '';
    remember.value = false;
    showPassword.value = false;
    errors.value = {};
  }

  return {
    // State
    cpf,
    password,
    remember,
    showPassword,
    loading,
    errors,
    
    // Computed
    cpfFormatted,
    isValid,
    
    // Methods
    updateCpf,
    togglePasswordVisibility,
    submitLogin,
    resetForm,
  };
}

