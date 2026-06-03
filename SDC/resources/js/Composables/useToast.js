export function useToast() {
  const show = (message, type = 'success', options = {}) => {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, type, ...options } }));
  };

  return { show, toast: show };
}
