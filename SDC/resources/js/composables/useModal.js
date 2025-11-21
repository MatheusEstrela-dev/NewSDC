import { ref } from 'vue';

/**
 * Composable para gerenciar estado de modais
 * Single Responsibility: Gerenciar apenas o estado do modal
 */
export function useModal() {
  const isOpen = ref(false);
  const title = ref('');
  const data = ref(null);

  /**
   * Abre o modal com título e dados
   */
  function open(modalTitle, modalData = null) {
    title.value = modalTitle;
    data.value = modalData;
    isOpen.value = true;
  }

  /**
   * Fecha o modal
   */
  function close() {
    isOpen.value = false;
    // Limpa os dados após um pequeno delay para animação
    setTimeout(() => {
      title.value = '';
      data.value = null;
    }, 300);
  }

  /**
   * Alterna o estado do modal
   */
  function toggle() {
    isOpen.value ? close() : open(title.value, data.value);
  }

  return {
    isOpen,
    title,
    data,
    open,
    close,
    toggle,
  };
}

