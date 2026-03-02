import { ref } from 'vue';

/**
 * Composable para gerenciamento de estado de modais
 * @param {any} initialData - Dados iniciais do modal (default: null)
 * @returns {Object} - isOpen, data, loading, open, close, setLoading, toggle
 */
export function useModalState(initialData = null) {
  const isOpen = ref(false);
  const data = ref(initialData);
  const loading = ref(false);

  const open = (item = null) => {
    data.value = item;
    isOpen.value = true;
  };

  const close = () => {
    isOpen.value = false;
    data.value = null;
    loading.value = false;
  };

  const setLoading = (value) => {
    loading.value = value;
  };

  const toggle = () => {
    isOpen.value = !isOpen.value;
  };

  /**
   * Abre o modal e carrega dados de forma assincrona
   * @param {Function} fetchFn - Funcao que retorna uma Promise com os dados
   * @param {Object} options - Opcoes { onSuccess, onError }
   */
  const openWithLoading = async (fetchFn, options = {}) => {
    isOpen.value = true;
    loading.value = true;

    try {
      const result = await fetchFn();
      data.value = result;
      options.onSuccess?.(result);
    } catch (error) {
      options.onError?.(error);
      if (!options.keepOpenOnError) {
        close();
      }
    } finally {
      loading.value = false;
    }
  };

  return {
    isOpen,
    data,
    loading,
    open,
    close,
    setLoading,
    toggle,
    openWithLoading
  };
}

/**
 * Composable para gerenciamento de dialogo de confirmacao
 * @returns {Object} - isOpen, message, title, confirm, handleConfirm, cancel, isLoading
 */
export function useConfirmDialog() {
  const isOpen = ref(false);
  const message = ref('');
  const title = ref('Confirmar');
  const isLoading = ref(false);
  let onConfirmCallback = null;

  const confirm = (msg, callback, dialogTitle = 'Confirmar') => {
    message.value = msg;
    title.value = dialogTitle;
    onConfirmCallback = callback;
    isOpen.value = true;
  };

  const handleConfirm = async () => {
    if (!onConfirmCallback) return;

    isLoading.value = true;
    try {
      await onConfirmCallback();
    } finally {
      isLoading.value = false;
      isOpen.value = false;
      onConfirmCallback = null;
    }
  };

  const cancel = () => {
    isOpen.value = false;
    onConfirmCallback = null;
  };

  return {
    isOpen,
    message,
    title,
    isLoading,
    confirm,
    handleConfirm,
    cancel
  };
}
