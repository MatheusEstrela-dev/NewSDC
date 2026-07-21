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

  // Autocomplete de municipio (busca sob demanda no endpoint publico)
  const municipioBusca = ref('');
  const municipioResultados = ref([]);
  const buscandoMunicipios = ref(false);
  let buscaTimer = null;

  function buscarMunicipios(termo) {
    municipioBusca.value = termo;
    // Editar o texto invalida qualquer selecao anterior (evita enviar id velho)
    municipioId.value = '';
    clearTimeout(buscaTimer);

    if (termo.trim().length < 2) {
      municipioResultados.value = [];
      return;
    }

    buscaTimer = setTimeout(async () => {
      buscandoMunicipios.value = true;
      try {
        const resp = await fetch(`/forgot-password/municipios?q=${encodeURIComponent(termo)}`, {
          headers: { Accept: 'application/json' },
        });
        municipioResultados.value = resp.ok ? await resp.json() : [];
      } catch {
        municipioResultados.value = [];
      } finally {
        buscandoMunicipios.value = false;
      }
    }, 300);
  }

  function selecionarMunicipio(municipio) {
    municipioId.value = municipio.id;
    municipioBusca.value = `${municipio.nome} / ${municipio.uf}`;
    municipioResultados.value = [];
  }

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
    municipioBusca.value = '';
    municipioResultados.value = [];
    municipioId.value = '';
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
        municipioBusca.value = '';
        municipioResultados.value = [];
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
    municipioBusca.value = '';
    municipioResultados.value = [];
    errors.value = {};
    successMessage.value = '';
  }

  return {
    recoveryMethod,
    cpf,
    municipioId,
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
    resetForm,
  };
}
