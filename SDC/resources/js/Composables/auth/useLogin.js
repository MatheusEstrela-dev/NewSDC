import { router } from '@inertiajs/vue3';
import { computed, ref, onScopeDispose } from 'vue';
import { applyCpfMask, isValidCpfFormat, removeCpfMask } from '../../utils/cpfMask';

/**
 * Composable para gerenciar o estado e lógica do formulário de login
 */
// Chave do "lembrar CPF" no navegador. Guardamos APENAS o CPF (identificador
// de login), NUNCA a senha. Padrao "remember username".
const REMEMBER_CPF_KEY = 'sdc_remember_cpf';

function lerCpfLembrado() {
  if (typeof window === 'undefined') return '';
  try {
    return window.localStorage.getItem(REMEMBER_CPF_KEY) || '';
  } catch {
    return '';
  }
}

export function useLogin() {
  const cpfLembrado = lerCpfLembrado();
  const cpf = ref(cpfLembrado);
  const password = ref('');
  const remember = ref(cpfLembrado !== '');
  const showPassword = ref(false);
  const loading = ref(false);
  const errors = ref({});

  // Contagem regressiva de bloqueio por excesso de tentativas (throttle).
  // O backend devolve `retry_after` (segundos) no erro; aqui animamos.
  const throttleRemaining = ref(0);
  const throttleTotal = ref(0);
  let throttleTimer = null;
  const isThrottled = computed(() => throttleRemaining.value > 0);

  function startThrottleCountdown(seconds) {
    const s = Math.max(0, Math.ceil(Number(seconds) || 0));
    if (throttleTimer) clearInterval(throttleTimer);
    throttleTotal.value = s;
    throttleRemaining.value = s;
    if (s <= 0) return;
    throttleTimer = setInterval(() => {
      throttleRemaining.value -= 1;
      if (throttleRemaining.value <= 0) {
        clearInterval(throttleTimer);
        throttleTimer = null;
        throttleRemaining.value = 0;
        errors.value = {}; // libera o formulario ao zerar
      }
    }, 1000);
  }

  onScopeDispose(() => {
    if (throttleTimer) clearInterval(throttleTimer);
  });

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
   * Atualiza o CPF permitindo apenas números e aplicando a máscara
   * @param {String} value
   */
  function updateCpf(value) {
    // Garante que é string
    const strValue = String(value || '');
    const numbers = strValue.replace(/\D/g, '');

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
    if (!isValid.value || isThrottled.value) {
      return;
    }

    loading.value = true;
    errors.value = {};

    // Persiste (ou limpa) o CPF lembrado conforme o checkbox. Só o CPF,
    // nunca a senha.
    if (typeof window !== 'undefined') {
      try {
        if (remember.value) {
          window.localStorage.setItem(REMEMBER_CPF_KEY, removeCpfMask(cpf.value));
        } else {
          window.localStorage.removeItem(REMEMBER_CPF_KEY);
        }
      } catch {
        // localStorage indisponivel (modo privado etc.): ignora silenciosamente
      }
    }

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
        if (pageErrors && pageErrors.retry_after) {
          startThrottleCountdown(pageErrors.retry_after);
        }
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

    // Throttle / countdown
    throttleRemaining,
    throttleTotal,
    isThrottled,

    // Methods
    updateCpf,
    togglePasswordVisibility,
    submitLogin,
    resetForm,
  };
}

