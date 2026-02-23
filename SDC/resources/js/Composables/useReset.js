import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { applyCpfMask, removeCpfMask, isValidCpfFormat } from '../utils/cpfMask';

export function useReset() {
  const recoveryMethod = ref('cpf');
  const cpf = ref('');
  const municipioId = ref('');
  const loading = ref(false);
  const errors = ref({});
  const successMessage = ref('');

  const cpfFormatted = computed(() => {
    return applyCpfMask(cpf.value);
  });

  const isValid = computed(() => {
    if (recoveryMethod.value === 'cpf') {
      return isValidCpfFormat(cpf.value);
    }
    return municipioId.value !== '';
  });

  function updateCpf(value) {
    const numbers = value.replace(/\D/g, '');
    if (numbers.length <= 11) {
      cpf.value = numbers;
    }
  }

  function setRecoveryMethod(method) {
    recoveryMethod.value = method;
    errors.value = {};
    successMessage.value = '';
  }

  function submitReset() {
    if (!isValid.value) {
      return;
    }

    loading.value = true;
    errors.value = {};
    successMessage.value = '';

    const data = {
      reset_type: recoveryMethod.value,
    };

    if (recoveryMethod.value === 'cpf') {
      data.cpf = removeCpfMask(cpf.value);
    } else {
      data.id_municipio = municipioId.value;
    }

    router.post('/forgot-password', data, {
      onFinish: () => {
        loading.value = false;
      },
      onSuccess: () => {
        successMessage.value = 'Link de redefinição enviado! Verifique o e-mail cadastrado.';
        cpf.value = '';
        municipioId.value = '';
      },
      onError: (pageErrors) => {
        errors.value = pageErrors;
        loading.value = false;
      },
    });
  }

  function resetForm() {
    cpf.value = '';
    municipioId.value = '';
    errors.value = {};
    successMessage.value = '';
  }

  return {
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
    resetForm,
  };
}
